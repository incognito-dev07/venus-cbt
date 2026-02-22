const HistoryManager = {
    tests: [],
    
    init: function() {
        this.tests = StorageManager.getTests();
        this.renderList();
    },
    
    renderList: function() {
        const listContainer = document.getElementById('testsListContainer');
        const emptyState = document.getElementById('emptyState');
        const clearBtn = document.getElementById('clearHistoryBtn');
        
        if (!listContainer) return;
        
        if (this.tests.length === 0) {
            listContainer.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            if (clearBtn) clearBtn.style.display = 'none';
            return;
        }
        
        if (emptyState) emptyState.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'inline-block';
        
        let html = '<div class="tests-list">';
        
        // Sort by date, newest first
        this.tests.sort((a, b) => new Date(b.date) - new Date(a.date)).forEach(test => {
            const scoreClass = test.percentage >= 70 ? 'text-success' : (test.percentage >= 50 ? 'text-warning' : 'text-danger');
            
            html += `
                <div class="test-item" onclick="HistoryManager.viewTest('${test.id}')">
                    <div class="test-item-header">
                        <span class="course-badge">${test.courseId}</span>
                        <span class="test-date">${Utils.formatDate(test.date)}</span>
                    </div>
                    <div class="test-item-body">
                        <div class="test-score">
                            <span class="list-label">Score: </span>
                            <span class="score-value ${scoreClass}">${test.percentage}%</span>
                            <span class="score-detail">(${test.score}/${test.total})</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        listContainer.innerHTML = html;
    },
    
    viewTest: function(testId) {
        const test = this.tests.find(t => t.id === testId);
        if (!test) return;
        
        const listContainer = document.getElementById('testsListContainer');
        const detailsContainer = document.getElementById('testDetailsContainer');
        const clearBtn = document.getElementById('clearHistoryBtn');
        
        if (listContainer) listContainer.style.display = 'none';
        if (detailsContainer) detailsContainer.style.display = 'block';
        if (clearBtn) clearBtn.style.display = 'none';
        
        this.renderTestDetails(test);
    },
    
    renderTestDetails: function(test) {
        const container = document.getElementById('testDetailsContent');
        if (!container) return;
        
        let html = `
            <div class="test-details">
                <div class="test-summary">
                    <div class="summary-item">
                        <span class="label">Course:</span>
                        <span class="value">${test.courseId} - ${test.courseName}</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Date:</span>
                        <span class="value">${Utils.formatDateTime(test.date)}</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Score:</span>
                        <span class="value ${test.percentage >= 70 ? 'text-success' : (test.percentage >= 50 ? 'text-warning' : 'text-danger')}">
                            ${test.percentage}% (${test.score}/${test.total})
                        </span>
                    </div>
                </div>

                <div class="test-questions">
                    <h3>Questions Review</h3>
        `;
        
        test.questions.forEach((q, index) => {
            const userAnswer = test.answers[index];
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
                                ${userAnswer !== null && q.options[userAnswer] ? q.options[userAnswer] : 'Not answered'}
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
                            ${q.explanation || 'No explanation available'}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        container.innerHTML = html;
    },
    
    showList: function() {
        const listContainer = document.getElementById('testsListContainer');
        const detailsContainer = document.getElementById('testDetailsContainer');
        const clearBtn = document.getElementById('clearHistoryBtn');
        
        if (listContainer) listContainer.style.display = 'block';
        if (detailsContainer) detailsContainer.style.display = 'none';
        if (clearBtn) clearBtn.style.display = this.tests.length > 0 ? 'inline-block' : 'none';
    },
    
    clearHistory: function() {
        if (confirm('Clear all test history? This cannot be undone.')) {
            localStorage.setItem('venus_tests', JSON.stringify([]));
            this.tests = [];
            this.renderList();
            this.showList();
            Utils.showMessage('History cleared!', 'success');
        }
    }
};