<?php
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
    /* Space for offline banner */
    body.has-offline-banner {
      padding-bottom: 50px;
    }
  </style>
</head>
<body class="<?php echo $theme === 'light' ? 'light-mode' : ''; ?>">
<div class="navbar">
  <div class="nav-container">
    <div class="logo" onclick="window.location.href='index.php'" style="cursor: pointer;">
      <i class="fas fa-graduation-cap"></i> Venus CBT
    </div>
    
    <div class="nav-links">
      <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-user"></i> Profile
      </a>
      <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Settings
      </a>
      <a href="#" onclick="return logout()" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Exit
      </a>
    </div>
    
    <div class="hamburger" onclick="toggleMenu()">
      <span></span>
      <span></span>
      <span></span>
    </div>
    
    <div class="menu-dropdown" id="menuDropdown">
      <a href="profile.php"><i class="fas fa-user"></i> User Profile</a>
      <a href="settings.php"><i class="fas fa-cog"></i> App Settings</a>
      <a href="#" onclick="return logout()" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Exit
      </a>
    </div>
  </div>
</div>

<div class="icon-bar">
  <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>" title="Home">
    <i class="fas fa-home"></i>
  </a>
  <a href="study.php" class="<?php echo $current_page == 'study.php' ? 'active' : ''; ?>" title="Study">
    <i class="fas fa-book-open"></i>
  </a>
  <a href="select-test.php" class="<?php echo $current_page == 'select-test.php' ? 'active' : ''; ?>" title="Practice">
    <i class="fas fa-pencil-alt"></i>
  </a>
  <a href="history.php" class="<?php echo $current_page == 'history.php' ? 'active' : ''; ?>" title="History">
    <i class="fas fa-history"></i>
  </a>
  <a href="notifications.php" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>" title="Notifications" id="notificationIcon">
    <i class="fas fa-bell"></i>
    <span class="badge" id="notificationBadge" style="display: none;">0</span>
  </a>
</div>

<!-- Offline Warning - Sticky to bottom -->
<div id="offlineWarning" class="offline-warning" style="display: none;">
  <i class="fas fa-wifi-slash"></i>
  <span>You're offline - Check your connection</span>
</div>

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

function logout() {
  if (confirm('Exit application? Your data will remain saved.')) {
    window.location.href = 'index.php';
  }
  return false;
}

function toggleMenu() {
  const menu = document.getElementById('menuDropdown');
  const hamburger = document.querySelector('.hamburger');
  if (menu && hamburger) {
    menu.classList.toggle('show');
    hamburger.classList.toggle('active');
  }
}

document.addEventListener('click', function(event) {
  const menu = document.getElementById('menuDropdown');
  const hamburger = document.querySelector('.hamburger');
  if (menu && hamburger && !menu.contains(event.target) && !hamburger.contains(event.target)) {
    menu.classList.remove('show');
    hamburger.classList.remove('active');
  }
});

// ========== Online/Offline Detection ==========
const offlineWarning = document.getElementById('offlineWarning');

function updateOnlineStatus() {
  if (!navigator.onLine) {
    offlineWarning.style.display = 'flex';
    document.body.classList.add('has-offline-banner');
  } else {
    offlineWarning.style.display = 'none';
    document.body.classList.remove('has-offline-banner');
  }
}

window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

// ========== Update Notification Badge ==========
function updateNotificationBadge() {
  if (typeof StorageManager !== 'undefined' && StorageManager.updateNotificationBadge) {
    StorageManager.updateNotificationBadge();
  }
}

// Check every 30 seconds
setInterval(updateNotificationBadge, 30000);
</script>

<!-- Offline Warning Styles -->
<style>
.offline-warning {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--accent-danger);
  color: white;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.9rem;
  z-index: 9998;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
}

.offline-warning i {
  font-size: 1rem;
}

/* Safe area for notched phones */
@supports (padding: max(0px)) {
  .offline-warning {
    padding-bottom: max(12px, env(safe-area-inset-bottom));
  }
}
</style>