<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - History</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/public/styles/core.css">
  <link rel="stylesheet" href="/public/styles/component.css">
  <link rel="stylesheet" href="/public/styles/pages.css">
  <link rel="stylesheet" href="/public/styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="history-panel">
      <div id="messageContainer"></div>
      
      <div class="history-header">
        <h2><i class="fas fa-history"></i> Test History</h2>
        <button class="btn btn-danger btn-small" onclick="HistoryManager.clearHistory()" id="clearHistoryBtn" style="display: none;">
          <i class="fas fa-trash-alt"></i> Clear History
        </button>
      </div>

      <div id="historyView">
        <!-- History list view -->
        <div id="testsListContainer"></div>
        
        <!-- Test details view (hidden by default) -->
        <div id="testDetailsContainer" style="display: none;">
          <button class="back-btn" onclick="HistoryManager.showList()">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <div id="testDetailsContent"></div>
        </div>
      </div>

      <div id="emptyState" class="empty-state" style="display: none;">
        <i class="fas fa-history"></i>
        <p>No test history yet</p><br>
        <a href="select-test.php" class="btn btn-primary">Take a Test</a>
      </div>
    </div>
  </div>

  <script src="/public/scripts/utilities.js"></script>
  <script src="/public/scripts/storage.js"></script>
  <script src="/public/scripts/history.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      HistoryManager.init();
    });
  </script>
</body>
</html>