<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user = findUserById($_SESSION['user_id']);
$message = '';
$error = '';

$is_google_user = isset($user['auth_provider']) && $user['auth_provider'] === 'google';

$csrf_token = generateCSRFToken();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_image'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token!";
    } else {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            
            if (!in_array($mime, $allowed_types)) {
                $error = "Only JPG, PNG, and GIF images are allowed!";
            } elseif ($file['size'] > $max_size) {
                $error = "Image size must be less than 5MB!";
            } else {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'user_' . $user['id'] . '.' . $extension;
                $upload_path = UPLOAD_DIR . $filename;
                
                $old_files = glob(UPLOAD_DIR . 'user_' . $user['id'] . '.*');
                foreach ($old_files as $old_file) {
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $db = getDB();
                    $stmt = $db->prepare('UPDATE users SET profile_image = :image WHERE id = :id');
                    $stmt->bindValue(':image', $upload_path, SQLITE3_TEXT);
                    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    $message = "Profile image updated successfully!";
                    $user = findUserById($_SESSION['user_id']);
                } else {
                    $error = "Failed to upload image!";
                }
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = "Error uploading file. Code: " . $file['error'];
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_bio'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token!";
    } else {
        $new_bio = trim($_POST['bio']);
        
        if (strlen($new_bio) > 500) {
            $error = "Bio must be less than 500 characters!";
        } else {
            $db = getDB();
            $stmt = $db->prepare('UPDATE users SET bio = :bio WHERE id = :id');
            $stmt->bindValue(':bio', $new_bio, SQLITE3_TEXT);
            $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            $message = "Bio updated successfully!";
            $user = findUserById($_SESSION['user_id']);
        }
    }
}

$user_stats = getUserStats($_SESSION['user_id']);
$followers_count = getFollowersCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
      <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            
      <?php if ($message): ?>
        <div class="success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>
            
      <?php if ($error): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
            
      <div class="profile-image-container">
        <div class="profile-image-wrapper">
          <?php 
            if (isset($user['profile_image']) && $user['profile_image'] && imageExists($user['profile_image'])): 
          ?>
            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="profile-image" id="profileImage">
          <?php else: ?>
            <div class="default-avatar" id="profileImage">
              <i class="fas fa-user-circle"></i>
            </div>
          <?php endif; ?>
          
          <form method="post" enctype="multipart/form-data" id="imageUploadForm" action="/api/upload">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="file" name="profile_image" id="profileImageInput" accept="image/*" style="display: none;">
            <button type="button" class="image-upload-btn" onclick="document.getElementById('profileImageInput').click();">
              <i class="fas fa-camera"></i>
            </button>
          </form>
        </div>
        <div class="profile-username">
          <h3><?php echo htmlspecialchars($user['username']); ?></h3>
        </div>
        
        <div class="profile-stats">
          <span><i class="fas fa-users"></i> <?php echo $followers_count; ?> followers</span>
          <span class="stat-divider">•</span>
          <span><i class="fas fa-level-up-alt"></i> Level <?php echo $user_stats['level']; ?></span>
          <?php if (($user_stats['streak'] ?? 0) > 0): ?>
            <span class="stat-divider">•</span>
            <span><i class="fas fa-fire"></i> <?php echo $user_stats['streak']; ?> day streak</span>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="info-card bio">
        <div class="info-item">
          <div class="info-header">
            <i class="fas fa-quote-left"></i>
            <span class="info-label">Bio</span>
          </div>
          <span class="info-value"><?php echo htmlspecialchars($user['bio'] ?? 'No bio yet'); ?></span>
        </div>
        
        <div class="info-item">
          <div class="info-header">
            <i class="fas fa-calendar-alt"></i>
            <span class="info-label">Joined:</span>
            <span class="info-value-short"><?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
          </div>
        </div>
        
        <?php if (isset($user['auth_provider']) && $user['auth_provider'] === 'google'): ?>
        <div class="info-item">
          <div class="info-header">
            <i class="fab fa-google"></i>
            <span class="info-label">Connected with Google</span>
          </div>
        </div>
        <?php endif; ?>
      </div>
      
      <button class="btn btn-primary btn-block edit-bio-btn" onclick="toggleBioForm()">
        <i class="fas fa-edit"></i> Edit Bio
      </button>
      
      <div class="upload-form" id="bioForm" style="display: none;">
        <h3><i class="fas fa-edit"></i> Update Bio</h3>
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
          <div class="form-group">
            <textarea name="bio" rows="3" placeholder="Write something about yourself..." maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
          </div>
          <button type="submit" name="update_bio" class="btn btn-primary btn-block" style="margin-bottom: 0.7rem;">
            <i class="fas fa-save"></i> Save Bio
          </button>
          <button type="button" class="btn btn-danger btn-block" onclick="toggleBioForm()">
            <i class="fas fa-arrow-left"></i> Back
          </button>
        </form>
      </div>

      <div class="stats-section">
        <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
        
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-pencil-alt"></i>
            </div>
            <div class="stat-content">
              <span class="stat-value"><?php echo $user_stats['total_tests'] ?? 0; ?></span>
              <span class="stat-label">Tests Taken</span>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-percent"></i>
            </div>
            <div class="stat-content">
              <span class="stat-value"><?php echo $user_stats['avg_score'] ?? 0; ?>%</span>
              <span class="stat-label">Average Score</span>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
              <span class="stat-value"><?php echo ($user_stats['best_course'] ?? 'N/A'); ?></span>
              <span class="stat-label">Best Course</span>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
              <span class="stat-value">#<?php echo $user_stats['global_rank'] ?? 'N/A'; ?></span>
              <span class="stat-label">Global Rank</span>
            </div>
          </div>
        </div>
      </div>

      <div class="achievements-section">
        <h3><i class="fas fa-medal"></i> Achievements</h3>
        
        <div class="achievements-grid">
          <?php
          $achievements = [];
          
          if (isset($user_stats) && is_array($user_stats)) {
            $total_tests = $user_stats['total_tests'] ?? 0;
            $best_score = $user_stats['best_score'] ?? 0;
            $avg_score = $user_stats['avg_score'] ?? 0;
            
            if ($total_tests >= 1) {
              $achievements[] = ['icon' => 'fa-star', 'name' => 'First Test', 'desc' => 'Completed your first test'];
            }
            if ($total_tests >= 5) {
              $achievements[] = ['icon' => 'fa-fire', 'name' => 'On Fire', 'desc' => 'Completed 5 tests'];
            }
            if ($total_tests >= 10) {
              $achievements[] = ['icon' => 'fa-dragon', 'name' => 'Test Master', 'desc' => 'Completed 10 tests'];
            }
              
            if ($best_score >= 90) {
              $achievements[] = ['icon' => 'fa-brain', 'name' => 'Genius', 'desc' => 'Scored 90%+ on a test'];
            }
            if ($avg_score >= 80) {
              $achievements[] = ['icon' => 'fa-graduation-cap', 'name' => 'Scholar', 'desc' => 'Average score 80%+'];
            }
          }
          
          if (!empty($achievements)) {
            foreach ($achievements as $achievement): ?>
              <div class="achievement-card earned">
                <div class="achievement-header">
                  <i class="fas <?php echo $achievement['icon']; ?>"></i>
                  <h4><?php echo htmlspecialchars($achievement['name']); ?></h4>
                </div>
                <p><?php echo htmlspecialchars($achievement['desc']); ?></p>
              </div>
            <?php endforeach; 
          } else { ?>
            <div class="no-achievements">
              <i class="fas fa-medal"></i>
              <p>Complete tests to earn achievements!</p><br>
              <a href="/select-test" class="btn btn-primary btn-small">Start a Test</a>
            </div>
          <?php } ?>
        </div>
      </div>
            
      <div class="btn-group">
        <a href="/leaderboard" class="btn btn-primary"><i class="fas fa-trophy"></i> Leaderboard</a>
        <a href="/" class="btn btn-primary"><i class="fas fa-home"></i> Home</a>
      </div>
    </div>
  </div>

  <script>
    function toggleBioForm() {
      const bioForm = document.getElementById('bioForm');
      bioForm.style.display = bioForm.style.display === 'none' ? 'block' : 'none';
    }

    document.getElementById('profileImageInput').addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
        if (this.files[0].size > 5 * 1024 * 1024) {
          alert('Image size must be less than 5MB!');
          this.value = '';
          return;
        }
        
        if (!this.files[0].type.match('image.*')) {
          alert('Only image files are allowed!');
          this.value = '';
          return;
        }
        
        document.getElementById('imageUploadForm').submit();
      }
    });
  </script>
    
  <script src="/js/script.js"></script>
</body>
</html>