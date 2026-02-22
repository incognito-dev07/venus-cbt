<?php
require_once 'config.php';

if (!isset($_GET['course'])) {
  header("Location: select-test.php");
  exit();
}

$course_id = $_GET['course'];
$course = getCourse($course_id);

if (!$course) {
  header("Location: select-test.php");
  exit();
}

$all_questions = getQuestions($course_id);
shuffle($all_questions);
$questions = array_slice($all_questions, 0, 20);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Taking Test: <?php echo $course['id']; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/public/styles/core.css">
  <link rel="stylesheet" href="/public/styles/component.css">
  <link rel="stylesheet" href="/public/styles/pages.css">
  <link rel="stylesheet" href="/public/styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
  
  <style>
    .icon-bar {
      display: none !important;
    }
    body {
      padding-top: 60px !important;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="test-container">
    <div class="timer-bar">
      <button class="exit-test-btn" id="exitTestBtn">
        <i class="fas fa-arrow-left"></i>
      </button>
      <div class="timer-info">
        <i class="fas fa-clock"></i>
        <span id="timer"><?php echo gmdate("i:s", $course['time_limit']); ?></span>
      </div>
    </div>

    <div class="test-main">
      <div class="question-area">
        <div class="question-card">
          <div class="question-header">
            <span class="question-number" id="questionNumber">1.</span>
            <h3 id="questionText">Loading...</h3>
          </div>
          
          <div class="options-grid" id="optionsContainer"></div>

          <div class="navigation-buttons">
            <button class="nav-btn prev-btn" id="prevBtn" disabled>
              <i class="fas fa-arrow-left"></i>
              <span>Previous</span>
            </button>
            
            <button class="nav-btn next-btn" id="nextBtn">
              <span>Next</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <div class="flag-container">
            <button class="flag-btn" id="flagBtn">
              <i class="fas fa-flag"></i>
              <span>Flag for Review</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="question-navigator-bottom">
      <div class="navigator-header">
        <h4>Question Navigator</h4>
        <div class="legend-compact">
          <span class="legend-item"><span class="dot current"></span> Current</span>
          <span class="legend-item"><span class="dot answered"></span> Answered</span>
          <span class="legend-item"><span class="dot flagged"></span> Flagged</span>
          <span class="legend-item"><span class="dot"></span> Unanswered</span>
        </div>
      </div>
      <div class="navigator-grid-bottom" id="navigatorGrid"></div>
    </div>
  </div>

  <script>
    const testData = {
      course: <?php echo json_encode($course); ?>,
      questions: <?php echo json_encode($questions); ?>,
      timeLimit: <?php echo $course['time_limit']; ?>
    };
  </script>
  <script src="/public/scripts/utilities.js"></script>
  <script src="/public/scripts/storage.js"></script>
  <script src="/public/scripts/test.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      TestManager.init(testData);
    });
  </script>
</body>
</html>