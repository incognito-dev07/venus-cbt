<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Notifications</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="styles/core.css">
  <link rel="stylesheet" href="styles/component.css">
  <link rel="stylesheet" href="styles/pages.css">
  <link rel="stylesheet" href="styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="notifications-panel">
      <div class="notifications-header">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
        <div>
          <button class="btn btn-small btn-primary" onclick="NotificationManager.markAllAsRead()" id="markAllReadBtn" style="display: none;">
            <i class="fas fa-check"></i> Mark All Read
          </button>
          <button class="btn btn-small btn-danger" onclick="NotificationManager.clearAll()" id="clearAllBtn" style="display: none; margin-left: 0.5rem;">
            <i class="fas fa-trash-alt"></i> Clear All
          </button>
        </div>
      </div>

      <div class="notifications-list" id="notificationsList">
        <!-- Notifications will be loaded here -->
      </div>

      <div id="emptyNotifications" class="empty-state" style="display: none;">
        <i class="fas fa-bell-slash"></i>
        <p>No notifications yet</p>
      </div>
    </div>
  </div>

  <script src="scripts/utilities.js"></script>
  <script src="scripts/storage.js"></script>
  <script src="scripts/notifications.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      NotificationManager.init();
    });
  </script>
</body>
</html>