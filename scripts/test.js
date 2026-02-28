const TestManager = {
    course: null,
    questions: null,
    answers: [],
    flagged: [],
    currentIndex: 0,
    timeRemaining: 0,
    timerInterval: null,
    startTime: null,
    config: null,
    
    init: function() {
        console.log('TestManager initializing...');
        
        // Load test configuration from localStorage (only config, not questions)
        const configStr = localStorage.getItem('venus_test_config');
        if (!configStr) {
            console.error('No test config found, redirecting to select-test');
            window.location.href = 'select-test.html';
            return;
        }
        
        try {
            this.config = JSON.parse(configStr);
            this.course = this.config.course;
            this.timeRemaining = this.config.timeLimit;
            this.startTime = Date.now();
            
            // Load questions from file
            this.loadQuestionsFromFile();
        } catch (e) {
            console.error('Error parsing test config:', e);
            window.location.href = 'select-test.html';
        }
    },
    
    loadQuestionsFromFile: function() {
        console.log('Loading questions from file for course:', this.course.id);
        
        // Map course ID to filename
        const filename = this.getQuestionFileName(this.course.id);
        const filePath = `../storage/questions/${filename}`;
        
        console.log('Fetching from:', filePath);
        
        // Fetch from file with cache busting
        fetch(`${filePath}?t=${Date.now()}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Raw data loaded:', data);
                
                // Extract questions based on file structure
                let allQuestions = [];
                
                // Check the structure of your JSON files
                if (Array.isArray(data)) {
                    // If file is directly an array of questions
                    allQuestions = data;
                    console.log('File is direct array of questions');
                } else if (data.questions && Array.isArray(data.questions)) {
                    // If file has { "questions": [...] } structure
                    allQuestions = data.questions;
                    console.log('File has questions property');
                } else if (data[this.course.id] && Array.isArray(data[this.course.id])) {
                    // If file has { "MTS101": [...] } structure
                    allQuestions = data[this.course.id];
                    console.log(`File has course ID ${this.course.id} property`);
                } else {
                    // Try to find any array in the object
                    for (let key in data) {
                        if (Array.isArray(data[key])) {
                            allQuestions = data[key];
                            console.log(`Found questions array under key: ${key}`);
                            break;
                        }
                    }
                }
                
                console.log(`Total questions found: ${allQuestions.length}`);
                
                if (allQuestions.length === 0) {
                    throw new Error('No questions found in file');
                }
                
                // Log first question to see structure
                console.log('Sample question:', allQuestions[0]);
                
                // Filter by topics if needed
                let availableQuestions = allQuestions;
                if (this.config.source === 'topics' && this.config.topics && this.config.topics.length > 0) {
                    console.log('Filtering by topics:', this.config.topics);
                    availableQuestions = allQuestions.filter(q => {
                        // Check various possible topic field names
                        const questionTopic = q.topic || q.topicId || q.topic_name || q.subject || '';
                        return this.config.topics.includes(questionTopic);
                    });
                    console.log(`Filtered to ${availableQuestions.length} questions`);
                }
                
                if (availableQuestions.length === 0) {
                    throw new Error('No questions available for selected topics');
                }
                
                // Shuffle and select required number
                const shuffled = this.shuffleArray([...availableQuestions]);
                this.questions = shuffled.slice(0, this.config.questionCount);
                
                console.log(`Selected ${this.questions.length} questions for test`);
                
                if (this.questions.length === 0) {
                    throw new Error('No questions selected');
                }
                
                // Format questions consistently
                this.formatQuestions();
                
                // Initialize answers array
                this.answers = new Array(this.questions.length).fill(null);
                this.flagged = new Array(this.questions.length).fill(false);
                
                // Setup and render
                this.setupEventListeners();
                this.renderQuestion();
                this.renderNavigator();
                this.startTimer();
                
            })
            .catch(error => {
                console.error('Error loading questions:', error);
                Utils.showMessage('Failed to load questions: ' + error.message, 'error');
                
                // Redirect back after showing error
                setTimeout(() => {
                    window.location.href = 'select-test.html';
                }, 2000);
            });
    },
    
    formatQuestions: function() {
        // Ensure all questions have the expected format
        this.questions = this.questions.map((q, index) => {
            // Handle options
            let options = [];
            if (Array.isArray(q.options)) {
                options = q.options;
            } else if (Array.isArray(q.choices)) {
                options = q.choices;
            } else if (q.option1) {
                options = [q.option1, q.option2, q.option3, q.option4].filter(opt => opt);
            } else {
                options = ['Option A', 'Option B', 'Option C', 'Option D'];
            }
            
            // Handle correct answer
            let correct = 0;
            if (typeof q.correct === 'number') {
                correct = q.correct;
            } else if (typeof q.answer === 'number') {
                correct = q.answer;
            } else if (typeof q.correct === 'string') {
                // Convert A, B, C, D to 0, 1, 2, 3
                const letterMap = { 'A': 0, 'B': 1, 'C': 2, 'D': 3, 'a': 0, 'b': 1, 'c': 2, 'd': 3 };
                correct = letterMap[q.correct] || 0;
            }
            
            return {
                id: index,
                question: q.question || q.text || 'Question not available',
                options: options,
                correct: correct,
                topic: q.topic || q.topicId || 'general',
                explanation: q.explanation || q.exp || ''
            };
        });
    },
    
    getQuestionFileName: function(courseId) {
        const files = {
            'MTS101': 'mathematics.json',
            'PHY101': 'physics.json',
            'STA111': 'statistics.json',
            'CSC101': 'computer.json',
            'GNS103': 'literacy.json'
        };
        return files[courseId] || courseId.toLowerCase() + '.json';
    },
    
    shuffleArray: function(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    },
    
    setupEventListeners: function() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const exitBtn = document.getElementById('exitTestBtn');
        const flagBtn = document.getElementById('flagBtn');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.navigate(-1));
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (this.currentIndex === this.questions.length - 1) {
                    this.submitTest();
                } else {
                    this.navigate(1);
                }
            });
        }
        
        if (exitBtn) {
            exitBtn.addEventListener('click', () => this.confirmExit());
        }
        
        if (flagBtn) {
            flagBtn.addEventListener('click', () => this.toggleFlag());
        }
    },
    
    renderQuestion: function() {
        if (!this.questions || this.questions.length === 0) return;
        
        const question = this.questions[this.currentIndex];
        
        const questionNumber = document.getElementById('questionNumber');
        const questionText = document.getElementById('questionText');
        const optionsContainer = document.getElementById('optionsContainer');
        
        if (questionNumber) questionNumber.textContent = (this.currentIndex + 1) + '.';
        if (questionText) questionText.textContent = question.question;
        
        if (optionsContainer) {
            const options = question.options || [];
            let optionsHtml = '';
            options.forEach((option, index) => {
                const isSelected = this.answers[this.currentIndex] === index;
                const optionText = option || `Option ${String.fromCharCode(65 + index)}`;
                optionsHtml += `
                    <div class="option-card ${isSelected ? 'selected' : ''}" data-option-index="${index}">
                        <div class="option-letter">${String.fromCharCode(65 + index)}</div>
                        <div class="option-text">${optionText}</div>
                    </div>
                `;
            });
            optionsContainer.innerHTML = optionsHtml;
            
            document.querySelectorAll('.option-card').forEach(option => {
                option.addEventListener('click', (e) => {
                    const index = parseInt(option.dataset.optionIndex);
                    this.selectOption(index);
                });
            });
        }
        
        const flagBtn = document.getElementById('flagBtn');
        if (flagBtn) {
            if (this.flagged[this.currentIndex]) {
                flagBtn.classList.add('flagged');
                flagBtn.innerHTML = '<i class="fas fa-flag"></i><span>Flagged</span>';
            } else {
                flagBtn.classList.remove('flagged');
                flagBtn.innerHTML = '<i class="fas fa-flag"></i><span>Flag for Review</span>';
            }
        }
        
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) {
            prevBtn.disabled = this.currentIndex === 0;
        }
        
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            if (this.currentIndex === this.questions.length - 1) {
                nextBtn.innerHTML = '<span>Submit</span><i class="fas fa-check"></i>';
                nextBtn.classList.add('submit-btn');
            } else {
                nextBtn.innerHTML = '<span>Next</span><i class="fas fa-arrow-right"></i>';
                nextBtn.classList.remove('submit-btn');
            }
        }
        
        this.renderNavigator();
    },
    
    renderNavigator: function() {
        const grid = document.getElementById('navigatorGrid');
        if (!grid) return;
        
        let html = '';
        
        for (let i = 0; i < this.questions.length; i++) {
            let status = '';
            if (i === this.currentIndex) status = 'current';
            else if (this.flagged[i]) status = 'flagged';
            else if (this.answers[i] !== null) status = 'answered';
            
            html += `<div class="nav-item-bottom ${status}" data-question-index="${i}">${i + 1}</div>`;
        }
        
        grid.innerHTML = html;
        
        document.querySelectorAll('.nav-item-bottom').forEach(item => {
            item.addEventListener('click', (e) => {
                const index = parseInt(item.dataset.questionIndex);
                this.goToQuestion(index);
            });
        });
    },
    
    selectOption: function(optionIndex) {
        this.answers[this.currentIndex] = optionIndex;
        this.renderQuestion();
    },
    
    toggleFlag: function() {
        this.flagged[this.currentIndex] = !this.flagged[this.currentIndex];
        this.renderQuestion();
    },
    
    navigate: function(direction) {
        const newIndex = this.currentIndex + direction;
        if (newIndex >= 0 && newIndex < this.questions.length) {
            this.currentIndex = newIndex;
            this.renderQuestion();
        }
    },
    
    goToQuestion: function(index) {
        if (index >= 0 && index < this.questions.length) {
            this.currentIndex = index;
            this.renderQuestion();
        }
    },
    
    startTimer: function() {
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            const minutes = Math.floor(this.timeRemaining / 60);
            const seconds = this.timeRemaining % 60;
            
            const timer = document.getElementById('timer');
            if (timer) {
                timer.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            if (this.timeRemaining <= 0) {
                clearInterval(this.timerInterval);
                this.submitTest();
            }
        }, 1000);
    },
    
    submitTest: function() {
        clearInterval(this.timerInterval);
        
        const timeTaken = Math.floor((Date.now() - this.startTime) / 1000);
        const score = this.calculateScore();
        const total = this.questions.length;
        const percentage = Math.round((score / total) * 100);
        
        const testResult = {
            id: Utils.generateId(),
            courseId: this.course.id,
            courseName: this.course.name,
            score: score,
            total: total,
            percentage: percentage,
            timeTaken: timeTaken,
            date: new Date().toISOString(),
            answers: this.answers,
            questions: this.questions,
            config: {
                source: this.config.source,
                topics: this.config.topics,
                questionCount: this.config.questionCount
            }
        };
        
        // Save to localStorage (only results, not questions)
        StorageManager.saveTest(testResult);
        
        // Store current result for display
        localStorage.setItem('venus_current_result', JSON.stringify(testResult));
        
        // Clean up
        localStorage.removeItem('venus_test_config');
        
        // Redirect to results page
        window.location.href = 'submit-test.html';
    },
    
    calculateScore: function() {
        let score = 0;
        this.questions.forEach((q, index) => {
            const userAnswer = this.answers[index];
            if (userAnswer === q.correct) {
                score++;
            }
        });
        return score;
    },
    
    confirmExit: function() {
        if (confirm('Exit test? Your progress will be lost.')) {
            clearInterval(this.timerInterval);
            localStorage.removeItem('venus_test_config');
            window.location.href = 'select-test.html';
        }
    },
    
    displayResults: function(result) {
        const container = document.getElementById('resultContainer');
        if (!container) return;
        
        const headerClass = result.percentage >= 70 ? 'success' : (result.percentage >= 50 ? 'warning' : 'danger');
        const iconClass = result.percentage >= 70 ? 'fa-trophy' : (result.percentage >= 50 ? 'fa-smile' : 'fa-frown');
        const titleText = result.percentage >= 70 ? 'Excellent!' : (result.percentage >= 50 ? 'Good Job!' : 'Keep Practicing!');
        
        let html = `
            <div class="result-header ${headerClass}">
                <div class="result-icon">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="result-title">
                    <h2>${titleText}</h2>
                    <div class="score-display">
                        <span class="score">${result.percentage}%</span>
                        <span class="score-detail">${result.score}/${result.total} correct</span>
                    </div>
                </div>
            </div>

            <div class="result-stats">
                <div class="stat-item">
                    <i class="fas fa-book"></i>
                    <div class="stat-content">
                        <span class="stat-label">Course</span>
                        <span class="stat-value">${result.courseId}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <div class="stat-content">
                        <span class="stat-label">Time Taken</span>
                        <span class="stat-value">${Utils.formatTime(result.timeTaken)}</span>
                    </div>
                </div>
            </div>

            <div class="result-actions">
                <a href="select-test.html" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Take Another Test
                </a>
                <a href="history.html" class="btn btn-primary">
                    <i class="fas fa-history"></i> View Test History
                </a>
                <a href="index.html" class="btn btn-primary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>

            <div class="review-section">
                <h3><i class="fas fa-search"></i> Review Answers</h3>
        `;
        
        result.questions.forEach((q, index) => {
            const userAnswer = result.answers[index];
            const isCorrect = userAnswer === q.correct;
            const options = q.options || [];
            
            html += `
                <div class="review-item ${isCorrect ? 'correct' : 'incorrect'}">
                    <div class="review-question">
                        <span class="question-number">Q${index + 1}.</span>
                        ${q.question}
                    </div>
                    <div class="review-answers">
                        <div class="user-answer">
                            <span class="label">Your answer:</span>
                            <span class="value ${isCorrect ? 'text-success' : 'text-danger'}">
                                ${userAnswer !== null && options[userAnswer] ? options[userAnswer] : 'Not answered'}
                            </span>
                        </div>
                        ${!isCorrect ? `
                            <div class="correct-answer">
                                <span class="label">Correct answer:</span>
                                <span class="value text-success">${options[q.correct]}</span>
                            </div>
                        ` : ''}
                        <div class="explanation">
                            <i class="fas fa-info-circle"></i>
                            ${q.explanation || 'No explanation available'}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
};

// Initialize ONLY when on take-test.html
if (window.location.pathname.includes('take-test.html')) {
    console.log('Take test page detected, initializing TestManager');
    document.addEventListener('DOMContentLoaded', () => {
        TestManager.init();
    });
}