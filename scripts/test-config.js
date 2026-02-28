const TestConfig = {
    courses: [],
    selectedCourse: null,
    selectedTopics: [],
    
    init: function() {
        console.log('TestConfig initializing...');
        this.loadCourses();
    },
    
    loadCourses: function() {
        // Add cache-busting parameter
        const cacheBuster = `?t=${Date.now()}`;
        
        fetch(`../storage/courses.json${cacheBuster}`)
            .then(response => {
                if (!response.ok) throw new Error('Failed to load courses');
                return response.json();
            })
            .then(courses => {
                this.courses = courses;
                this.renderCourses();
            })
            .catch(error => {
                console.error('Error loading courses:', error);
                Utils.showMessage('Failed to load courses', 'error');
            });
    },
    
    renderCourses: function() {
        const container = document.getElementById('coursesGrid');
        if (!container) return;
        
        let html = '';
        this.courses.forEach(course => {
            html += `
                <div class="course-card">
                    <div class="course-icon">
                        <i class="fas ${course.icon}"></i>
                    </div>
                    <h3>${Utils.escapeHtml(course.name)}</h3>
                    <div class="course-code">${course.id}</div>
                    <p class="course-description">${Utils.escapeHtml(course.description)}</p>
                    <button class="btn btn-primary select-course-btn" onclick="TestConfig.selectCourse('${course.id}')">
                        <i class="fas fa-check-circle"></i> Select Course
                    </button>
                </div>
            `;
        });
        
        container.innerHTML = html;
    },
    
    selectCourse: function(courseId) {
        console.log('Course selected:', courseId);
        this.selectedCourse = this.courses.find(c => c.id === courseId);
        if (!this.selectedCourse) return;
        
        document.getElementById('courseSelection').style.display = 'none';
        document.getElementById('testConfig').style.display = 'block';
        document.getElementById('selectedCourseTitle').innerHTML = 
            `${this.selectedCourse.name}`;
        
        this.loadTopics();
    },
    
    backToCourses: function() {
        document.getElementById('courseSelection').style.display = 'block';
        document.getElementById('testConfig').style.display = 'none';
        this.selectedCourse = null;
        this.selectedTopics = [];
    },
    
    loadTopics: function() {
        if (!this.selectedCourse) return;
        
        // Load topics from the course's study material to get topic names
        const subjectId = this.selectedCourse.id;
        const materialFile = this.getMaterialFileName(subjectId);
        
        // Add cache-busting parameter
        const cacheBuster = `?t=${Date.now()}`;
        
        fetch(`../storage/materials/${materialFile}${cacheBuster}`)
            .then(response => response.json())
            .then(data => {
                const subject = data[subjectId];
                if (subject && subject.topics) {
                    this.renderTopics(subject.topics);
                }
            })
            .catch(error => {
                console.error('Error loading topics:', error);
                // Fallback: use topic IDs from course
                const topics = {};
                this.selectedCourse.topics.forEach(topicId => {
                    topics[topicId] = { name: topicId.replace(/_/g, ' ') };
                });
                this.renderTopics(topics);
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
    
    renderTopics: function(topics) {
        const container = document.getElementById('topicsGrid');
        if (!container) return;
        
        let html = '';
        this.selectedCourse.topics.forEach(topicId => {
            const topicName = topics[topicId]?.name || topicId.replace(/_/g, ' ');
            html += `
                <label class="topic-checkbox">
                    <input type="checkbox" value="${topicId}" checked onchange="TestConfig.updateSelectedTopics()">
                    <span class="checkbox-label">
                        <i class="fas fa-folder"></i> ${Utils.escapeHtml(topicName)}
                    </span>
                </label>
            `;
        });
        
        container.innerHTML = html;
        this.selectedTopics = [...this.selectedCourse.topics];
    },
    
    toggleTopicSelection: function() {
        const source = document.querySelector('input[name="questionSource"]:checked').value;
        const topicsDiv = document.getElementById('topicsSelection');
        
        if (source === 'topics') {
            topicsDiv.style.display = 'block';
        } else {
            topicsDiv.style.display = 'none';
            this.selectedTopics = [...this.selectedCourse.topics];
        }
    },
    
    updateSelectedTopics: function() {
        const checkboxes = document.querySelectorAll('#topicsGrid input:checked');
        this.selectedTopics = Array.from(checkboxes).map(cb => cb.value);
    },
    
    selectAllTopics: function() {
        document.querySelectorAll('#topicsGrid input').forEach(cb => {
            cb.checked = true;
        });
        this.updateSelectedTopics();
    },
    
    deselectAllTopics: function() {
        document.querySelectorAll('#topicsGrid input').forEach(cb => {
            cb.checked = false;
        });
        this.updateSelectedTopics();
    },
    
    updateCount: function() {
        const count = document.getElementById('questionCount').value;
        document.getElementById('countValue').textContent = count;
        
        const timePerQuestion = this.selectedCourse?.time_per_question || 30;
        const totalSeconds = count * timePerQuestion;
        const minutes = Math.ceil(totalSeconds / 60);
        
        document.getElementById('timeEstimate').textContent = minutes;
    },
    
    startTest: function() {
        if (!this.selectedCourse) {
            Utils.showMessage('Please select a course', 'error');
            return;
        }
        
        const source = document.querySelector('input[name="questionSource"]:checked').value;
        const questionCount = parseInt(document.getElementById('questionCount').value);
        
        if (source === 'topics' && this.selectedTopics.length === 0) {
            Utils.showMessage('Please select at least one topic', 'error');
            return;
        }
        
        console.log('Starting test with config:', {
            course: this.selectedCourse.id,
            source: source,
            topics: this.selectedTopics,
            questionCount: questionCount
        });
        
        // Store test configuration
        const testConfig = {
            course: this.selectedCourse,
            source: source,
            topics: source === 'topics' ? this.selectedTopics : null,
            questionCount: questionCount,
            timeLimit: questionCount * (this.selectedCourse.time_per_question || 30)
        };
        
        localStorage.setItem('venus_test_config', JSON.stringify(testConfig));
        console.log('Test config saved, redirecting to take-test.html');
        window.location.href = 'take-test.html';
    }
};

// Initialize on page load if on select-test page
if (window.location.pathname.includes('select-test.html')) {
    document.addEventListener('DOMContentLoaded', () => TestConfig.init());
}