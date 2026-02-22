<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Test Results</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/public/styles/core.css">
  <link rel="stylesheet" href="/public/styles/component.css">
  <link rel="stylesheet" href="/public/styles/pages.css">
  <link rel="stylesheet" href="/public/styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="result-card" id="resultContainer">
      <!-- Results will be populated by JavaScript -->
    </div>
  </div>

  <script src="/public/scripts/utilities.js"></script>
  <script src="/public/scripts/storage.js"></script>
  <script src="/public/scripts/notifications.js"></script>
  <script src="/public/scripts/test.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const savedTest = localStorage.getItem('venus_current_result');
      if (savedTest) {
        const result = JSON.parse(savedTest);
        TestManager.displayResults(result);
        localStorage.removeItem('venus_current_result');
      } else {
        window.location.href = 'select-test.php';
      }
    });
  </script>
</body>
</html>