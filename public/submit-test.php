<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if (!isset($_SESSION['test'])) {
  header("Location: /select-test");
  exit();
}

$test = $_SESSION['test'];
$course_id = $test['course_id'];
$questions = $test['questions'];
$answers = $test['answers'];
$time_taken = time() - $test['start_time'];

$score = 0;
$results = [];

foreach ($questions as $index => $question) {
  $is_correct = ($answers[$index] === $question['correct']);
  if ($is_correct) {
    $score++;
  }
    
  $results[] = [
    'question' => $question['question'],
    'user_answer' => isset($answers[$index]) ? $question['options'][$answers[$index]] : 'Not answered',
    'correct_answer' => $question['options'][$question['correct']],
        'is_correct' => $is_correct,
        'explanation' => $question['explanation'] ?? 'No explanation available'
  ];
}

$total = count($questions);
$percentage = round(($score / $total) * 100, 2);

$test_id = saveTestResult([
  'user_id' => $_SESSION['user_id'],
  'course_id' => $course_id,
  'score' => $score,
  'total' => $total,
  'percentage' => $percentage,
  'time_taken' => $time_taken,
  'answers' => $answers,
  'questions' => $questions
]);

unset($_SESSION['test']);

$course = getCourse($course_id);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Test Results</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="result-card">
      <div class="result-header <?php echo $percentage >= 70 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger'); ?>">
        <div class="result-icon">
          <i class="fas fa-<?php echo $percentage >= 70 ? 'trophy' : ($percentage >= 50 ? 'smile' : 'frown'); ?>"></i>
        </div>
        <div class="result-title">
          <h2>Test Complete!</h2>
          <div class="score-display">
            <span class="score"><?php echo $percentage; ?>%</span>
            <span class="score-detail"><?php echo $score; ?>/<?php echo $total; ?> correct</span>
          </div>
        </div>
      </div>

      <div class="result-stats">
        <div class="stat-item">
          <i class="fas fa-book"></i>
          <div class="stat-content">
            <span class="stat-label">Course</span>
            <span class="stat-value"><?php echo $course['id']; ?></span>
          </div>
        </div>
        <div class="stat-item">
          <i class="fas fa-clock"></i>
          <div class="stat-content">
            <span class="stat-label">Time Taken</span>
            <span class="stat-value"><?php echo floor($time_taken / 60); ?>:<?php echo str_pad($time_taken % 60, 2, '0', STR_PAD_LEFT); ?></span>
          </div>
        </div>
        <div class="stat-item">
          <i class="fas fa-star"></i>
          <div class="stat-content">
            <span class="stat-label">Points Earned</span>
            <span class="stat-value"><?php echo ($score * 10) + 50; ?></span>
          </div>
        </div>
      </div>

      <div class="result-actions">
        <a href="/select-test" class="btn btn-primary">
          <i class="fas fa-redo"></i> Take Another Test
        </a>
        <a href="/leaderboard" class="btn btn-primary">
          <i class="fas fa-trophy"></i> View Leaderboard
        </a>
        <a href="/" class="btn btn-primary">
          <i class="fas fa-home"></i> Home
        </a>
      </div>

      <div class="review-section">
        <h3><i class="fas fa-search"></i> Review Answers</h3>
        
        <?php foreach ($results as $index => $result): ?>
          <div class="review-item <?php echo $result['is_correct'] ? 'correct' : 'incorrect'; ?>">
            <div class="review-question">
              <span class="question-number">Q<?php echo $index + 1; ?>.</span>
              <?php echo htmlspecialchars($result['question']); ?>
            </div>
            <div class="review-answers">
              <div class="user-answer">
                <span class="label">Your answer:</span>
                <span class="value <?php echo $result['is_correct'] ? 'text-success' : 'text-danger'; ?>">
                  <?php echo htmlspecialchars($result['user_answer']); ?>
                </span>
              </div>
              <?php if (!$result['is_correct']): ?>
                <div class="correct-answer">
                  <span class="label">Correct answer:</span>
                  <span class="value text-success">
                    <?php echo htmlspecialchars($result['correct_answer']); ?>
                  </span>
                </div>
              <?php endif; ?>
              <div class="explanation">
                <i class="fas fa-info-circle"></i>
                <?php echo htmlspecialchars($result['explanation']); ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script src="/js/script.js"></script>
</body>
</html>