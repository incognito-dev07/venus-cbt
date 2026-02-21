<?php
require_once __DIR__ . '/../includes/config.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'global';
$course = isset($_GET['course']) ? $_GET['course'] : 'MTH101';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$weekly_leaderboard = getWeeklyLeaderboard($limit);
$global_leaderboard = getGlobalLeaderboard($limit, $offset);
$course_leaderboard = getCourseLeaderboard($course, $limit);

$courses = getCourses();

$current_user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

$db = getDB();
$total_users = $db->querySingle('SELECT COUNT(*) FROM users WHERE total_tests > 0');
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Leaderboard</title>
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
    <div class="leaderboard-panel">
      <div class="panel-header">
        <h2><i class="fas fa-trophy"></i> Leaderboard</h2>
      </div>

      <div class="leaderboard-tabs">
        <a href="?type=global&page=1" class="tab <?php echo $type == 'global' ? 'active' : ''; ?>">
          <i class="fas fa-globe"></i> Global
        </a>
        <a href="?type=weekly" class="tab <?php echo $type == 'weekly' ? 'active' : ''; ?>">
          <i class="fas fa-calendar-week"></i> Weekly
        </a>
        <a href="?type=course" class="tab <?php echo $type == 'course' ? 'active' : ''; ?>">
          <i class="fas fa-book"></i> By Course
        </a>
      </div>

      <?php if ($type == 'course'): ?>
        <div class="course-filter">
          <div class="custom-select-wrapper">
            <div class="custom-select" id="customCourseSelect">
              <div class="select-selected">
                <?php 
                  $selected_course = getCourse($course);
                  echo $selected_course ? htmlspecialchars($selected_course['name']) : 'Select Course';
                ?>
                <i class="fas fa-chevron-down"></i>
              </div>
              <div class="select-items" id="selectItems">
                <?php foreach ($courses as $c): ?>
                  <div class="select-item <?php echo $course == $c['id'] ? 'same-as-selected' : ''; ?>" 
                       data-value="<?php echo htmlspecialchars($c['id']); ?>">
                    <span class="course-code"><?php echo htmlspecialchars($c['id']); ?></span>
                    <span class="course-name"><?php echo htmlspecialchars($c['name']); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="leaderboard-table">
          <thead>
            <tr>
              <th>S/N</th>
              <th>User</th>
              <th>Points</th>
              <th>Avg</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $leaderboard = [];
            if ($type == 'global') {
              $leaderboard = $global_leaderboard;
            } elseif ($type == 'weekly') {
              $leaderboard = $weekly_leaderboard;
            } else {
              $leaderboard = $course_leaderboard;
            }

            if (empty($leaderboard)): ?>
              <tr>
                <td colspan="4" class="no-data empty-state"><br>
                  <i class="fas fa-info-circle"></i> 
                  <p>No data available yet</p><br>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($leaderboard as $index => $entry): 
                $rank = $offset + $index + 1;
                $is_current_user = $current_user_id && $entry['user_id'] == $current_user_id;
                
                $user = findUserById($entry['user_id']);
                $hide_avg = isset($user['privacy']['hide_avg_leaderboard']) && $user['privacy']['hide_avg_leaderboard'];
              ?>
                <tr class="<?php echo $is_current_user ? 'current-user' : ''; ?>" onclick="window.location.href='/view-profile?id=<?php echo $entry['user_id']; ?>'">
                  <td class="rank">
                    <?php if ($rank == 1): ?>
                      <span class="rank-number gold">#1</span>
                    <?php elseif ($rank == 2): ?>
                      <span class="rank-number silver">#2</span>
                    <?php elseif ($rank == 3): ?>
                      <span class="rank-number bronze">#3</span>
                    <?php else: ?>
                      <span class="rank-number">#<?php echo $rank; ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="user-info">
                    <div class="user-avatar">
                      <?php 
                      if ($user && isset($user['profile_image']) && $user['profile_image'] && imageExists($user['profile_image'])): 
                      ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="">
                      <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                      <?php endif; ?>
                    </div>
                    <div class="user-details">
                      <span class="username"><?php echo htmlspecialchars($entry['username']); ?></span>
                    </div>
                  </td>
                  <td class="points"><?php echo number_format($entry['points']); ?></td>
                  <td class="score">
                    <?php if ($hide_avg && !$is_current_user): ?>
                      <span class="hidden-score" title="User has hidden their average">
                        <i class="fas fa-eye-slash"></i>
                      </span>
                    <?php else: ?>
                      <?php 
                      $avg_score = isset($entry['avg_score']) && $entry['avg_score'] !== null ? $entry['avg_score'] : 0;
                      echo $avg_score == 100 ? '100%' : number_format($avg_score, 1) . '%'; 
                      ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($type == 'global' && $total_pages > 1): ?>
        <div class="pagination" style="margin-top: 2rem; text-align: center;">
          <?php for ($i = 1; $i <= $total_pages && $i <= 10; $i++): ?>
            <a href="?type=global&page=<?php echo $i; ?>" class="btn btn-small <?php echo $i == $page ? 'btn-primary' : ''; ?>" style="margin: 0 0.25rem;">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
          <?php if ($total_pages > 10): ?>
            <span>...</span>
            <a href="?type=global&page=<?php echo $total_pages; ?>" class="btn btn-small"><?php echo $total_pages; ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function changeCourse(courseId) {
      window.location.href = '/leaderboard?type=course&course=' + courseId;
    }

    document.addEventListener('DOMContentLoaded', function() {
      const customSelect = document.getElementById('customCourseSelect');
      if (!customSelect) return;

      const selected = customSelect.querySelector('.select-selected');
      const items = customSelect.querySelectorAll('.select-item');

      selected.addEventListener('click', function(e) {
        e.stopPropagation();
        customSelect.classList.toggle('active');
      });

      items.forEach(item => {
        item.addEventListener('click', function(e) {
          e.stopPropagation();
          const value = this.dataset.value;
          
          const courseCode = this.querySelector('.course-code').textContent;
          const courseName = this.querySelector('.course-name').textContent;
          selected.innerHTML = `${courseCode} - ${courseName} <i class="fas fa-chevron-down"></i>`;
          
          items.forEach(i => i.classList.remove('same-as-selected'));
          this.classList.add('same-as-selected');
          
          customSelect.classList.remove('active');
          
          changeCourse(value);
        });
      });

      document.addEventListener('click', function(e) {
        if (!customSelect.contains(e.target)) {
          customSelect.classList.remove('active');
        }
      });
    });
  </script>

  <script src="/js/script.js"></script>
</body>
</html>