// Main application controller
const App = {
    init: function() {
        this.loadNavbar();
        this.loadPageData();
        StorageManager.init();
        this.updateNotificationBadgePeriodically();
    },

    loadNavbar: function() {
        // Try multiple paths for navbar
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
        // Setup logout buttons
        document.querySelectorAll('.logout-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        });

        // Setup hamburger menu
        const hamburger = document.querySelector('.hamburger');
        if (hamburger) {
            hamburger.addEventListener('click', () => this.toggleMenu());
        }

        // Close menu when clicking outside
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

    logout: function() {
        if (confirm('Exit application? Your data will remain saved.')) {
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
        const theme = this.getCookie('theme') || 'dark';
        if (theme === 'light') {
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

    updateNotificationBadgePeriodically: function() {
        // Update every 30 seconds
        setInterval(() => {
            if (typeof StorageManager !== 'undefined' && StorageManager.updateNotificationBadge) {
                StorageManager.updateNotificationBadge();
            }
        }, 30000);
    },

    getCookie: function(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    },

    loadPageData: function() {
        const page = window.location.pathname.split('/').pop() || 'index.html';
        
        if (page === 'profile.html') {
            this.loadProfilePage();
        } else if (page === 'settings.html') {
            this.loadSettingsPage();
        } else if (page === 'history.html') {
            this.loadHistoryPage();
        } else if (page === 'notifications.html') {
            this.loadNotificationsPage();
        } else if (page === 'study.html') {
            this.loadStudyPage();
        } else if (page === 'select-test.html') {
            this.loadSelectTestPage();
        } else if (page === 'take-test.html') {
            this.loadTakeTestPage();
        } else if (page === 'submit-test.html') {
            this.loadSubmitTestPage();
        } else if (page === 'index.html' || page === '') {
            this.loadHomePage();
        }
    },

    loadProfilePage: function() {
        // Wait for DOM and navbar to be ready
        setTimeout(() => {
            if (typeof ProfileManager !== 'undefined') {
                ProfileManager.init();
                this.setupProfileEventListeners();
            }
        }, 100);
    },

    setupProfileEventListeners: function() {
        const bioInput = document.getElementById('bioInput');
        const bioCharCount = document.getElementById('bioCharCount');
        
        if (bioInput && bioCharCount) {
            bioInput.addEventListener('input', function(e) {
                const count = e.target.value.length;
                bioCharCount.textContent = count;
                
                if (count >= 110) {
                    bioCharCount.style.color = 'var(--accent-danger)';
                } else if (count >= 90) {
                    bioCharCount.style.color = '#f59e0b';
                } else {
                    bioCharCount.style.color = 'var(--text-secondary)';
                }
            });
        }

        const imageInput = document.getElementById('profileImageInput');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    if (this.files[0].size > 5 * 1024 * 1024) {
                        Utils.showMessage('Image size must be less than 5MB!', 'error');
                        this.value = '';
                        return;
                    }
                    
                    if (!this.files[0].type.match('image.*')) {
                        Utils.showMessage('Only image files are allowed!', 'error');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        ProfileManager.updateAvatar(e.target.result);
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    },

    loadSettingsPage: function() {
        setTimeout(() => {
            if (typeof SettingsManager !== 'undefined') {
                SettingsManager.init();
                this.setupSettingsEventListeners();
            }
        }, 100);
    },

    setupSettingsEventListeners: function() {
        const themeSwitch = document.getElementById('themeSwitch');
        if (themeSwitch) {
            // Set initial state
            const theme = this.getCookie('theme') || 'dark';
            themeSwitch.checked = theme === 'light';
            
            themeSwitch.addEventListener('change', function(e) {
                const isLightMode = this.checked;
                const theme = isLightMode ? 'light' : 'dark';
                
                const themeOn = document.querySelector('.theme-on');
                const themeOff = document.querySelector('.theme-off');
                
                if (themeOn) themeOn.classList.toggle('active', !isLightMode);
                if (themeOff) themeOff.classList.toggle('active', isLightMode);
                
                if (isLightMode) {
                    document.body.classList.add('light-mode');
                } else {
                    document.body.classList.remove('light-mode');
                }
                
                const date = new Date();
                date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = `theme=${theme}; path=/; expires=${date.toUTCString()}; SameSite=Lax`;
                
                SettingsManager.saveTheme(theme);
            });
        }
    },

    loadHistoryPage: function() {
        setTimeout(() => {
            if (typeof HistoryManager !== 'undefined') {
                HistoryManager.init();
            }
        }, 100);
    },

    loadNotificationsPage: function() {
        setTimeout(() => {
            if (typeof NotificationManager !== 'undefined') {
                NotificationManager.init();
            }
        }, 100);
    },

    loadStudyPage: function() {
    console.log('Loading study page...');
    
    // Show loading state
    const container = document.getElementById('subjectsAccordion');
    if (container) {
        container.innerHTML = '<div class="loading-indicator" style="text-align: center; padding: 2rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Loading study materials...</div>';
    }
    
    // Fetch study notes
    fetch('storage/study_notes.json')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(notes => {
            console.log('Study notes loaded successfully:', notes);
            window.studyNotes = notes;
            
            // Initialize StudyManager after a short delay to ensure DOM is ready
            setTimeout(() => {
                if (typeof StudyManager !== 'undefined') {
                    StudyManager.init(notes);
                } else {
                    console.error('StudyManager not defined');
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error loading study notes:', error);
            if (container) {
                container.innerHTML = '<div class="error" style="text-align: center; padding: 2rem; color: var(--accent-danger);"><i class="fas fa-exclamation-circle"></i> Failed to load study materials. Please refresh the page.</div>';
            }
            Utils.showMessage('Failed to load study materials', 'error');
        });
    },

    loadSelectTestPage: function() {
        fetch('storage/courses.json')
            .then(response => response.json())
            .then(courses => {
                window.coursesData = courses;
                this.renderCourses(courses);
            })
            .catch(error => {
                console.error('Error loading courses:', error);
                Utils.showMessage('Failed to load courses', 'error');
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
                    <div class="course-meta">
                        <span><i class="fas fa-question-circle"></i> ${course.question_count} Questions</span>
                        <span><i class="fas fa-clock"></i> ${Math.floor(course.time_limit / 60)} min</span>
                    </div>
                    <a href="take-test.html?course=${course.id}" class="btn btn-primary btn-block">
                        <i class="fas fa-play"></i> Start Test
                    </a>
                </div>
            `;
        });
        container.innerHTML = html;
    },

    loadTakeTestPage: function() {
        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get('course');
        
        if (!courseId) {
            window.location.href = 'select-test.html';
            return;
        }

        Promise.all([
            fetch('storage/courses.json').then(r => r.json()),
            this.loadQuestions(courseId)
        ]).then(([courses, questions]) => {
            const course = courses.find(c => c.id === courseId);
            if (!course || !questions || questions.length === 0) {
                Utils.showMessage('Failed to load test questions', 'error');
                setTimeout(() => {
                    window.location.href = 'select-test.html';
                }, 2000);
                return;
            }

            // Shuffle and take first 20 questions
            const shuffled = [...questions].sort(() => Math.random() - 0.5);
            const testQuestions = shuffled.slice(0, 20);

            window.testData = {
                course: course,
                questions: testQuestions,
                timeLimit: course.time_limit
            };

            setTimeout(() => {
                if (typeof TestManager !== 'undefined') {
                    TestManager.init(window.testData);
                }
            }, 100);
        }).catch(error => {
            console.error('Error loading test:', error);
            Utils.showMessage('Failed to load test', 'error');
        });
    },

    loadQuestions: function(courseId) {
        const files = {
            'MTS101': 'storage/mathematics.json',
            'PHY101': 'storage/physics.json',
            'STA111': 'storage/statistics.json',
            'CSC101': 'storage/computer.json',
            'GNS103': 'storage/literacy.json'
        };

        const file = files[courseId];
        if (!file) return Promise.resolve([]);

        return fetch(file)
            .then(r => r.json())
            .then(data => data[courseId] || []);
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

    loadHomePage: function() {
        setTimeout(() => {
            const profile = StorageManager.getProfile();
            const displayUsername = document.getElementById('displayUsername');
            if (displayUsername) {
                displayUsername.textContent = profile.username;
            }
        }, 200);
    },

    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => App.init());