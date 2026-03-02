const App = {
  init: function() {
    this.loadNavbar();
    this.loadPageData();
    this.clearFileCaches();
  },

  clearFileCaches: function() {
    const preserve = [
      'venus_settings',
      'venus_tests',
      'venus_study_bookmarks',
      'venus_study_recent'
    ];
    
    const keysToRemove = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith('venus_') && !preserve.includes(key)) {
        keysToRemove.push(key);
      }
    }
    
    keysToRemove.forEach(key => localStorage.removeItem(key));
  },

  loadNavbar: function() {
    const possiblePaths = [
      'navbar.html',
      '/navbar.html',
      './navbar.html'
    ];
    
    this.tryLoadNavbar(possiblePaths, 0);
  },

  tryLoadNavbar: function(paths, index) {
    if (index >= paths.length) {
      console.error('Could not load navbar from any path');
      return;
    }

    fetch(paths[index])
      .then(response => {
        if (!response.ok) throw new Error('Not found');
        return response.text();
      })
      .then(html => {
        document.body.insertAdjacentHTML('afterbegin', html);
        this.updateActiveNavLink();
        this.initTheme();
        this.initOfflineDetection();
        this.setupNavbarEventListeners();
      })
      .catch(() => {
        this.tryLoadNavbar(paths, index + 1);
      });
  },

  setupNavbarEventListeners: function() {
    document.querySelectorAll('.logout-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.exit();
      });
    });

    const hamburger = document.querySelector('.hamburger');
    if (hamburger) {
      hamburger.addEventListener('click', () => this.toggleMenu());
    }

    document.addEventListener('click', (event) => {
      const menu = document.getElementById('menuDropdown');
      const hamburger = document.querySelector('.hamburger');
      if (menu && hamburger && !menu.contains(event.target) && !hamburger.contains(event.target)) {
        menu.classList.remove('show');
        hamburger.classList.remove('active');
      }
    });
  },

  toggleMenu: function() {
    const menu = document.getElementById('menuDropdown');
    const hamburger = document.querySelector('.hamburger');
    if (menu && hamburger) {
      menu.classList.toggle('show');
      hamburger.classList.toggle('active');
    }
  },

  exit: function() {
    if (confirm('Exit application?')) {
      window.location.href = 'index.html';
    }
  },

  updateActiveNavLink: function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.icon-bar a, .nav-links a').forEach(link => {
      const href = link.getAttribute('href');
      if (href === currentPage) {
        link.classList.add('active');
      }
    });
  },

  initTheme: function() {
    const settings = StorageManager.getSettings();
    if (settings.theme === 'light') {
      document.body.classList.add('light-mode');
    }
  },

  initOfflineDetection: function() {
    const offlineWarning = document.getElementById('offlineWarning');
    if (!offlineWarning) return;
    
    const updateOnlineStatus = () => {
      if (!navigator.onLine) {
        offlineWarning.style.display = 'flex';
        document.body.classList.add('has-offline-banner');
      } else {
        offlineWarning.style.display = 'none';
        document.body.classList.remove('has-offline-banner');
      }
    };

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
  },

  loadPageData: function() {
    const page = window.location.pathname.split('/').pop() || 'index.html';
    
    if (page === 'profile.html') {
      this.loadProfilePage();
    } else if (page === 'settings.html') {
      this.loadSettingsPage();
    } else if (page === 'history.html') {
      this.loadHistoryPage();
    } else if (page === 'study.html') {
      this.loadStudyPage();
    } else if (page === 'select-test.html') {
      this.loadSelectTestPage();
    } else if (page === 'take-test.html') {
      console.log('Take test page - waiting for TestManager to initialize');
    } else if (page === 'submit-test.html') {
      this.loadSubmitTestPage();
    } else if (page === 'index.html' || page === '') {
      this.loadHomePage();
    }
  },

  loadProfilePage: function() {
    setTimeout(() => {
      if (typeof ProfileManager !== 'undefined') {
        ProfileManager.init();
      }
    }, 100);
  },

  loadSettingsPage: function() {
    setTimeout(() => {
      if (typeof SettingsManager !== 'undefined') {
        SettingsManager.init();
      }
    }, 100);
  },

  loadHistoryPage: function() {
    setTimeout(() => {
      if (typeof HistoryManager !== 'undefined') {
        HistoryManager.init();
      }
    }, 100);
  },

  loadStudyPage: function() {
    console.log('Loading study page...');
    
    const container = document.getElementById('subjectsAccordion');
    if (container) {
      container.innerHTML = '<div class="loading-indicator" style="text-align: center; padding: 2rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Loading study materials...</div>';
    }
    
    const cacheBuster = `?t=${Date.now()}`;
    
    const subjects = ['MTS101', 'PHY101', 'STA111', 'CSC101', 'GNS103'];
    const promises = subjects.map(subjectId => 
      fetch(`../storage/materials/${this.getMaterialFileName(subjectId)}${cacheBuster}`)
      .then(response => {
        if (!response.ok) throw new Error(`Failed to load ${subjectId}`);
          return response.json();
      })
      .catch(error => {
        console.error(`Error loading ${subjectId}:`, error);
        return null;
      })
    );
    
    Promise.all(promises)
      .then(results => {
        const studyNotes = {};
        results.forEach((data, index) => {
          if (data) {
            const subjectId = subjects[index];
            studyNotes[subjectId] = data[subjectId];
          }
        });
        
        console.log('Study notes loaded successfully');
        window.studyNotes = studyNotes;
        
        setTimeout(() => {
          if (typeof StudyManager !== 'undefined') {
            StudyManager.init(studyNotes);
          }
        }, 100);
      })
      .catch(error => {
        console.error('Error loading study notes:', error);
        if (container) {
          container.innerHTML = '<div class="error" style="text-align: center; padding: 2rem; color: var(--accent-danger);"><i class="fas fa-exclamation-circle"></i> Failed to load study materials. Please refresh the page.</div>';
        }
      });
  },

  getMaterialFileName: function(subjectId) {
    const files = {
      'MTS101': 'mathematics.json',
      'PHY101': 'physics.json',
      'STA111': 'statistics.json',
      'CSC101': 'computer.json',
      'GNS103': 'literacy.json'
    };
    return files[subjectId] || subjectId.toLowerCase() + '.json';
  },

  loadSelectTestPage: function() {
    const cacheBuster = `?t=${Date.now()}`;
    
    fetch(`../storage/courses.json${cacheBuster}`)
      .then(response => response.json())
      .then(courses => {
        window.coursesData = courses;
        this.renderCourses(courses);
      })
      .catch(error => {
        console.error('Error loading courses:', error);
      });
  },

  renderCourses: function(courses) {
    const container = document.querySelector('.courses-grid');
    if (!container) return;

    let html = '';
    courses.forEach(course => {
      html += `
        <div class="course-card">
          <div class="course-icon">
            <i class="fas ${course.icon}"></i>
          </div>
          <h3>${this.escapeHtml(course.name)}</h3>
          <div class="course-code">${this.escapeHtml(course.id)}</div>
          <p class="course-description">${this.escapeHtml(course.description)}</p>
          <button class="btn btn-primary select-course-btn" onclick="TestConfig.selectCourse('${course.id}')">
            <i class="fas fa-check-circle"></i> Select Course
          </button>
        </div>
      `;
    });
    container.innerHTML = html;
  },

  loadSubmitTestPage: function() {
    const savedTest = localStorage.getItem('venus_current_result');
    if (savedTest) {
      const result = JSON.parse(savedTest);
      setTimeout(() => {
        if (typeof TestManager !== 'undefined') {
          TestManager.displayResults(result);
        }
      }, 100);
      localStorage.removeItem('venus_current_result');
    } else {
      window.location.href = 'select-test.html';
    }
  },

  escapeHtml: function(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
};

document.addEventListener('DOMContentLoaded', () => App.init());