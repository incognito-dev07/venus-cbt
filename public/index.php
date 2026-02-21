<?php
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Computer Based Test Platform</title>
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
    <div class="hero">
      <h1><i class="fas fa-graduation-cap"></i> Welcome to Venus CBT</h1>
      <span class="first-message">Connect, compete, and grow with friends in a smarter learning community.</span>
      
      <?php if (isLoggedIn()): ?>
        <?php $user = findUserById($_SESSION['user_id']); ?>
        <div class="welcome-message">
          <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
        </div>
      <?php endif; ?>
            
      <div class="btn-group">
        <?php if (isLoggedIn()): ?>
          <a href="/select-test" class="btn btn-primary"><i class="fas fa-pencil-alt"></i> Take a Test</a>
          <a href="/profile" class="btn btn-primary"><i class="fas fa-user"></i> My Profile</a>
        <?php else: ?>
          <a href="/login" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
          <a href="/register" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Account</a>
        <?php endif; ?>
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
        <h3>Leaderboards</h3>
        <p>Compete with others and track your ranking</p>
      </div>
    </div>

    <div class="footer">
      <p><i class="far fa-copyright"></i> 2026 Incognito. All rights reserved</p>
    </div>
  </div>
    
  <script src="/js/script.js"></script>
</body>
</html>