<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$conversations = getConversations($user_id);
$selected_user = isset($_GET['user']) ? intval($_GET['user']) : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($selected_user && $selected_user != $user_id) {
  $messages = getMessages($user_id, $selected_user);
  $other_user = findUserById($selected_user);
} else {
  $messages = [];
  $other_user = null;
}

$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['to_id'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("Invalid security token");
    }
    
    $to_id = intval($_POST['to_id']);
    $message = trim($_POST['message']);
    
    if (!empty($message) && $to_id != $user_id) {
        if (strlen($message) > 1000) {
            $_SESSION['message_error'] = "Message too long!";
        } else {
            $sent = sendMessage($user_id, $to_id, $message);
            if (!$sent) {
                $_SESSION['message_error'] = "Rate limit exceeded. Please wait a moment.";
            }
        }
        header("Location: /messages?user=" . $to_id);
        exit();
    }
}

$friends = getFriends($user_id);
$search_results = !empty($search_query) ? searchUsers($search_query, $user_id) : [];
$message_error = $_SESSION['message_error'] ?? '';
unset($_SESSION['message_error']);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Messages</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  
  <?php if ($other_user): ?>
  <style>
    .icon-bar {
      display: none !important;
    }
    body {
      padding-top: 60px !important;
    }
  </style>
  <?php endif; ?>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <?php if ($other_user): ?>
    <!-- Full Screen Chat View -->
    <div class="chat-fullscreen">
      <div class="chat-header">
        <button class="btn btn-icon" onclick="window.location.href='/messages'">
          <i class="fas fa-arrow-left"></i>
        </button>
        <div class="chat-user" onclick="window.location.href='/view-profile?id=<?php echo $other_user['id']; ?>&from=messages'">
          <div class="chat-avatar">
            <?php if (isset($other_user['profile_image']) && $other_user['profile_image'] && imageExists($other_user['profile_image'])): ?>
              <img src="<?php echo htmlspecialchars($other_user['profile_image']); ?>" alt="">
            <?php else: ?>
              <i class="fas fa-user-circle"></i>
            <?php endif; ?>
          </div>
          <div class="chat-info">
            <span class="chat-name"><?php echo htmlspecialchars($other_user['username']); ?></span>
            <?php if (isFriend($user_id, $other_user['id'])): ?>
              <span class="friend-indicator">Friend</span>
            <?php endif; ?>
          </div>
        </div>
        <button class="btn btn-icon" onclick="window.location.href='/view-profile?id=<?php echo $other_user['id']; ?>&from=messages'">
          <i class="fas fa-user"></i>
        </button>
      </div>

      <?php if ($message_error): ?>
        <div class="error" style="margin: 1rem;">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message_error); ?>
        </div>
      <?php endif; ?>

      <div class="messages-area-full" id="chatMessages">
        <?php foreach ($messages as $msg): ?>
          <div class="message <?php echo $msg['from_id'] == $user_id ? 'sent' : 'received'; ?>">
            <div class="message-content">
              <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
            </div>
            <div class="message-time <?php echo $msg['from_id'] == $user_id ? 'sent-time' : 'received-time'; ?>">
              <?php echo date('H:i', strtotime($msg['created_at'])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="chat-input-area-full">
        <form method="POST" action="" id="messageForm">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
          <input type="hidden" name="to_id" value="<?php echo $selected_user; ?>">
          <input type="text" 
                 name="message" 
                 placeholder="Message..." 
                 autocomplete="off"
                 maxlength="1000"
                 required>
          <button type="submit" class="btn btn-primary btn-send">
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <!-- Messages List View -->
    <div class="container">
      <div class="messages-panel">
        <div class="panel-header">
          <h2><i class="fas fa-envelope"></i> Messages</h2>        
        </div>
        
        <?php if ($message_error): ?>
          <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message_error); ?>
          </div>
        <?php endif; ?>
        
        <div class="search-bar">
          <form method="GET" action="/messages" id="searchForm">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off" maxlength="50">
            <?php if (!empty($search_query)): ?>
              <a href="/messages" class="clear-search"><i class="fas fa-times"></i></a>
            <?php endif; ?>
          </form>
        </div>

        <?php if (!empty($search_query)): ?>
          <div class="search-results">
            <div class="results-header">
              <span class="results-count"><?php echo count($search_results); ?> users found</span>
            </div>
            
            <?php if (empty($search_results)): ?>
              <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <p>No users found matching "<?php echo htmlspecialchars($search_query); ?>"</p>
              </div>
            <?php else: ?>
              <div class="users-list">
                <?php foreach ($search_results as $result): ?>
                  <a href="/messages?user=<?php echo $result['id']; ?>" class="user-item">
                    <div class="user-avatar">
                      <?php if (isset($result['profile_image']) && $result['profile_image'] && imageExists($result['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($result['profile_image']); ?>" alt="">
                      <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                      <?php endif; ?>
                    </div>
                    <div class="user-details">
                      <span class="user-name"><?php echo htmlspecialchars($result['username']); ?></span>
                      <?php if ($result['is_friend']): ?>
                        <span class="friend-badge">Friend</span>
                      <?php elseif ($result['is_following']): ?>
                        <span class="following-badge">Following</span>
                      <?php endif; ?>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php if (!empty($friends)): ?>
            <div class="section">
              <div class="section-header">
                <h3><i class="fas fa-user-friends"></i> Friends</h3>
                <span class="section-count"><?php echo count($friends); ?></span>
              </div>
              <div class="horizontal-scroll">
                <?php foreach ($friends as $friend): ?>
                  <a href="/messages?user=<?php echo $friend['id']; ?>" class="friend-chip">
                    <div class="friend-avatar">
                      <?php if (isset($friend['profile_image']) && $friend['profile_image'] && imageExists($friend['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($friend['profile_image']); ?>" alt="">
                      <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                      <?php endif; ?>
                    </div>
                    <span class="friend-name"><?php echo htmlspecialchars($friend['username']); ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="section">
            <div class="section-header">
              <h3><i class="fas fa-history"></i> Recent Chats</h3>
            </div>
            
            <?php if (empty($conversations)): ?>
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No conversations yet</p>
              </div>
            <?php else: ?>
              <div class="conversations-list">
                <?php foreach ($conversations as $conv): ?>
                  <a href="?user=<?php echo $conv['user_id']; ?>" class="conversation-item">
                    <div class="conv-avatar">
                      <?php 
                      $conv_user = findUserById($conv['user_id']);
                      if ($conv_user && isset($conv_user['profile_image']) && $conv_user['profile_image'] && imageExists($conv_user['profile_image'])): 
                      ?>
                        <img src="<?php echo htmlspecialchars($conv_user['profile_image']); ?>" alt="">
                      <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                      <?php endif; ?>
                      <?php if ($conv['unread'] > 0): ?>
                        <span class="unread-dot"></span>
                      <?php endif; ?>
                    </div>
                    <div class="conv-details">
                      <div class="conv-top">
                        <span class="conv-name"><?php echo htmlspecialchars($conv['username']); ?></span>
                        <span class="conv-time"><?php echo date('H:i', strtotime($conv['timestamp'])); ?></span>
                      </div>
                      <div class="conv-preview">
                        <?php echo htmlspecialchars(substr($conv['last_message'], 0, 40)); ?>
                        <?php if (strlen($conv['last_message']) > 40): ?>...<?php endif; ?>
                      </div>
                    </div>
                    <?php if ($conv['unread'] > 0): ?>
                      <span class="unread-badge"><?php echo $conv['unread']; ?></span>
                    <?php endif; ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <script>
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    <?php if ($selected_user): ?>
    setInterval(() => {
      location.reload();
    }, 30000);
    <?php endif; ?>

    const messageInput = document.querySelector('.chat-input-area-full input');
    if (messageInput) {
      messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          document.getElementById('messageForm').submit();
        }
      });
    }
  </script>

  <script src="/js/script.js"></script>
</body>
</html>