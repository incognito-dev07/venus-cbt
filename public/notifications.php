<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

if (isset($_GET['action']) && $_GET['action'] == 'mark_read' && isset($_GET['id'])) {
    markNotificationAsRead($_GET['id']);
    header("Location: /notifications");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'mark_all_read') {
    markAllNotificationsAsRead($user_id);
    header("Location: /notifications");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'clear_all') {
    clearAllNotifications($user_id);
    header("Location: /notifications?cleared=1");
    exit();
}

$notifications = getNotifications($user_id);
$cleared = isset($_GET['cleared']);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Notifications</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="notifications-panel">
      <?php if ($cleared): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> All notifications cleared!
        </div>
      <?php endif; ?>
      
      <?php if (!empty($notifications)): ?>
        <a href="?action=clear_all" class="clear-btn" onclick="return confirm('Clear all notifications?')" title="Clear All Notifications">
          <i class="fas fa-trash-alt"></i>
        </a>
      <?php endif; ?>
      
      <div class="notifications-header">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
        <?php if (!empty($notifications)): ?>
          <a href="?action=mark_all_read" class="btn btn-small btn-primary">
            <i class="fas fa-check"></i> Mark All Read
          </a>
        <?php endif; ?>
      </div>

      <div class="notifications-list">
        <?php if (empty($notifications)): ?>
          <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <p>No notifications yet</p>
          </div>
        <?php else: ?>
          <?php foreach ($notifications as $notif): ?>
            <?php 
            $link = '#';
            if ($notif['type'] == 'follow' && isset($notif['data']['follower_id'])) {
                $link = '/view-profile?id=' . $notif['data']['follower_id'] . '&from=notifications';
            } elseif ($notif['type'] == 'friend' && isset($notif['data']['user_id'])) {
                $link = '/view-profile?id=' . $notif['data']['user_id'] . '&from=notifications';
            }
            ?>
            <a href="<?php echo $link; ?>" class="notification-item <?php echo $notif['read'] ? 'read' : 'unread'; ?>">
              <div class="notification-icon">
                <?php if ($notif['type'] == 'follow'): ?>
                  <i class="fas fa-user-plus"></i>
                <?php elseif ($notif['type'] == 'friend'): ?>
                  <i class="fas fa-handshake"></i>
                <?php else: ?>
                  <i class="fas fa-bell"></i>
                <?php endif; ?>
              </div>
              <div class="notification-content">
                <?php if ($notif['type'] == 'follow'): ?>
                  <p><strong><?php echo htmlspecialchars($notif['data']['follower_name']); ?></strong> started following you</p>
                <?php elseif ($notif['type'] == 'friend'): ?>
                  <p>You and <strong><?php echo htmlspecialchars($notif['data']['user_name']); ?></strong> are now friends!</p>
                <?php endif; ?>
                <span class="notification-time">
                  <?php echo date('M j, H:i', strtotime($notif['created_at'])); ?>
                </span>
              </div>
              <?php if (!$notif['read']): ?>
                <div class="mark-read" onclick="event.stopPropagation(); window.location.href='?action=mark_read&id=<?php echo $notif['id']; ?>'">
                  <i class="fas fa-circle"></i>
                </div>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.mark-read').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.location.href = this.getAttribute('onclick')?.replace('event.stopPropagation(); ', '') || this.href;
      });
    });
  </script>

  <script src="/js/script.js"></script>
</body>
</html>