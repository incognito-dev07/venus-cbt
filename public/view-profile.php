<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    header("Location: /leaderboard");
    exit();
}

$profile_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];

if ($profile_id == $current_user_id) {
    header("Location: /profile");
    exit();
}

$profile_user = findUserById($profile_id);
if (!$profile_user) {
    header("HTTP/1.0 404 Not Found");
    echo "User not found";
    exit();
}

$is_following = isFollowing($current_user_id, $profile_id);
$is_friend = isFriend($current_user_id, $profile_id);
$followers_count = getFollowersCount($profile_id);
$user_stats = getUserStats($profile_id);
$is_google_user = isset($profile_user['auth_provider']) && $profile_user['auth_provider'] === 'google';

$from = isset($_GET['from']) ? $_GET['from'] : 'leaderboard';
$back_url = ($from == 'messages') ? '/messages' : '/leaderboard';

$csrf_token = generateCSRFToken();

if (isset($_GET['action']) && $_GET['action'] == 'follow') {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die("Invalid security token");
    }
    followUser($current_user_id, $profile_id);
    header("Location: /view-profile?id=" . $profile_id . "&from=" . $from);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'unfollow') {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die("Invalid security token");
    }
    unfollowUser($current_user_id, $profile_id);
    header("Location: /view-profile?id=" . $profile_id . "&from=" . $from);
    exit();
}

$achievements = [];

if (isset($user_stats) && is_array($user_stats)) {
  if (isset($user_stats['total_tests'])) {
    if ($user_stats['total_tests'] >= 1) {
      $achievements[] = ['icon' => 'fa-star', 'name' => 'First Test', 'desc' => 'Completed first test'];
    }
    if ($user_stats['total_tests'] >= 5) {
      $achievements[] = ['icon' => 'fa-fire', 'name' => 'On Fire', 'desc' => 'Completed 5 tests'];
    }
    if ($user_stats['total_tests'] >= 10) {
      $achievements[] = ['icon' => 'fa-dragon', 'name' => 'Test Master', 'desc' => 'Completed 10 tests'];
    }
  }
    
  if (isset($user_stats['best_score']) && $user_stats['best_score'] >= 90) {
    $achievements[] = ['icon' => 'fa-brain', 'name' => 'Genius', 'desc' => 'Scored 90%+ on a test'];
  }
  if (isset($user_stats['avg_score']) && $user_stats['avg_score'] >= 80) {
    $achievements[] = ['icon' => 'fa-graduation-cap', 'name' => 'Scholar', 'desc' => 'Average score 80%+'];
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - <?php echo htmlspecialchars($profile_user['username']); ?>'s Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="profile-box">
      <div class="profile-header">
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="back-btn">
          <i class="fas fa-arrow-left"></i> Back to <?php echo ($from == 'messages') ? 'Messages' : 'Leaderboard'; ?>
        </a>
        <h2><i class="fas fa-user-circle"></i> User Profile</h2>
      </div>
            
      <div class="profile-image-container">
        <div class="profile-image-wrapper">
          <?php 
            if (isset($profile_user['profile_image']) && $profile_user['profile_image'] && imageExists($profile_user['profile_image'])): 
          ?>
            <img src="<?php echo htmlspecialchars($profile_user['profile_image']); ?>" alt="Profile" class="profile-image">
          <?php elseif ($is_google_user && isset($profile_user['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($profile_user['profile_image']); ?>" alt="Profile" class="profile-image">
          <?php else: ?>
            <div class="default-avatar">
              <i class="fas fa-user-circle"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="profile-username">
          <h3><?php echo htmlspecialchars($profile_user['username']); ?></h3>
        </div>
        
        <div class="profile-stats">
          <span><i class="fas fa-users"></i> <?php echo $followers_count; ?> followers</span>
          <span class="stat-divider">•</span>
          <span><i class="fas fa-level-up-alt"></i> Level <?php echo $user_stats['level']; ?></span>
          <?php if ($user_stats['streak'] > 0): ?>
            <span class="stat-divider">•</span>
            <span><i class="fas fa-fire"></i> <?php echo $user_stats['streak']; ?> day streak</span>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="profile-actions">
        <?php if ($is_following): ?>
          <a href="?id=<?php echo $profile_id; ?>&action=unfollow&from=<?php echo $from; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-danger" onclick="return confirm('Unfollow this user?')">
            <i class="fas fa-user-minus"></i> Unfollow
          </a>
        <?php else: ?>
          <a href="?id=<?php echo $profile_id; ?>&action=follow&from=<?php echo $from; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Follow
          </a>
        <?php endif; ?>
        
        <?php if ($is_friend): ?>
          <a href="/messages?user=<?php echo $profile_id; ?>" class="btn btn-primary">
            <i class="fas fa-comment"></i> Message
          </a>
        <?php endif; ?>
      </div>
      
      <div class="info-card">
        <div class="info-item">
          <div class="info-header">
            <i class="fas fa-quote-left"></i>
            <span class="info-label">Bio</span>
          </div>
          <span class="info-value"><?php echo htmlspecialchars($profile_user['bio'] ?? 'No bio yet'); ?></span>
        </div>
        
        <div class="info-item">
          <div class="info-header">
            <i class="fas fa-calendar-alt"></i>
            <span class="info-label">Joined:</span>
            <span class="info-value-short"><?php echo date('M j, Y', strtotime($profile_user['created_at'] ?? 'now')); ?></span>
          </div>
        </div>
        
        <?php if (isset($profile_user['auth_provider']) && $profile_user['auth_provider'] === 'google'): ?>
        <div class="info-item">
          <div class="info-header">
            <i class="fab fa-google"></i>
            <span class="info-label">Connected with Google</span>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="achievements-section">
        <h3><i class="fas fa-medal"></i> Achievements</h3>
        
        <div class="achievements-grid">
          <?php if (!empty($achievements)): ?>
            <?php foreach ($achievements as $achievement): ?>
              <div class="achievement-card earned">
                <div class="achievement-header">
                  <i class="fas <?php echo $achievement['icon']; ?>"></i>
                  <h4><?php echo htmlspecialchars($achievement['name']); ?></h4>
                </div>
                <p><?php echo htmlspecialchars($achievement['desc']); ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-achievements">
              <i class="fas fa-medal"></i>
              <p>No achievements yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="/js/script.js"></script>
</body>
</html>