<?php
$unread_count = 0;
$unread_notifications = 0;
if (isset($_SESSION['user_id'])) {
    $unread_count = getUnreadCount($_SESSION['user_id']);
    $unread_notifications = getUnreadNotificationCount($_SESSION['user_id']);
}
$current_page = basename($_SERVER['PHP_SELF']);
$theme = $_COOKIE['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html>
<head>
  <style>
    body {
      background: var(--bg-primary);
      transition: background-color 0.3s, color 0.3s;
    }
  </style>
</head>
<body class="<?php echo $theme === 'light' ? 'light-mode' : ''; ?>">
<div class="navbar">
  <div class="nav-container">
    <div class="logo" onclick="window.location.href='/'" style="cursor: pointer;">
      <i class="fas fa-graduation-cap"></i> Venus CBT
    </div>
    
    <div class="nav-links">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/profile" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
          <i class="fas fa-user"></i> Profile
        </a>
        <a href="/history" class="<?php echo $current_page == 'history.php' ? 'active' : ''; ?>">
          <i class="fas fa-history"></i> History
        </a>
        <a href="/settings" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
          <i class="fas fa-cog"></i> Settings
        </a>
        <a href="#" onclick="return logout()" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      <?php else: ?>
        <a href="/register" class="<?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
          <i class="fas fa-user-plus"></i> Register
        </a>
      <?php endif; ?>
    </div>
    
    <div class="hamburger" onclick="toggleMenu()">
      <span></span>
      <span></span>
      <span></span>
    </div>
    
    <div class="menu-dropdown" id="menuDropdown">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/profile"><i class="fas fa-user"></i> User Profile</a>
        <a href="/history"><i class="fas fa-history"></i> Test History</a>
        <a href="/settings"><i class="fas fa-cog"></i> App Settings</a>
        <a href="#" onclick="return logout()" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      <?php else: ?>
        <a href="/"><i class="fas fa-home"></i> Home</a>
        <a href="/register"><i class="fas fa-user-plus"></i> Register</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (isset($_SESSION['user_id'])): ?>
<div class="icon-bar">
  <a href="/" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>" title="Home">
    <i class="fas fa-home"></i>
  </a>
  <a href="/select-test" class="<?php echo $current_page == 'select-test.php' ? 'active' : ''; ?>" title="Practice">
    <i class="fas fa-pencil-alt"></i>
  </a>
  <a href="/leaderboard" class="<?php echo $current_page == 'leaderboard.php' ? 'active' : ''; ?>" title="Leaderboard">
    <i class="fas fa-trophy"></i>
  </a>
  <a href="/messages" class="<?php echo $current_page == 'messages.php' ? 'active' : ''; ?>" title="Messages">
    <i class="fas fa-envelope"></i>
    <?php if ($unread_count > 0): ?>
      <span class="badge"><?php echo $unread_count; ?></span>
    <?php endif; ?>
  </a>
  <a href="/notifications" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>" title="Notifications">
    <i class="fas fa-bell"></i>
    <?php if ($unread_notifications > 0): ?>
      <span class="badge"><?php echo $unread_notifications; ?></span>
    <?php endif; ?>
  </a>
</div>
<?php endif; ?>

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
  } else {
    document.body.classList.remove('light-mode');
  }
})();
</script>