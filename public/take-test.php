<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if (!isset($_GET['course'])) {
  header("Location: /select-test");
  exit();
}

$course_id = $_GET['course'];
$course = getCourse($course_id);

if (!$course) {
  header("Location: /select-test");
  exit();
}

$all_questions = getQuestions($course_id);
shuffle($all_questions);
$questions = array_slice($all_questions, 0, 20);

if (!isset($_SESSION['test'])) {
  $_SESSION['test'] = [
    'course_id' => $course_id,
    'questions' => $questions,
    'answers' => array_fill(0, count($questions), null),
    'flagged' => array_fill(0, count($questions), false),
    'current' => 0,
    'start_time' => time(),
    'time_limit' => $course['time_limit']
  ];
}

$test = $_SESSION['test'];
$total_questions = count($test['questions']);
$current = $test['current'];
$question = $test['questions'][$current];
$time_remaining = $test['time_limit'] - (time() - $test['start_time']);

if ($time_remaining <= 0) {
  header("Location: /submit-test");
  exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Taking Test: <?php echo $course['id']; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
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
      <button class="exit-test-btn" onclick="confirmExit()">
        <i class="fas fa-arrow-left"></i> Exit
      </button>
      <div class="timer-info">
        <i class="fas fa-clock"></i>
        <span id="timer"><?php echo gmdate("i:s", $time_remaining); ?></span>
      </div>
    </div>

    <div class="test-main">
      <div class="question-area">
        <div class="question-card">
          <div class="question-header">
            <span class="question-number"><?php echo $current + 1; ?>.</span>
            <h3><?php echo htmlspecialchars($question['question']); ?></h3>
          </div>
          
          <div class="options-grid">
            <?php foreach ($question['options'] as $index => $option): ?>
              <div class="option-card <?php echo $test['answers'][$current] === $index ? 'selected' : ''; ?>" 
                   onclick="selectOption(<?php echo $index; ?>)">
                <div class="option-letter"><?php echo chr(65 + $index); ?></div>
                <div class="option-text"><?php echo htmlspecialchars($option); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="navigation-buttons">
            <button class="nav-btn prev-btn" onclick="navigate(-1)" <?php echo $current == 0 ? 'disabled' : ''; ?>>
              <i class="fas fa-arrow-left"></i>
              <span>Previous</span>
            </button>
            
            <?php if ($current < $total_questions - 1): ?>
              <button class="nav-btn next-btn" onclick="navigate(1)">
                <span>Next</span>
                <i class="fas fa-arrow-right"></i>
              </button>
            <?php else: ?>
              <button class="nav-btn submit-btn" onclick="submitTest()">
                <span>Submit</span>
                <i class="fas fa-check"></i>
              </button>
            <?php endif; ?>
          </div>

          <div class="flag-container">
            <button class="flag-btn <?php echo $test['flagged'][$current] ? 'flagged' : ''; ?>" onclick="flagQuestion()">
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
      <div class="navigator-grid-bottom">
        <?php for ($i = 0; $i < $total_questions; $i++): 
          $status = '';
          if ($test['answers'][$i] !== null) $status = 'answered';
          if ($test['flagged'][$i]) $status = 'flagged';
          if ($i == $current) $status = 'current';
        ?>
          <div class="nav-item-bottom <?php echo $status; ?>" onclick="goToQuestion(<?php echo $i; ?>)">
            <?php echo $i + 1; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <script>
    let timeRemaining = <?php echo $time_remaining; ?>;
    let timerInterval = setInterval(updateTimer, 1000);

    function updateTimer() {
      timeRemaining--;
      if (timeRemaining <= 0) {
        clearInterval(timerInterval);
        window.location.href = '/submit-test';
        return;
      }
      
      const minutes = Math.floor(timeRemaining / 60);
      const seconds = timeRemaining % 60;
      document.getElementById('timer').textContent = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function selectOption(optionIndex) {
      fetch('/api/test-actions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=answer&question=<?php echo $current; ?>&option=' + optionIndex
      }).then(() => {
        location.reload();
      });
    }

    function flagQuestion() {
      fetch('/api/test-actions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=flag&question=<?php echo $current; ?>'
      }).then(() => {
        location.reload();
      });
    }

    function navigate(direction) {
      fetch('/api/test-actions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=navigate&direction=' + direction
      }).then(() => {
        location.reload();
      });
    }

    function goToQuestion(index) {
      fetch('/api/test-actions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=goTo&index=' + index
      }).then(() => {
        location.reload();
      });
    }

    function submitTest() {
      if (confirm('Are you sure you want to submit your test?')) {
        window.location.href = '/submit-test';
      }
    }

    function confirmExit() {
      if (confirm('Are you sure you want to exit this test? Your progress will be lost.')) {
        fetch('/api/test-actions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=clear_test'
        }).then(() => {
          window.location.href = '/select-test';
        });
      }
    }

    document.addEventListener('keydown', function(e) {
      if (e.key >= '1' && e.key <= '4') {
        e.preventDefault();
        const option = parseInt(e.key) - 1;
        const optionCards = document.querySelectorAll('.option-card');
        if (optionCards[option]) {
          optionCards[option].click();
        }
      }
      
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        const prevBtn = document.querySelector('.prev-btn');
        if (prevBtn && !prevBtn.disabled) prevBtn.click();
      }
      
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        const nextBtn = document.querySelector('.next-btn');
        if (nextBtn) nextBtn.click();
      }
      
      if (e.key.toLowerCase() === 'f') {
        e.preventDefault();
        const flagBtn = document.querySelector('.flag-btn');
        if (flagBtn) flagBtn.click();
      }
      
      if (e.key === 'Escape') {
        e.preventDefault();
        confirmExit();
      }
    });
  </script>

  <script src="/js/script.js"></script>
</body>
</html>