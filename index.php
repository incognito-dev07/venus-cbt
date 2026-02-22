<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Computer Based Test Platform</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="styles/core.css">
  <link rel="stylesheet" href="styles/component.css">
  <link rel="stylesheet" href="styles/pages.css">
  <link rel="stylesheet" href="styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="hero">
      <h1><i class="fas fa-graduation-cap"></i> Welcome to Venus CBT</h1>
      <span class="first-message">Practice, track progress, and earn achievements in a smarter learning community.</span>
      
      <div class="welcome-message" id="welcomeMessage">
        <p>Welcome back, <strong id="displayUsername">Guest</strong>!</p>
      </div>
            
      <div class="btn-group">
        <a href="/api/select-test.php" class="btn btn-primary"><i class="fas fa-pencil-alt"></i> Take a Test</a>
        <a href="/api/profile.php" class="btn btn-primary"><i class="fas fa-user"></i> My Profile</a>
      </div>
    </div>

    <div class="features">
      <h2 class="features-heading"><i class="fas fa-star"></i> Features</h2>
      <div class="feature">
        <i class="fas fa-clock"></i>
        <h3>Timed Practices</h3>
        <p>Complete tests within specified time limits</p>
      </div>
      <div class="feature">
        <i class="fas fa-chart-bar"></i>
        <h3>Instant Results</h3>
        <p>Get smart feedback and detailed performance analytics</p>
      </div>
      <div class="feature">
        <i class="fas fa-trophy"></i>
        <h3>Achievements</h3>
        <p>Earn rewards as you improve your skills</p>
      </div>
    </div>

    <div class="footer">
      <p><i class="far fa-copyright"></i> 2026 Incognito. All rights reserved</p>
    </div>
  </div>
    
  <script src="scripts/utilities.js"></script>
  <script src="scripts/storage.js"></script>
  <script src="scripts/profile.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const profile = StorageManager.getProfile();
      document.getElementById('displayUsername').textContent = profile.username;
    });
  </script>
</body>
</html>