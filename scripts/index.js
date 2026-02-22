// Main application controller
const App = {
    init: function() {
        this.loadNavbar();
        this.loadPageData();
        StorageManager.init();
    },

    loadNavbar: function() {
        fetch('navbar.html')
            .then(response => response.text())
            .then(html => {
                document.body.insertAdjacentHTML('afterbegin', html);
                this.updateActiveNavLink();
                this.initTheme();
                this.initOfflineDetection();
            });
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
        const theme = getCookie('theme') || 'dark';
        if (theme === 'light') {
            document.body.classList.add('light-mode');
        }
    },

    initOfflineDetection: function() {
        const offlineWarning = document.getElementById('offlineWarning');
        
        function updateOnlineStatus() {
            if (!navigator.onLine) {
                offlineWarning.style.display = 'flex';
                document.body.classList.add('has-offline-banner');
            } else {
                offlineWarning.style.display = 'none';
                document.body.classList.remove('has-offline-banner');
            }
        }

        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();
    },

    loadPageData: function() {
        const page = window.location.pathname.split('/').pop();
        
        if (page === 'profile.html') {
            ProfileManager.init();
        } else if (page === 'settings.html') {
            SettingsManager.init();
        } else if (page === 'history.html') {
            HistoryManager.init();
        } else if (page === 'notifications.html') {
            NotificationManager.init();
        } else if (page === 'study.html') {
            this.loadStudyNotes();
        } else if (page === 'select-test.html') {
            this.loadCourses();
        } else if (page === 'take-test.html') {
            this.loadTest();
        } else if (page === 'submit-test.html') {
            this.loadResult();
        } else if (page === 'index.html' || page === '') {
            this.loadHomePage();
        }
    },

    loadCourses: function() {
        fetch('storage/courses.json')
            .then(response => response.json())
            .then(courses => {
                window.coursesData = courses;
                this.renderCourses(courses);
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
                    <h3>${course.name}</h3>
                    <div class="course-code">${course.id}</div>
                    <p class="course-description">${course.description}</p>
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

    loadStudyNotes: function() {
        fetch('storage/study_notes.json')
            .then(response => response.json())
            .then(notes => {
                window.studyNotes = notes;
                if (typeof StudyManager !== 'undefined') {
                    StudyManager.init(notes);
                }
            });
    },

    loadTest: function() {
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
            if (!course || !questions) {
                window.location.href = 'select-test.html';
                return;
            }

            const shuffled = [...questions].sort(() => Math.random() - 0.5);
            const testQuestions = shuffled.slice(0, 20);

            window.testData = {
                course: course,
                questions: testQuestions,
                timeLimit: course.time_limit
            };

            if (typeof TestManager !== 'undefined') {
                TestManager.init(window.testData);
            }
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

    loadResult: function() {
        const savedTest = localStorage.getItem('venus_current_result');
        if (savedTest) {
            const result = JSON.parse(savedTest);
            if (typeof TestManager !== 'undefined') {
                TestManager.displayResults(result);
            }
            localStorage.removeItem('venus_current_result');
        } else {
            window.location.href = 'select-test.html';
        }
    },

    loadHomePage: function() {
        const profile = StorageManager.getProfile();
        const displayUsername = document.getElementById('displayUsername');
        if (displayUsername) {
            displayUsername.textContent = profile.username;
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => App.init());