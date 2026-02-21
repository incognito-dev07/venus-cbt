<?php
// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// User Functions
function findUserByUsername($username) {
    if (empty($username)) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $user['privacy'] = json_decode($user['privacy_settings'], true) ?: [];
        unset($user['privacy_settings']);
    }
    return $user ?: null;
}

function findUserByEmail($email) {
    if (empty($email)) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $user['privacy'] = json_decode($user['privacy_settings'], true) ?: [];
        unset($user['privacy_settings']);
    }
    return $user ?: null;
}

function findUserById($id) {
    if (empty($id)) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $user['privacy'] = json_decode($user['privacy_settings'], true) ?: [];
        unset($user['privacy_settings']);
    }
    return $user ?: null;
}

function updateUsername($user_id, $new_username) {
    if (empty($user_id) || empty($new_username)) return false;
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET username = :username WHERE id = :id');
    $stmt->bindValue(':username', $new_username, SQLITE3_TEXT);
    $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
    return $stmt->execute() ? true : false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /login");
        exit();
    }
}

function generateUserImageName($user_id, $original_name) {
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    return 'user_' . $user_id . '.' . $extension;
}

function curlPost($url, $data) {
    if (empty($url)) return ['response' => null, 'code' => 400];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['response' => $response, 'code' => $http_code];
}

function curlGet($url, $token) {
    if (empty($url)) return null;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function getCourses() {
    if (!file_exists(COURSES_FILE)) return [];
    $json = file_get_contents(COURSES_FILE);
    return json_decode($json, true) ?: [];
}

function getCourse($course_id) {
    if (empty($course_id)) return null;
    $courses = getCourses();
    foreach ($courses as $course) {
        if ($course['id'] === $course_id) {
            return $course;
        }
    }
    return null;
}

function getQuestionFile($course_id) {
  $files = [
    'MTS101' => 'mathematics.json',
    'PHY101' => 'physics.json',
    'STA111' => 'statistics.json',
    'CSC101' => 'computer.json',
    'GNS103' => 'literacy.json'
  ];
    
  if (!isset($files[$course_id])) {
    return null;
  }
    
  return QUESTIONS_DIR . $files[$course_id];
}

function getQuestions($course_id) {
  $file = getQuestionFile($course_id);
  if (!$file || !file_exists($file)) {
    error_log("Question file not found: " . $file);
    return [];
  }
    
  $json = file_get_contents($file);
  if ($json === false) {
    error_log("Failed to read question file: " . $file);
    return [];
  }
    
  $data = json_decode($json, true);
  if ($data === null) {
    error_log("Invalid JSON in question file: " . $file);
    return [];
  }
    
  // The JSON files have course_id as key
  return isset($data[$course_id]) ? $data[$course_id] : [];
}

function checkRateLimit($user_id, $action, $limit, $period_seconds) {
    if (empty($user_id)) return true;
    $db = getDB();
    $cutoff = date('Y-m-d H:i:s', time() - $period_seconds);
    
    switch($action) {
        case 'message':
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM messages 
                                  WHERE from_id = :user_id AND created_at > :cutoff');
            break;
        case 'follow':
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM follows 
                                  WHERE follower_id = :user_id AND created_at > :cutoff');
            break;
        default:
            return true;
    }
    
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':cutoff', $cutoff, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    return ($result['count'] ?? 0) < $limit;
}

function followUser($follower_id, $following_id) {
    if ($follower_id == $following_id || empty($follower_id) || empty($following_id)) return false;
    
    if (!checkRateLimit($follower_id, 'follow', 30, 3600)) {
        return false;
    }
    
    $db = getDB();
    
    $check = $db->prepare('SELECT 1 FROM follows WHERE follower_id = :follower AND following_id = :following');
    $check->bindValue(':follower', $follower_id, SQLITE3_INTEGER);
    $check->bindValue(':following', $following_id, SQLITE3_INTEGER);
    if ($check->execute()->fetchArray()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (:follower, :following)');
        $stmt->bindValue(':follower', $follower_id, SQLITE3_INTEGER);
        $stmt->bindValue(':following', $following_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        $follower = findUserById($follower_id);
        $notification_data = json_encode([
            'follower_id' => $follower_id,
            'follower_name' => $follower['username'] ?? 'Unknown'
        ]);
        
        $notif = $db->prepare('INSERT INTO notifications (user_id, type, data) VALUES (:user_id, :type, :data)');
        $notif->bindValue(':user_id', $following_id, SQLITE3_INTEGER);
        $notif->bindValue(':type', 'follow', SQLITE3_TEXT);
        $notif->bindValue(':data', $notification_data, SQLITE3_TEXT);
        $notif->execute();
        
        checkMutualFollow($follower_id, $following_id);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function unfollowUser($follower_id, $following_id) {
    if ($follower_id == $following_id || empty($follower_id) || empty($following_id)) return false;
    
    $db = getDB();
    
    try {
        $stmt = $db->prepare('DELETE FROM follows WHERE follower_id = :follower AND following_id = :following');
        $stmt->bindValue(':follower', $follower_id, SQLITE3_INTEGER);
        $stmt->bindValue(':following', $following_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        removeFriendship($follower_id, $following_id);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function checkMutualFollow($user1_id, $user2_id) {
    if (empty($user1_id) || empty($user2_id)) return false;
    $db = getDB();
    
    $check1 = $db->prepare('SELECT 1 FROM follows WHERE follower_id = :u1 AND following_id = :u2');
    $check1->bindValue(':u1', $user1_id, SQLITE3_INTEGER);
    $check1->bindValue(':u2', $user2_id, SQLITE3_INTEGER);
    $follow1 = $check1->execute()->fetchArray();
    
    $check2 = $db->prepare('SELECT 1 FROM follows WHERE follower_id = :u2 AND following_id = :u1');
    $check2->bindValue(':u2', $user2_id, SQLITE3_INTEGER);
    $check2->bindValue(':u1', $user1_id, SQLITE3_INTEGER);
    $follow2 = $check2->execute()->fetchArray();
    
    if ($follow1 && $follow2) {
        addFriendship($user1_id, $user2_id);
        
        $user1 = findUserById($user1_id);
        $user2 = findUserById($user2_id);
        
        $notif1 = $db->prepare('INSERT INTO notifications (user_id, type, data) VALUES (:user_id, :type, :data)');
        $notif1->bindValue(':user_id', $user1_id, SQLITE3_INTEGER);
        $notif1->bindValue(':type', 'friend', SQLITE3_TEXT);
        $notif1->bindValue(':data', json_encode(['user_id' => $user2_id, 'user_name' => $user2['username'] ?? 'Unknown']), SQLITE3_TEXT);
        $notif1->execute();
        
        $notif2 = $db->prepare('INSERT INTO notifications (user_id, type, data) VALUES (:user_id, :type, :data)');
        $notif2->bindValue(':user_id', $user2_id, SQLITE3_INTEGER);
        $notif2->bindValue(':type', 'friend', SQLITE3_TEXT);
        $notif2->bindValue(':data', json_encode(['user_id' => $user1_id, 'user_name' => $user1['username'] ?? 'Unknown']), SQLITE3_TEXT);
        $notif2->execute();
        
        return true;
    }
    return false;
}

function addFriendship($user1_id, $user2_id) {
    if (empty($user1_id) || empty($user2_id)) return false;
    $db = getDB();
    
    try {
        $stmt1 = $db->prepare('INSERT OR IGNORE INTO friends (user_id, friend_id) VALUES (:u1, :u2)');
        $stmt1->bindValue(':u1', $user1_id, SQLITE3_INTEGER);
        $stmt1->bindValue(':u2', $user2_id, SQLITE3_INTEGER);
        $stmt1->execute();
        
        $stmt2 = $db->prepare('INSERT OR IGNORE INTO friends (user_id, friend_id) VALUES (:u2, :u1)');
        $stmt2->bindValue(':u2', $user2_id, SQLITE3_INTEGER);
        $stmt2->bindValue(':u1', $user1_id, SQLITE3_INTEGER);
        $stmt2->execute();
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function removeFriendship($user1_id, $user2_id) {
    if (empty($user1_id) || empty($user2_id)) return false;
    $db = getDB();
    
    $stmt = $db->prepare('DELETE FROM friends WHERE (user_id = :u1 AND friend_id = :u2) OR (user_id = :u2 AND friend_id = :u1)');
    $stmt->bindValue(':u1', $user1_id, SQLITE3_INTEGER);
    $stmt->bindValue(':u2', $user2_id, SQLITE3_INTEGER);
    $stmt->execute();
    
    return true;
}

function getFollowersCount($user_id) {
    if (empty($user_id)) return 0;
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM follows WHERE following_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $result['count'] ?? 0;
}

function getFollowingCount($user_id) {
    if (empty($user_id)) return 0;
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM follows WHERE follower_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $result['count'] ?? 0;
}

function isFollowing($follower_id, $following_id) {
    if (empty($follower_id) || empty($following_id)) return false;
    $db = getDB();
    $stmt = $db->prepare('SELECT 1 FROM follows WHERE follower_id = :follower AND following_id = :following');
    $stmt->bindValue(':follower', $follower_id, SQLITE3_INTEGER);
    $stmt->bindValue(':following', $following_id, SQLITE3_INTEGER);
    return $stmt->execute()->fetchArray() ? true : false;
}

function getFriends($user_id, $limit = 50) {
    if (empty($user_id)) return [];
    $db = getDB();
    $stmt = $db->prepare('SELECT u.id, u.username, u.profile_image, u.last_activity 
                          FROM friends f 
                          JOIN users u ON f.friend_id = u.id 
                          WHERE f.user_id = :user_id
                          ORDER BY u.username
                          LIMIT :limit');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $friends = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $friends[] = $row;
    }
    return $friends;
}

function isFriend($user_id, $friend_id) {
    if (empty($user_id) || empty($friend_id)) return false;
    $db = getDB();
    $stmt = $db->prepare('SELECT 1 FROM friends WHERE user_id = :user_id AND friend_id = :friend_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':friend_id', $friend_id, SQLITE3_INTEGER);
    return $stmt->execute()->fetchArray() ? true : false;
}

function searchUsers($query, $current_user_id, $limit = 50) {
    if (empty($current_user_id)) return [];
    $db = getDB();
    $search = "%$query%";
    
    $stmt = $db->prepare('SELECT id, username, profile_image 
                          FROM users 
                          WHERE id != :current_id AND username LIKE :search 
                          ORDER BY username
                          LIMIT :limit');
    $stmt->bindValue(':current_id', $current_user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':search', $search, SQLITE3_TEXT);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['is_friend'] = isFriend($current_user_id, $row['id']);
        $row['is_following'] = isFollowing($current_user_id, $row['id']);
        $users[] = $row;
    }
    return $users;
}

function saveTestResult($data) {
    if (empty($data['user_id']) || empty($data['course_id'])) return false;
    
    $db = getDB();
    
    $questions = $data['questions'] ?? [];
    if (empty($questions) && isset($_SESSION['test']['questions'])) {
        $questions = $_SESSION['test']['questions'];
    }
    
    $db->exec('BEGIN IMMEDIATE');
    
    try {
        $stmt = $db->prepare('INSERT INTO tests (user_id, course_id, score, total, percentage, time_taken, answers, questions) 
                              VALUES (:user_id, :course_id, :score, :total, :percentage, :time_taken, :answers, :questions)');
        
        $stmt->bindValue(':user_id', $data['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':course_id', $data['course_id'], SQLITE3_TEXT);
        $stmt->bindValue(':score', $data['score'], SQLITE3_INTEGER);
        $stmt->bindValue(':total', $data['total'], SQLITE3_INTEGER);
        $stmt->bindValue(':percentage', $data['percentage'], SQLITE3_FLOAT);
        $stmt->bindValue(':time_taken', $data['time_taken'], SQLITE3_INTEGER);
        $stmt->bindValue(':answers', json_encode($data['answers']), SQLITE3_TEXT);
        $stmt->bindValue(':questions', json_encode($questions), SQLITE3_TEXT);
        
        $stmt->execute();
        $test_id = $db->lastInsertRowID();
        
        // Update leaderboard
        $points = ($data['score'] * 10) + 50;
        
        $lp_stmt = $db->prepare('INSERT INTO leaderboard_points (user_id, course_id, points, test_id) 
                                  VALUES (:user_id, :course_id, :points, :test_id)');
        $lp_stmt->bindValue(':user_id', $data['user_id'], SQLITE3_INTEGER);
        $lp_stmt->bindValue(':course_id', $data['course_id'], SQLITE3_TEXT);
        $lp_stmt->bindValue(':points', $points, SQLITE3_INTEGER);
        $lp_stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
        $lp_stmt->execute();
        
        // Update user stats
        $user = findUserById($data['user_id']);
        $total_tests = ($user['total_tests'] ?? 0) + 1;
        $total_points = ($user['total_points'] ?? 0) + $points;
        $old_avg = $user['avg_score'] ?? 0;
        $new_avg = ($total_tests > 1) ? (($old_avg * ($total_tests - 1)) + $data['percentage']) / $total_tests : $data['percentage'];
        
        $update = $db->prepare('UPDATE users SET 
                                total_points = :points,
                                total_tests = :tests,
                                avg_score = :avg
                                WHERE id = :user_id');
        $update->bindValue(':points', $total_points, SQLITE3_INTEGER);
        $update->bindValue(':tests', $total_tests, SQLITE3_INTEGER);
        $update->bindValue(':avg', $new_avg, SQLITE3_FLOAT);
        $update->bindValue(':user_id', $data['user_id'], SQLITE3_INTEGER);
        $update->execute();
        
        $db->exec('COMMIT');
        
        updateDailyStreak($data['user_id']);
        
        return $test_id;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

function getTestsByUser($user_id, $include_hidden = false, $limit = 100, $offset = 0) {
    if (empty($user_id)) return [];
    $db = getDB();
    
    $sql = 'SELECT * FROM tests WHERE user_id = :user_id';
    if (!$include_hidden) {
        $sql .= ' AND hidden_from_history = 0';
    }
    $sql .= ' ORDER BY date_taken DESC LIMIT :limit OFFSET :offset';
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $tests = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['answers'] = json_decode($row['answers'], true) ?: [];
        $row['questions'] = json_decode($row['questions'], true) ?: [];
        $tests[] = $row;
    }
    return $tests;
}

function getTestById($test_id) {
    if (empty($test_id)) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM tests WHERE id = :id');
    $stmt->bindValue(':id', $test_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $test = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($test) {
        $test['answers'] = json_decode($test['answers'], true) ?: [];
        $test['questions'] = json_decode($test['questions'], true) ?: [];
    }
    return $test ?: null;
}

function hideAllFromHistory($user_id) {
    if (empty($user_id)) return false;
    $db = getDB();
    $stmt = $db->prepare('UPDATE tests SET hidden_from_history = 1 WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    return $stmt->execute() ? true : false;
}

function calculateLevel($points) {
    $points = max(0, $points ?? 0);
    $level = 1;
    $points_needed = 100;
    $multiplier = 2;
    
    while ($points >= $points_needed) {
        $points -= $points_needed;
        $level++;
        $points_needed = $points_needed * $multiplier;
    }
    
    return $level;
}

function updateDailyStreak($user_id) {
    if (empty($user_id)) return;
    $db = getDB();
    $today = date('Y-m-d');
    
    $user = findUserById($user_id);
    if (!$user) return;
    
    $current_streak = $user['current_streak'] ?? 0;
    $longest_streak = $user['longest_streak'] ?? 0;
    $last_activity = $user['last_activity'] ?? null;
    
    if ($last_activity == date('Y-m-d', strtotime('-1 day'))) {
        $current_streak++;
        if ($current_streak > $longest_streak) {
            $longest_streak = $current_streak;
        }
    } elseif ($last_activity != $today) {
        $current_streak = 1;
    }
    
    $stmt = $db->prepare('UPDATE users SET current_streak = :streak, longest_streak = :longest, last_activity = :today WHERE id = :id');
    $stmt->bindValue(':streak', $current_streak, SQLITE3_INTEGER);
    $stmt->bindValue(':longest', $longest_streak, SQLITE3_INTEGER);
    $stmt->bindValue(':today', $today, SQLITE3_TEXT);
    $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();
}

function getStreak($user_id) {
    $user = findUserById($user_id);
    return $user ? ($user['current_streak'] ?? 0) : 0;
}

function createNotification($user_id, $type, $data) {
    if (empty($user_id) || empty($type)) return false;
    $db = getDB();
    
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, data) VALUES (:user_id, :type, :data)');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $stmt->bindValue(':data', json_encode($data), SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

function getNotifications($user_id, $limit = 50) {
    if (empty($user_id)) return [];
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $notifications = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['data'] = json_decode($row['data'], true) ?: [];
        $row['read'] = $row['is_read'];
        unset($row['is_read']);
        $notifications[] = $row;
    }
    return $notifications;
}

function markNotificationAsRead($notification_id) {
    if (empty($notification_id)) return false;
    $db = getDB();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
    $stmt->bindValue(':id', $notification_id, SQLITE3_INTEGER);
    return $stmt->execute() ? true : false;
}

function markAllNotificationsAsRead($user_id) {
    if (empty($user_id)) return false;
    $db = getDB();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    return $stmt->execute() ? true : false;
}

function clearAllNotifications($user_id) {
    if (empty($user_id)) return false;
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM notifications WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    return $stmt->execute() ? true : false;
}

function getUnreadNotificationCount($user_id) {
    if (empty($user_id)) return 0;
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $result['count'] ?? 0;
}

function getGlobalLeaderboard($limit = 100, $offset = 0) {
    $db = getDB();
    
    $query = 'SELECT 
                u.id as user_id,
                u.username,
                COALESCE(u.total_points, 0) as points,
                COALESCE(u.total_tests, 0) as tests_taken,
                COALESCE(u.avg_score, 0) as avg_score
              FROM users u
              WHERE u.total_tests > 0
              ORDER BY u.total_points DESC
              LIMIT :limit OFFSET :offset';
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $leaderboard = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $leaderboard[] = $row;
    }
    return $leaderboard;
}

function getWeeklyLeaderboard($limit = 100) {
    $db = getDB();
    $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
    
    $query = 'SELECT 
                u.id as user_id,
                u.username,
                COALESCE(SUM(lp.points), 0) as points,
                COALESCE(COUNT(DISTINCT lp.test_id), 0) as tests_taken,
                COALESCE(AVG(t.percentage), 0) as avg_score
              FROM leaderboard_points lp
              JOIN users u ON lp.user_id = u.id
              JOIN tests t ON lp.test_id = t.id
              WHERE lp.created_at >= :week_ago
              GROUP BY lp.user_id
              ORDER BY points DESC
              LIMIT :limit';
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':week_ago', $week_ago, SQLITE3_TEXT);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $leaderboard = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $leaderboard[] = $row;
    }
    return $leaderboard;
}

function getCourseLeaderboard($course_id, $limit = 100) {
    if (empty($course_id)) return [];
    $db = getDB();
    
    $query = 'SELECT 
                u.id as user_id,
                u.username,
                COALESCE(SUM(lp.points), 0) as points,
                COALESCE(COUNT(DISTINCT lp.test_id), 0) as tests_taken,
                COALESCE(AVG(t.percentage), 0) as avg_score
              FROM leaderboard_points lp
              JOIN users u ON lp.user_id = u.id
              JOIN tests t ON lp.test_id = t.id
              WHERE lp.course_id = :course_id
              GROUP BY lp.user_id
              ORDER BY points DESC
              LIMIT :limit';
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':course_id', $course_id, SQLITE3_TEXT);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $leaderboard = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $leaderboard[] = $row;
    }
    return $leaderboard;
}

function getUserRank($user_id, $type = 'global') {
    if (empty($user_id)) return null;
    $db = getDB();
    
    if ($type == 'global') {
        $query = 'SELECT COUNT(*) + 1 as rank 
                  FROM users 
                  WHERE total_points > (SELECT COALESCE(total_points, 0) FROM users WHERE id = :user_id)
                  AND total_tests > 0';
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['rank'] : null;
        
    } else {
        $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $query = 'SELECT COUNT(DISTINCT lp.user_id) + 1 as rank
                  FROM leaderboard_points lp
                  WHERE lp.created_at >= :week_ago
                  AND (SELECT COALESCE(SUM(points), 0) FROM leaderboard_points 
                       WHERE user_id = lp.user_id AND created_at >= :week_ago) >
                      (SELECT COALESCE(SUM(points), 0) FROM leaderboard_points 
                       WHERE user_id = :user_id AND created_at >= :week_ago)';
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':week_ago', $week_ago, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['rank'] : null;
    }
}

function sendMessage($from_id, $to_id, $message) {
    if (empty($from_id) || empty($to_id) || empty($message)) return false;
    
    if (!checkRateLimit($from_id, 'message', 20, 60)) {
        return false;
    }
    
    $db = getDB();
    
    $stmt = $db->prepare('INSERT INTO messages (from_id, to_id, message) VALUES (:from_id, :to_id, :message)');
    $stmt->bindValue(':from_id', $from_id, SQLITE3_INTEGER);
    $stmt->bindValue(':to_id', $to_id, SQLITE3_INTEGER);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

function getConversations($user_id, $limit = 50) {
    if (empty($user_id)) return [];
    $db = getDB();
    
    $query = 'SELECT 
                m.*,
                u.username,
                u.profile_image,
                (SELECT COUNT(*) FROM messages WHERE to_id = :user_id AND from_id = 
                    CASE WHEN m.from_id = :user_id THEN m.to_id ELSE m.from_id END 
                    AND is_read = 0) as unread_count
              FROM messages m
              JOIN users u ON (m.from_id = u.id OR m.to_id = u.id) AND u.id != :user_id
              WHERE m.id IN (
                SELECT MAX(id)
                FROM messages
                WHERE from_id = :user_id OR to_id = :user_id
                GROUP BY CASE WHEN from_id = :user_id THEN to_id ELSE from_id END
              )
              ORDER BY m.created_at DESC
              LIMIT :limit';
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $conversations = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $other_id = $row['from_id'] == $user_id ? $row['to_id'] : $row['from_id'];
        $conversations[] = [
            'user_id' => $other_id,
            'username' => $row['username'],
            'last_message' => $row['message'],
            'timestamp' => $row['created_at'],
            'unread' => $row['unread_count'] ?? 0
        ];
    }
    return $conversations;
}

function getMessages($user_id, $other_id, $limit = 100) {
    if (empty($user_id) || empty($other_id)) return [];
    $db = getDB();
    
    $query = 'SELECT * FROM messages 
              WHERE (from_id = :user_id AND to_id = :other_id) 
                 OR (from_id = :other_id AND to_id = :user_id)
              ORDER BY created_at ASC
              LIMIT :limit';
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':other_id', $other_id, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $messages = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = $row;
    }
    
    $update = $db->prepare('UPDATE messages SET is_read = 1 WHERE from_id = :other_id AND to_id = :user_id');
    $update->bindValue(':other_id', $other_id, SQLITE3_INTEGER);
    $update->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $update->execute();
    
    return $messages;
}

function getUnreadCount($user_id) {
    if (empty($user_id)) return 0;
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM messages WHERE to_id = :user_id AND is_read = 0');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $result['count'] ?? 0;
}

function getAllUsers($limit = 1000) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username FROM users ORDER BY username LIMIT :limit');
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    return $users;
}

function getUserStats($user_id) {
    if (empty($user_id)) {
        return [
            'total_tests' => 0,
            'avg_score' => 0,
            'best_course' => 'N/A',
            'best_score' => 0,
            'total_points' => 0,
            'level' => 1,
            'streak' => 0,
            'global_rank' => null,
            'weekly_rank' => null
        ];
    }
    
    $user = findUserById($user_id);
    if (!$user) {
        return [
            'total_tests' => 0,
            'avg_score' => 0,
            'best_course' => 'N/A',
            'best_score' => 0,
            'total_points' => 0,
            'level' => 1,
            'streak' => 0,
            'global_rank' => null,
            'weekly_rank' => null
        ];
    }
    
    $db = getDB();
    
    $stmt = $db->prepare('SELECT course_id, AVG(percentage) as avg 
                          FROM tests 
                          WHERE user_id = :user_id 
                          GROUP BY course_id 
                          ORDER BY avg DESC 
                          LIMIT 1');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $best_course_result = $stmt->execute();
    $best_course = $best_course_result->fetchArray(SQLITE3_ASSOC);
    
    $stmt2 = $db->prepare('SELECT MAX(percentage) as best, course_id 
                           FROM tests 
                           WHERE user_id = :user_id');
    $stmt2->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $best_score_result = $stmt2->execute();
    $best_score = $best_score_result->fetchArray(SQLITE3_ASSOC);
    
    $avg_score = isset($user['avg_score']) && is_numeric($user['avg_score']) ? round($user['avg_score'], 2) : 0;
    $best_score_value = ($best_score && isset($best_score['best']) && is_numeric($best_score['best'])) ? round($best_score['best'], 2) : 0;
    
    return [
        'total_tests' => $user['total_tests'] ?? 0,
        'avg_score' => $avg_score,
        'best_course' => ($best_course && isset($best_course['course_id'])) ? $best_course['course_id'] : 'N/A',
        'best_score' => $best_score_value,
        'global_rank' => getUserRank($user_id, 'global'),
        'weekly_rank' => getUserRank($user_id, 'weekly'),
        'total_points' => $user['total_points'] ?? 0,
        'level' => calculateLevel($user['total_points'] ?? 0),
        'streak' => $user['current_streak'] ?? 0
    ];
}

function imageExists($path) {
    if (empty($path)) return false;
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $headers = @get_headers($path);
        return $headers && strpos($headers[0], '200');
    }
    return file_exists($path);
}
?>