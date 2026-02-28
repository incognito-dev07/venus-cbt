const ProfileManager = {
    init: function() {
        this.loadStats();
        this.loadAchievements();
    },
    
    loadStats: function() {
        const stats = StorageManager.getStats();
        const tests = StorageManager.getTests();
        
        // Calculate additional stats
        const totalStudyTime = this.calculateTotalStudyTime(tests);
        const { strongest, weakest } = this.calculateTopicInsights(tests);
        
        // Update stats display
        document.getElementById('totalTests').textContent = stats.totalTests || 0;
        document.getElementById('avgScore').textContent = (stats.averageScore || 0) + '%';
        document.getElementById('bestScore').textContent = (stats.bestScore || 0) + '%';
        document.getElementById('bestCourse').textContent = stats.bestCourse || 'N/A';
        document.getElementById('totalStudyTime').textContent = totalStudyTime;
        
        // Update topic insights
        document.getElementById('strongestTopic').textContent = strongest.name;
        document.getElementById('strongestScore').textContent = strongest.score + '%';
        document.getElementById('weakestTopic').textContent = weakest.name;
        document.getElementById('weakestScore').textContent = weakest.score + '%';
    },
    
    calculateTotalStudyTime: function(tests) {
        const totalMinutes = tests.reduce((total, test) => {
            return total + Math.ceil(test.timeTaken / 60);
        }, 0);
        
        if (totalMinutes < 60) {
            return totalMinutes + 'm';
        } else {
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            return hours + 'h ' + minutes + 'm';
        }
    },
    
    calculateTopicInsights: function(tests) {
        const topicScores = {};
        const topicCounts = {};
        
        tests.forEach(test => {
            if (test.questions && test.answers) {
                test.questions.forEach((q, index) => {
                    const topic = q.topic || 'general';
                    const correctIndex = q.correct !== undefined ? q.correct : q.answer;
                    const isCorrect = test.answers[index] === correctIndex;
                    
                    if (!topicScores[topic]) {
                        topicScores[topic] = 0;
                        topicCounts[topic] = 0;
                    }
                    
                    if (isCorrect) {
                        topicScores[topic]++;
                    }
                    topicCounts[topic]++;
                });
            }
        });
        
        let strongest = { name: 'N/A', score: 0 };
        let weakest = { name: 'N/A', score: 100 };
        
        for (const topic in topicCounts) {
            const percentage = Math.round((topicScores[topic] / topicCounts[topic]) * 100);
            const topicName = topic.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            if (percentage > strongest.score) {
                strongest = { name: topicName, score: percentage };
            }
            if (percentage < weakest.score) {
                weakest = { name: topicName, score: percentage };
            }
        }
        
        if (weakest.name === 'N/A') weakest.score = 0;
        
        return { strongest, weakest };
    },
    
    loadAchievements: function() {
        const tests = StorageManager.getTests();
        const stats = StorageManager.getStats();
        const achievements = [];
        
        // Perfect Score Achievement
        const perfectTests = tests.filter(t => t.percentage === 100).length;
        if (perfectTests >= 1) {
            achievements.push({
                icon: 'fa-star',
                title: 'Perfect Score',
                description: 'Got 100% on a test',
                unlocked: true,
                tier: perfectTests >= 5 ? 'gold' : (perfectTests >= 3 ? 'silver' : 'bronze')
            });
        }
        
        // Marathon Achievement
        const totalQuestions = tests.reduce((total, test) => total + test.total, 0);
        if (totalQuestions >= 100) {
            achievements.push({
                icon: 'fa-running',
                title: 'Marathon Runner',
                description: 'Answered 100+ questions',
                unlocked: true,
                tier: totalQuestions >= 500 ? 'gold' : (totalQuestions >= 250 ? 'silver' : 'bronze')
            });
        }
        
        // Consistency Achievement
        if (stats.totalTests >= 10) {
            achievements.push({
                icon: 'fa-calendar-check',
                title: 'Consistent Learner',
                description: 'Took 10+ tests',
                unlocked: true,
                tier: stats.totalTests >= 50 ? 'gold' : (stats.totalTests >= 25 ? 'silver' : 'bronze')
            });
        }
        
        this.renderAchievements(achievements);
    },
    
    renderAchievements: function(achievements) {
        const container = document.getElementById('achievementsGrid');
        if (!container) return;
        
        if (achievements.length === 0) {
            container.innerHTML = `
                <div class="empty-achievements">
                    <i class="fas fa-medal"></i>
                    <p>Complete tests to unlock achievements!</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        achievements.forEach(achievement => {
            html += `
                <div class="achievement-card ${achievement.tier}">
                    <div class="achievement-icon">
                        <i class="fas ${achievement.icon}"></i>
                    </div>
                    <div class="achievement-info">
                        <h4>${achievement.title}</h4>
                        <p>${achievement.description}</p>
                    </div>
                    <div class="achievement-tier ${achievement.tier}">
                        <i class="fas fa-crown"></i>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
};

// Initialize on page load
if (window.location.pathname.includes('profile.html')) {
    document.addEventListener('DOMContentLoaded', () => ProfileManager.init());
}