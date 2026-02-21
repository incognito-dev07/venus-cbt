<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user = findUserById($_SESSION['user_id']);
$message = '';
$error = '';

$csrf_token = generateCSRFToken();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_username'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token!";
    } else {
        $new_username = trim($_POST['new_username']);
        
        if (empty($new_username)) {
            $error = "Username cannot be empty!";
        } elseif (strlen($new_username) < 3) {
            $error = "Username must be at least 3 characters!";
        } elseif (strlen($new_username) > 50) {
            $error = "Username too long!";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $error = "Username can only contain letters, numbers, and underscores!";
        } else {
            $db = getDB();
            $check = $db->prepare('SELECT id FROM users WHERE username = :username AND id != :id');
            $check->bindValue(':username', $new_username, SQLITE3_TEXT);
            $check->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $existing = $check->execute()->fetchArray();
            
            if ($existing) {
                $error = "Username already taken!";
            } else {
                $update = $db->prepare('UPDATE users SET username = :username WHERE id = :id');
                $update->bindValue(':username', $new_username, SQLITE3_TEXT);
                $update->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                
                if ($update->execute()) {
                    $_SESSION['username'] = $new_username;
                    $message = "Username updated successfully!";
                    $user = findUserById($_SESSION['user_id']);
                } else {
                    $error = "Failed to update username!";
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['privacy'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token!";
    } else {
        $hide_avg = isset($_POST['hide_avg']) ? true : false;
        
        $db = getDB();
        $privacy = json_encode(['hide_avg_leaderboard' => $hide_avg]);
        
        $update = $db->prepare('UPDATE users SET privacy_settings = :privacy WHERE id = :id');
        $update->bindValue(':privacy', $privacy, SQLITE3_TEXT);
        $update->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $update->execute();
        
        $message = "Privacy settings updated!";
        $user = findUserById($_SESSION['user_id']);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['theme'])) {
    $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('theme', $theme, [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
    $message = "Theme preference saved!";
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'theme' => $theme]);
        exit();
    }
}

$current_theme = $_COOKIE['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="<?php echo $current_theme === 'light' ? 'light-mode' : ''; ?>">
  <?php include 'navbar.php'; ?>
  
  <div class="container">
    <div class="settings-panel">
      <h2><i class="fas fa-cog"></i> Settings</h2>
      
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
      
      <div class="settings-section">
        <h3 style="margin-bottom: 1rem"><i class="fas fa-user-edit"></i>Change Username</h3>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
          <div class="form-group">
            <label>Current:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
          </div>
          <div class="form-group">
            <label>Update:</label>
            <input type="text" name="new_username" placeholder="Enter new username" required maxlength="50" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
          </div>
          <button type="submit" name="change_username" class="btn btn-primary">
            <i class="fas fa-save"></i> Update Username
          </button>
        </form>
      </div>
      
      <div class="settings-section">
        <h3><i class="fas fa-lock"></i> Privacy Settings</h3>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
          <div class="privacy-option">
            <input type="checkbox" name="hide_avg" id="hide_avg" <?php echo (isset($user['privacy']['hide_avg_leaderboard']) && $user['privacy']['hide_avg_leaderboard']) ? 'checked' : ''; ?>>
            <label for="hide_avg">
              <i class="fas fa-eye-slash"></i> Hide my average score from leaderboard
            </label>
          </div>
          <button type="submit" name="privacy" class="btn btn-primary" style="margin-top: 1rem;">
            <i class="fas fa-save"></i> Save Privacy Settings
          </button>
        </form>
      </div>
      
      <div class="settings-section">
        <h3><i class="fas fa-palette"></i> Theme Preference</h3>
        <div class="theme-toggle">
          <span class="theme-label"><i class="fas fa-moon"></i> Night mode:</span>
          <label class="switch">
            <input type="checkbox" id="themeSwitch" <?php echo $current_theme === 'light' ? 'checked' : ''; ?>>
            <span class="slider round"></span>
          </label>
          <div class="theme-status">
            <span class="theme-on <?php echo $current_theme === 'dark' ? 'active' : ''; ?>">On</span>
            <span class="theme-separator">/</span>
            <span class="theme-off <?php echo $current_theme === 'light' ? 'active' : ''; ?>">Off</span>
          </div>
        </div>
      </div>

      <div class="btn-group">
        <a href="/profile" class="btn btn-primary"><i class="fas fa-user"></i> View Profile</a>
        <a href="/" class="btn btn-primary"><i class="fas fa-home"></i> Back to Home</a>
      </div>
    </div>
  </div>
  
  <script src="/js/script.js"></script>
  <script>
    function getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(';').shift();
    }

    (function() {
      const theme = getCookie('theme') || 'dark';
      if (theme === 'light') {
        document.body.classList.add('light-mode');
        document.querySelector('.theme-on')?.classList.remove('active');
        document.querySelector('.theme-off')?.classList.add('active');
      } else {
        document.body.classList.remove('light-mode');
        document.querySelector('.theme-on')?.classList.add('active');
        document.querySelector('.theme-off')?.classList.remove('active');
      }
    })();

    document.getElementById('themeSwitch')?.addEventListener('change', function(e) {
      const isLightMode = this.checked;
      const theme = isLightMode ? 'light' : 'dark';
    
      document.querySelector('.theme-on').classList.toggle('active', !isLightMode);
      document.querySelector('.theme-off').classList.toggle('active', isLightMode);
    
      if (isLightMode) {
        document.body.classList.add('light-mode');
      } else {
        document.body.classList.remove('light-mode');
      }
    
      const date = new Date();
      date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
      const expires = date.toUTCString();
      
      document.cookie = `theme=${theme}; path=/; expires=${expires}; SameSite=Lax`;
    
      const formData = new FormData();
      formData.append('theme', theme);
    
      fetch('/settings', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('Theme updated to:', data.theme);
        }
      })
      .catch(error => console.error('Error:', error));
    });
  </script>
</body>
</html>