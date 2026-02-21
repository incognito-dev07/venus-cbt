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

<!-- PWA Install Button - Shows when app is installable -->
<div id="pwaInstallContainer" style="display: none;">
  <div class="pwa-install-banner">
    <div class="pwa-install-content">
      <i class="fas fa-download"></i>
      <span>Install Venus CBT for offline access</span>
    </div>
    <div class="pwa-install-actions">
      <button class="btn btn-small btn-primary" id="pwaInstallBtn">Install</button>
      <button class="btn-icon" id="pwaCloseBtn"><i class="fas fa-times"></i></button>
    </div>
  </div>
</div>

<!-- Offline Warning - Sticky to bottom -->
<div id="offlineWarning" class="offline-warning" style="display: none;">
  <i class="fas fa-wifi-slash"></i>
  <span>You're offline - Using cached content</span>
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

// ========== PWA Installation ==========
let deferredPrompt;
const pwaInstallContainer = document.getElementById('pwaInstallContainer');
const pwaInstallBtn = document.getElementById('pwaInstallBtn');
const pwaCloseBtn = document.getElementById('pwaCloseBtn');

// Check if app is already installed
window.addEventListener('appinstalled', () => {
  console.log('PWA was installed');
  pwaInstallContainer.style.display = 'none';
});

// Before install prompt
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  
  // Don't show if already installed or if we've hidden it before
  if (!localStorage.getItem('pwa_install_dismissed')) {
    pwaInstallContainer.style.display = 'block';
  }
});

// Install button click
pwaInstallBtn?.addEventListener('click', async () => {
  if (!deferredPrompt) return;
  
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  console.log(`Install outcome: ${outcome}`);
  
  deferredPrompt = null;
  pwaInstallContainer.style.display = 'none';
  
  if (outcome === 'accepted') {
    localStorage.removeItem('pwa_install_dismissed');
  }
});

// Close/dismiss install banner
pwaCloseBtn?.addEventListener('click', () => {
  pwaInstallContainer.style.display = 'none';
  localStorage.setItem('pwa_install_dismissed', 'true');
  // Expire after 7 days
  setTimeout(() => {
    localStorage.removeItem('pwa_install_dismissed');
  }, 7 * 24 * 60 * 60 * 1000);
});

// ========== Service Worker Registration ==========
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js')
      .then(registration => {
        console.log('ServiceWorker registered:', registration.scope);
        
        // Check for updates
        registration.update();
        
        // Set up periodic sync if supported
        if ('periodicSync' in registration) {
          // Request periodic sync for content updates
        }
      })
      .catch(error => {
        console.log('ServiceWorker registration failed:', error);
      });
  });
}

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

// Check initial status
updateOnlineStatus();

// ========== PWA Mode Detection ==========
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('Running as installed PWA');
  document.body.classList.add('pwa-mode');
  
  // Hide install banner if in PWA mode
  pwaInstallContainer.style.display = 'none';
}

// ========== Update Notification Badge ==========
function updateNotificationBadge() {
  if (typeof StorageManager !== 'undefined') {
    StorageManager.updateNotificationBadge();
  }
}

// Check every 30 seconds
setInterval(updateNotificationBadge, 30000);
</script>

<!-- Add PWA styles -->
<style>
/* PWA Install Banner */
.pwa-install-banner {
  position: fixed;
  bottom: 70px;
  left: 10px;
  right: 10px;
  background: var(--accent-primary);
  color: white;
  padding: 12px 16px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  z-index: 9999;
  box-shadow: 0 4px 15px rgba(157, 78, 221, 0.3);
  animation: slideUp 0.3s ease;
}

.pwa-install-content {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
}

.pwa-install-content i {
  font-size: 1.2rem;
}

.pwa-install-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pwa-install-actions .btn {
  background: white;
  color: var(--accent-primary);
  font-weight: 600;
  padding: 6px 12px;
  font-size: 0.8rem;
}

.pwa-install-actions .btn-icon {
  background: rgba(255,255,255,0.2);
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 16px;
}

/* Offline Warning - Sticky Bottom */
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

/* Animation */
@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .pwa-install-banner {
    bottom: 60px;
    left: 8px;
    right: 8px;
    padding: 10px 12px;
  }
  
  .pwa-install-content {
    font-size: 0.8rem;
  }
}

/* Safe area for notched phones */
@supports (padding: max(0px)) {
  .offline-warning {
    padding-bottom: max(12px, env(safe-area-inset-bottom));
  }
  
  .pwa-install-banner {
    bottom: max(70px, calc(env(safe-area-inset-bottom) + 60px));
  }
}
</style>