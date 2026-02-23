// Main application controller
const App = {
    init: function() {
        this.loadNavbar();
        this.loadPageData();
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
        // Setup exit button
        document.querySelectorAll('.logout-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exit();
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
        
        if (page === 'user.html') {
            this.loadUserPage();
        } else if (page === 'history.html') {
            this.loadHistoryPage();
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

    loadUserPage: function() {
        setTimeout(() => {
            if (typeof UserManager !== 'undefined') {
                UserManager.init();
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
        
        // Load all study material files
        const subjects = ['MTS101', 'PHY101', 'STA111', 'CSC101', 'GNS103'];
        const promises = subjects.map(subjectId => 
            fetch(`storage/materials/${this.getMaterialFileName(subjectId)}`)
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
        fetch('storage/courses.json')
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
                setTimeout(() => {
                    window.location.href = 'select-test.html';
                }, 2000);
                return;
            }

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
        });
    },

    loadQuestions: function(courseId) {
        const files = {
            'MTS101': 'storage/questions/mathematics.json',
            'PHY101': 'storage/questions/physics.json',
            'STA111': 'storage/questions/statistics.json',
            'CSC101': 'storage/questions/computer.json',
            'GNS103': 'storage/questions/literacy.json'
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
        // No username to display, just show welcome message
    },

    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => App.init());