<?php
require_once 'config.php';
$courses = getCourses();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Select Test</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="styles/core.css">
  <link rel="stylesheet" href="styles/component.css">
  <link rel="stylesheet" href="styles/pages.css">
  <link rel="stylesheet" href="styles/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <div class="select-test-panel">
      <div class="panel-header">
        <h2><i class="fas fa-pencil-alt"></i> Choose Test</h2>
      </div>

      <div class="courses-grid">
        <?php foreach ($courses as $course): ?>
          <div class="course-card">
            <div class="course-icon">
              <i class="fas <?php echo $course['icon']; ?>"></i>
            </div>
            <h3><?php echo htmlspecialchars($course['name']); ?></h3>
            <div class="course-code"><?php echo $course['id']; ?></div>
            <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
            <div class="course-meta">
              <span><i class="fas fa-question-circle"></i> <?php echo $course['question_count']; ?> Questions</span>
              <span><i class="fas fa-clock"></i> <?php echo floor($course['time_limit'] / 60); ?> min</span>
            </div>
            <a href="take-test.php?course=<?php echo $course['id']; ?>" class="btn btn-primary btn-block">
              <i class="fas fa-play"></i> Start Test
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script src="scripts/utilities.js"></script>
</body>
</html>