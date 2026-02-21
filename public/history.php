<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$tests = getTestsByUser($user_id, false);

$view_test = isset($_GET['test_id']) ? intval($_GET['test_id']) : null;
$test_details = null;

if ($view_test) {
    $test_details = getTestById($view_test);
    if (!$test_details || $test_details['user_id'] != $user_id) {
        $view_test = null;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'hide_history') {
    if (hideAllFromHistory($user_id)) {
        header("Location: /history?hidden=1");
        exit();
    }
}

$hidden = isset($_GET['hidden']);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - History</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="history-panel">
      <?php if ($hidden): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> History cleared successfully!
        </div>
      <?php endif; ?>
      
      <?php if (!$view_test && !empty($tests)): ?>
        <a href="?action=hide_history" class="clear-btn" onclick="return confirm('Do you want to clear the test history?')" title="Clear History">
          <i class="fas fa-trash-alt"></i>
        </a>
      <?php endif; ?>
      
      <div class="history-header">
        <h2>
          <?php if ($view_test): ?>
            <a href="/history" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            Test Review
          <?php else: ?>
            <i class="fas fa-history"></i> Test History
          <?php endif; ?>
        </h2>
      </div>

      <?php if ($view_test && $test_details): ?>
        <div class="test-details">
          <div class="test-summary">
            <div class="summary-item">
              <span class="label">Course:</span>
              <span class="value"><?php echo $test_details['course_id']; ?></span>
            </div>
            <div class="summary-item">
              <span class="label">Date:</span>
              <span class="value"><?php echo date('M j, Y. H:i', strtotime($test_details['date_taken'])); ?></span>
            </div>
            <div class="summary-item">
              <span class="label">Score:</span>
              <span class="value <?php echo $test_details['percentage'] >= 70 ? 'text-success' : ($test_details['percentage'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                <?php echo $test_details['percentage']; ?>% (<?php echo $test_details['score']; ?>/<?php echo $test_details['total']; ?>)
              </span>
            </div>
            <div class="summary-item">
              <span class="label">Time:</span>
              <span class="value"><?php echo floor($test_details['time_taken'] / 60); ?>:<?php echo str_pad($test_details['time_taken'] % 60, 2, '0', STR_PAD_LEFT); ?></span>
            </div>
          </div>

          <div class="test-questions">
            <h3>Questions Review</h3>
            <?php 
            $questions = $test_details['questions'] ?? [];
            
            foreach ($questions as $index => $question): 
              $user_answer = isset($test_details['answers'][$index]) ? $test_details['answers'][$index] : null;
              $is_correct = ($user_answer === $question['correct']);
            ?>
              <div class="review-item <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                <div class="review-question">
                  <span class="question-number">Q<?php echo $index + 1; ?>.</span>
                  <?php echo htmlspecialchars($question['question']); ?>
                </div>
                <div class="review-answers">
                  <div class="user-answer <?php echo !$is_correct ? 'incorrect' : ''; ?>">
                    <span class="label">Your answer:</span>
                    <span class="value <?php echo $is_correct ? 'text-success' : 'text-danger'; ?>">
                      <?php 
                      if ($user_answer !== null && isset($question['options'][$user_answer])) {
                        echo htmlspecialchars($question['options'][$user_answer]);
                      } else {
                        echo "Not answered";
                      }
                      ?>
                    </span>
                  </div>
                  <?php if (!$is_correct): ?>
                    <div class="correct-answer">
                      <span class="label">Correct answer:</span>
                      <span class="value text-success">
                        <?php echo htmlspecialchars($question['options'][$question['correct']]); ?>
                      </span>
                    </div>
                  <?php endif; ?>
                  <?php if (isset($question['explanation'])): ?>
                    <div class="explanation">
                      <i class="fas fa-info-circle"></i>
                      <?php echo htmlspecialchars($question['explanation']); ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <?php elseif (empty($tests)): ?>
        <div class="empty-state">
          <i class="fas fa-history"></i>
          <p>No test history yet</p><br>
          <a href="/select-test" class="btn btn-primary">Take a Test</a>
        </div>

      <?php else: ?>
        <div class="tests-list">
          <?php foreach ($tests as $test): ?>
            <a href="?test_id=<?php echo $test['id']; ?>" class="test-item">
              <div class="test-item-header">
                <span class="course-badge"><?php echo $test['course_id']; ?></span>
                <span class="test-date"><?php echo date('M j, Y', strtotime($test['date_taken'])); ?></span>
              </div>
              <div class="test-item-body">
                <div class="test-score">
                  <span class="list-label">Score: </span>
                  <span class="score-value <?php echo $test['percentage'] >= 70 ? 'text-success' : ($test['percentage'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                    <?php echo $test['percentage']; ?>%
                  </span>
                  <span class="score-detail">(<?php echo $test['score']; ?>/<?php echo $test['total']; ?>)</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="/js/script.js"></script>
</body>
</html>