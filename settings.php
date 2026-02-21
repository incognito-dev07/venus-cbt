<?php
require_once 'config.php';
$current_theme = $_COOKIE['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="styles/core.css">
  <link rel="stylesheet" href="styles/component.css">
  <link rel="stylesheet" href="styles/pages.css">
  <link rel="stylesheet" href="styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="<?php echo $current_theme === 'light' ? 'light-mode' : ''; ?>">
  <?php include 'navbar.php'; ?>
  
  <div class="container">
    <div class="settings-panel">
      <h2><i class="fas fa-cog"></i> Settings</h2>
      
      <div id="messageContainer"></div>
      
      <div class="settings-section">
        <h3 style="margin-bottom: 1rem"><i class="fas fa-user-edit"></i>Change Username</h3>
        <div class="form-group">
          <label>Current:</label>
          <input type="text" id="currentUsername" value="" disabled>
        </div>
        <div class="form-group">
          <label>Update:</label>
          <input type="text" id="newUsername" placeholder="Enter new username" maxlength="50" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
        </div>
        <button type="button" class="btn btn-primary" onclick="SettingsManager.updateUsername()">
          <i class="fas fa-save"></i> Update Username
        </button>
      </div>
      
      <div class="settings-section">
        <h3><i class="fas fa-palette"></i> Theme Preference</h3>
        <div class="theme-toggle">
          <span class="theme-label"><i class="fas fa-moon"></i> Dark mode:</span>
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

      <div class="settings-section">
        <h3><i class="fas fa-database"></i> Data Management</h3>
        <button type="button" class="btn btn-danger" onclick="SettingsManager.clearAllData()">
          <i class="fas fa-trash-alt"></i> Clear All Data
        </button>
      </div>

      <div class="btn-group">
        <a href="profile.php" class="btn btn-primary"><i class="fas fa-user"></i> View Profile</a>
        <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> Back to Home</a>
      </div>
    </div>
  </div>
  
  <script src="scripts/utilities.js"></script>
  <script src="scripts/storage.js"></script>
  <script src="scripts/settings.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      SettingsManager.init();
    });

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
      
      SettingsManager.saveTheme(theme);
    });
  </script>
</body>
</html>