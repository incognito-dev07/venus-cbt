const TestManager = {
    course: null,
    questions: null,
    answers: [],
    flagged: [],
    currentIndex: 0,
    timeRemaining: 0,
    timerInterval: null,
    startTime: null,
    
    init: function(data) {
        this.course = data.course;
        this.questions = data.questions;
        this.timeRemaining = data.timeLimit;
        this.startTime = Date.now();
        
        this.answers = new Array(this.questions.length).fill(null);
        this.flagged = new Array(this.questions.length).fill(false);
        
        // Set up event listeners
        this.setupEventListeners();
        
        this.renderQuestion();
        this.renderNavigator();
        this.startTimer();
    },
    
    setupEventListeners: function() {
        // Previous button
        document.getElementById('prevBtn').addEventListener('click', () => {
            this.navigate(-1);
        });
        
        // Next/Submit button
        document.getElementById('nextBtn').addEventListener('click', () => {
            if (this.currentIndex === this.questions.length - 1) {
                console.log('Submit button clicked');
                this.submitTest();
            } else {
                this.navigate(1);
            }
        });
        
        // Exit button
        document.getElementById('exitTestBtn').addEventListener('click', () => {
            this.confirmExit();
        });
        
        // Flag button
        document.getElementById('flagBtn').addEventListener('click', () => {
            this.toggleFlag();
        });
    },
    
    renderQuestion: function() {
        const question = this.questions[this.currentIndex];
        document.getElementById('questionNumber').textContent = (this.currentIndex + 1) + '.';
        document.getElementById('questionText').textContent = question.question;
        
        let optionsHtml = '';
        question.options.forEach((option, index) => {
            const isSelected = this.answers[this.currentIndex] === index;
            optionsHtml += `
                <div class="option-card ${isSelected ? 'selected' : ''}" data-option-index="${index}">
                    <div class="option-letter">${String.fromCharCode(65 + index)}</div>
                    <div class="option-text">${option}</div>
                </div>
            `;
        });
        document.getElementById('optionsContainer').innerHTML = optionsHtml;
        
        // Add click listeners to options
        document.querySelectorAll('.option-card').forEach(option => {
            option.addEventListener('click', (e) => {
                const index = parseInt(option.dataset.optionIndex);
                this.selectOption(index);
            });
        });
        
        const flagBtn = document.getElementById('flagBtn');
        if (this.flagged[this.currentIndex]) {
            flagBtn.classList.add('flagged');
            flagBtn.innerHTML = '<i class="fas fa-flag"></i><span>Flagged</span>';
        } else {
            flagBtn.classList.remove('flagged');
            flagBtn.innerHTML = '<i class="fas fa-flag"></i><span>Flag for Review</span>';
        }
        
        document.getElementById('prevBtn').disabled = this.currentIndex === 0;
        
        const nextBtn = document.getElementById('nextBtn');
        if (this.currentIndex === this.questions.length - 1) {
            nextBtn.innerHTML = '<span>Submit</span><i class="fas fa-check"></i>';
            nextBtn.classList.add('submit-btn');
        } else {
            nextBtn.innerHTML = '<span>Next</span><i class="fas fa-arrow-right"></i>';
            nextBtn.classList.remove('submit-btn');
        }
        
        this.renderNavigator();
    },
    
    renderNavigator: function() {
        const grid = document.getElementById('navigatorGrid');
        let html = '';
        
        for (let i = 0; i < this.questions.length; i++) {
            let status = '';
            if (i === this.currentIndex) status = 'current';
            else if (this.flagged[i]) status = 'flagged';
            else if (this.answers[i] !== null) status = 'answered';
            
            html += `<div class="nav-item-bottom ${status}" data-question-index="${i}">${i + 1}</div>`;
        }
        
        grid.innerHTML = html;
        
        // Add click listeners to navigator items
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
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (this.timeRemaining <= 0) {
                clearInterval(this.timerInterval);
                this.submitTest();
            }
        }, 1000);
    },
    
    submitTest: function() {
    console.log('submitTest called');
    clearInterval(this.timerInterval);
    
    const timeTaken = Math.floor((Date.now() - this.startTime) / 1000);
    const score = this.calculateScore();
    const total = this.questions.length;
    const percentage = Math.round((score / total) * 100);
    
    console.log('Test results:', { score, total, percentage });
    
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
        questions: this.questions
    };
    
    StorageManager.saveTest(testResult);
    this.updateProfileStats(testResult);
    this.checkAchievements(testResult);
    
    // THIS LINE IS CRITICAL - Update streak after test
    const newStreak = StorageManager.checkAndUpdateStreak();
    console.log('🔥 Streak updated:', newStreak);
    
    localStorage.setItem('venus_current_result', JSON.stringify(testResult));
    console.log('Redirecting to submit-test.php');
    window.location.href = 'submit-test.php';
},    
    calculateScore: function() {
        let score = 0;
        this.questions.forEach((q, index) => {
            if (this.answers[index] === q.correct) {
                score++;
            }
        });
        return score;
    },
    
    updateProfileStats: function(testResult) {
        const profile = StorageManager.getProfile();
        const stats = profile.stats;
        
        stats.totalTests++;
        
        // Update average score
        const oldTotal = stats.averageScore * (stats.totalTests - 1);
        stats.averageScore = Math.round((oldTotal + testResult.percentage) / stats.totalTests);
        
        // Update best score
        if (testResult.percentage > stats.bestScore) {
            stats.bestScore = testResult.percentage;
            stats.bestCourse = testResult.courseId;
        }
        
        StorageManager.saveProfile(profile);
    },
    
    checkAchievements: function(testResult) {
    const achievements = StorageManager.getAchievements();
    const profile = StorageManager.getProfile();
    const tests = StorageManager.getTests();
    let newAchievements = [];
    
    // Track achievement progress
    const progressMap = {};
    
    // Check milestone achievements
    achievements.available.forEach(achievement => {
        if (achievement.type === 'milestone') {
            let progress = tests.length;
            
            // Track progress for notifications
            if (progress < achievement.requirement) {
                const remaining = achievement.requirement - progress;
                if (remaining <= 3 && remaining > 0) {
                    progressMap[achievement.name] = remaining;
                }
            }
            
            if (progress >= achievement.requirement) {
                const alreadyEarned = achievements.earned.some(a => a.id === achievement.id);
                if (!alreadyEarned) {
                    const earned = {
                        id: achievement.id,
                        name: achievement.name,
                        description: achievement.description,
                        icon: achievement.icon,
                        earnedAt: new Date().toISOString()
                    };
                    achievements.earned.push(earned);
                    newAchievements.push(earned);
                }
            }
        }
        
        // Performance achievements
        if (achievement.type === 'performance') {
            if (achievement.id === 'perfect_score' && testResult.percentage === 100) {
                const alreadyEarned = achievements.earned.some(a => a.id === achievement.id);
                if (!alreadyEarned) {
                    const earned = {
                        id: achievement.id,
                        name: achievement.name,
                        description: achievement.description,
                        icon: achievement.icon,
                        earnedAt: new Date().toISOString()
                    };
                    achievements.earned.push(earned);
                    newAchievements.push(earned);
                }
            }
            
            if (achievement.id === 'high_achiever' && testResult.percentage >= 90) {
                const alreadyEarned = achievements.earned.some(a => a.id === achievement.id);
                if (!alreadyEarned) {
                    const earned = {
                        id: achievement.id,
                        name: achievement.name,
                        description: achievement.description,
                        icon: achievement.icon,
                        earnedAt: new Date().toISOString()
                    };
                    achievements.earned.push(earned);
                    newAchievements.push(earned);
                }
            }
            
            if (achievement.id === 'scholar' && tests.length >= 5) {
                const avgScore = profile.stats.averageScore;
                if (avgScore >= 80) {
                    const alreadyEarned = achievements.earned.some(a => a.id === achievement.id);
                    if (!alreadyEarned) {
                        const earned = {
                            id: achievement.id,
                            name: achievement.name,
                            description: achievement.description,
                            icon: achievement.icon,
                            earnedAt: new Date().toISOString()
                        };
                        achievements.earned.push(earned);
                        newAchievements.push(earned);
                    }
                }
            }
        }
    });
    
    // Check streak achievements
    const streak = profile.stats.currentStreak;
    achievements.available.forEach(achievement => {
        if (achievement.type === 'streak' && streak >= achievement.requirement) {
            const alreadyEarned = achievements.earned.some(a => a.id === achievement.id);
            if (!alreadyEarned) {
                const earned = {
                    id: achievement.id,
                    name: achievement.name,
                    description: achievement.description,
                    icon: achievement.icon,
                    earnedAt: new Date().toISOString()
                };
                achievements.earned.push(earned);
                newAchievements.push(earned);
            }
        }
    });
    
    StorageManager.saveAchievements(achievements);
    
    // Send notifications for new achievements
    newAchievements.forEach(achievement => {
        if (typeof NotificationManager !== 'undefined') {
            NotificationManager.addAchievementNotification(achievement);
        }
    });
    
    // Send progress notifications
    Object.entries(progressMap).forEach(([achievementName, remaining]) => {
        if (typeof NotificationManager !== 'undefined') {
            NotificationManager.addAchievementProgressNotification(achievementName, remaining);
        }
    });
    
    // Milestone notifications
    if (typeof NotificationManager !== 'undefined') {
        checkMilestoneNotifications(tests.length);
        
        // Perfect score notification
        if (testResult.percentage === 100) {
            NotificationManager.addPerfectScoreNotification(testResult.courseName, testResult.score);
        }
        
        // Check weak subjects
        checkWeakSubjects();
        
        // Check review reminders
        checkReviewReminders();
        
        // Check performance trends
        checkPerformanceTrends();
      }
    },
    
    confirmExit: function() {
        if (confirm('Exit test? Your progress will be lost.')) {
            clearInterval(this.timerInterval);
            window.location.href = 'select-test.php';
        }
    },
    
    displayResults: function(result) {
        const container = document.getElementById('resultContainer');
        
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
                <a href="select-test.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Take Another Test
                </a>
                <a href="history.php" class="btn btn-primary">
                    <i class="fas fa-history"></i> View Test History
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>

            <div class="review-section">
                <h3><i class="fas fa-search"></i> Review Answers</h3>
        `;
        
        result.questions.forEach((q, index) => {
            const userAnswer = result.answers[index];
            const isCorrect = userAnswer === q.correct;
            
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
                                ${userAnswer !== null ? q.options[userAnswer] : 'Not answered'}
                            </span>
                        </div>
                        ${!isCorrect ? `
                            <div class="correct-answer">
                                <span class="label">Correct answer:</span>
                                <span class="value text-success">${q.options[q.correct]}</span>
                            </div>
                        ` : ''}
                        <div class="explanation">
                            <i class="fas fa-info-circle"></i>
                            ${q.explanation}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
};