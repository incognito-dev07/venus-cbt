<?php
require_once 'config.php';
$study_notes = json_decode(file_get_contents('storage/study_notes.json'), true);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Study Materials</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/public/styles/core.css">
  <link rel="stylesheet" href="/public/styles/component.css">
  <link rel="stylesheet" href="/public/styles/pages.css">
  <link rel="stylesheet" href="/public/styles/responsive.css">
  <link rel="stylesheet" href="/public/styles/study.css?v=<?php echo time(); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <!-- Mobile Study App -->
  <div class="study-app" id="studyApp">
    
    <!-- BROWSE VIEW -->
    <div class="browse-view" id="browseView">
      
      <!-- Header -->
      <div class="mobile-header">
        <div class="header-left">
          <i class="fas fa-book-open" style="color: var(--accent-primary);"></i>
          <h1>Study</h1>
        </div>
        <button class="header-icon" onclick="StudyManager.showBookmarks()">
          <i class="fas fa-bookmark"></i>
          <span class="badge" id="mobileBookmarkCount">0</span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="search-container">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="mobileSearch" placeholder="Search topics, keywords..." autocomplete="off">
        </div>
        <div class="search-results" id="mobileSearchResults" style="display: none;"></div>
      </div>

      <!-- Subjects Accordion -->
      <div class="subjects-accordion" id="subjectsAccordion">
        <?php foreach ($study_notes as $subject_id => $subject): ?>
          <div class="subject-block" data-subject="<?php echo $subject_id; ?>">
            
            <!-- Subject Header -->
            <div class="subject-header" onclick="StudyManager.toggleSubject('<?php echo $subject_id; ?>')">
              <div class="subject-title">
                <div class="subject-icon">
                  <i class="fas <?php echo $subject['icon']; ?>"></i>
                </div>
                <div>
                  <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                  <span class="topic-count"><?php echo count($subject['topics']); ?> topics</span>
                </div>
              </div>
              <i class="fas fa-chevron-down arrow-icon" id="arrow-<?php echo $subject_id; ?>"></i>
            </div>

            <!-- Topics Container (Hidden by default) -->
            <div class="topics-wrapper" id="topics-<?php echo $subject_id; ?>" style="display: none;">
              <?php foreach ($subject['topics'] as $topic_id => $topic): ?>
                
                <!-- Topic Header -->
                <div class="topic-header" onclick="StudyManager.toggleTopic('<?php echo $subject_id; ?>', '<?php echo $topic_id; ?>', event)">
                  <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($topic['name']); ?></span>
                  <i class="fas fa-chevron-right topic-arrow" id="topic-arrow-<?php echo $subject_id; ?>-<?php echo $topic_id; ?>"></i>
                </div>

                <!-- Subtopics Container (Hidden by default) -->
                <div class="subtopics-wrapper" id="subtopics-<?php echo $subject_id; ?>-<?php echo $topic_id; ?>" style="display: none;">
                  <?php foreach ($topic['subtopics'] as $subtopic_id => $subtopic): ?>
                    <div class="subtopic-item" 
                         data-subject="<?php echo $subject_id; ?>" 
                         data-topic="<?php echo $topic_id; ?>" 
                         data-subtopic="<?php echo $subtopic_id; ?>"
                         data-subject-name="<?php echo htmlspecialchars($subject['name']); ?>"
                         data-topic-name="<?php echo htmlspecialchars($topic['name']); ?>"
                         data-subtopic-name="<?php echo htmlspecialchars($subtopic['name']); ?>">
                      <i class="fas fa-file-alt"></i>
                      <span><?php echo htmlspecialchars($subtopic['name']); ?></span>
                      <i class="fas fa-arrow-right"></i>
                    </div>
                  <?php endforeach; ?>
                </div>

              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent Section (3 topics only) -->
      <div class="recent-section" id="recentSection">
        <div class="recent-header">
          <h4><i class="fas fa-clock"></i> Recent Topics</h4>
        </div>
        <div class="recent-list" id="recentList">
          <div class="empty-recent">
            <i class="fas fa-book-open"></i>
            <p>No recent topics</p>
          </div>
        </div>
      </div>
    </div>

    <!-- STUDY VIEW (Full Screen) -->
    <div class="study-view" id="studyView" style="display: none;">
      
      <!-- Study Header with breadcrumb format -->
      <div class="study-header">
        <button class="back-btn" onclick="StudyManager.backToBrowse()">
          <i class="fas fa-arrow-left"></i>
        </button>
        <div class="study-breadcrumb" id="studyBreadcrumb">
          <span id="breadcrumbSubject"></span> > 
          <span id="breadcrumbTopic"></span> >
        </div>
        <div class="study-actions">
          <button class="action-btn" onclick="StudyManager.toggleBookmark()" id="studyBookmarkBtn">
            <i class="far fa-bookmark"></i>
          </button>
          <button class="action-btn" onclick="StudyManager.downloadPDF()">
            <i class="fas fa-download"></i>
          </button>
        </div>
      </div>

      <!-- Study Content (Scrollable) -->
      <div class="study-content" id="studyContent">
        <!-- Content will be loaded here -->
      </div>

      <!-- Bottom Navigation -->
      <div class="study-bottom-nav">
        <button class="nav-item" onclick="StudyManager.showBookmarks()">
          <i class="fas fa-bookmark"></i>
          <span>Bookmarks</span>
        </button>
        <button class="nav-item" onclick="StudyManager.shareTopic()">
          <i class="fas fa-share-alt"></i>
          <span>Share</span>
        </button>
        <button class="nav-item" onclick="StudyManager.scrollToTop()">
          <i class="fas fa-arrow-up"></i>
          <span>Top</span>
        </button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script src="/public/scripts/utilities.js"></script>
  <script src="/public/scripts/storage.js"></script>
  <script>
    // Make studyNotes globally available
    const studyNotes = <?php echo json_encode($study_notes); ?>;
  </script>
  <script src="/public/scripts/study.js?v=<?php echo time(); ?>"></script>
</body>
</html>