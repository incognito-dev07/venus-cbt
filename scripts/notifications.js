const NotificationManager = {
    init: function() {
        this.renderNotifications();
        StorageManager.updateNotificationBadge();
        this.setupReminders();
        this.checkDailyReminders();
    },
    
    renderNotifications: function() {
        const notifications = StorageManager.getNotifications();
        const listContainer = document.getElementById('notificationsList');
        const emptyState = document.getElementById('emptyNotifications');
        const markAllBtn = document.getElementById('markAllReadBtn');
        const clearAllBtn = document.getElementById('clearAllBtn');
        
        if (!listContainer) return;
        
        if (notifications.length === 0) {
            listContainer.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            if (markAllBtn) markAllBtn.style.display = 'none';
            if (clearAllBtn) clearAllBtn.style.display = 'none';
            return;
        }
        
        if (emptyState) emptyState.style.display = 'none';
        if (markAllBtn) markAllBtn.style.display = 'inline-block';
        if (clearAllBtn) clearAllBtn.style.display = 'inline-block';
        
        let html = '';
        // Sort by date, newest first
        notifications.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)).forEach(notification => {
            html += this.renderNotificationItem(notification);
        });
        
        listContainer.innerHTML = html;
    },
    
    renderNotificationItem: function(notification) {
        const statusClass = notification.read ? 'read' : 'unread';
        
        return `
            <div class="notification-item ${statusClass}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="fas ${notification.icon || 'fa-bell'}"></i>
                </div>
                <div class="notification-content">
                    <p><strong>${notification.title}</strong></p>
                    <p>${notification.message}</p>
                    <span class="notification-time">${Utils.formatDateTime(notification.createdAt)}</span>
                </div>
                ${!notification.read ? `
                    <div class="mark-read" onclick="NotificationManager.markAsRead('${notification.id}')">
                        <i class="fas fa-circle"></i>
                    </div>
                ` : ''}
            </div>
        `;
    },
    
    markAsRead: function(notificationId) {
        StorageManager.markNotificationAsRead(notificationId);
        this.renderNotifications();
    },
    
    markAllAsRead: function() {
        StorageManager.markAllNotificationsAsRead();
        this.renderNotifications();
    },
    
    clearAll: function() {
        if (confirm('Clear all notifications?')) {
            StorageManager.clearAllNotifications();
            this.renderNotifications();
        }
    },
    
    // ===== NOTIFICATION TYPES =====
    
    // 1. Achievement Notifications (Fixed)
    addAchievementNotification: function(achievement) {
        StorageManager.addNotification({
            type: 'achievement_earned',
            title: 'New Achievement Unlocked!',
            message: `You earned "${achievement.name}" - ${achievement.description}`,
            icon: achievement.icon || 'fa-medal'
        });
    },
    
    // Achievement Progress Notifications
    addAchievementProgressNotification: function(achievementName, remainingTests) {
        StorageManager.addNotification({
            type: 'achievement_progress',
            title: 'Almost There!',
            message: `Complete ${remainingTests} more test${remainingTests > 1 ? 's' : ''} to unlock "${achievementName}"`,
            icon: 'fa-trophy'
        });
    },
    
    // 2. Streak Notifications
    addStreakReminder: function(currentStreak) {
        StorageManager.addNotification({
            type: 'streak_reminder',
            title: 'Keep Your Streak Alive!',
            message: `You haven't taken a test today. Complete one to maintain your ${currentStreak}-day streak!`,
            icon: 'fa-fire'
        });
    },
    
    addStreakSavedNotification: function(newStreak) {
        StorageManager.addNotification({
            type: 'streak_saved',
            title: 'Streak Saved!',
            message: `Great job! Your streak is now ${newStreak} day${newStreak > 1 ? 's' : ''}`,
            icon: 'fa-calendar-check'
        });
    },
    
    addStreakLostNotification: function(lostStreak) {
        StorageManager.addNotification({
            type: 'streak_lost',
            title: 'Streak Lost',
            message: `You lost your ${lostStreak}-day streak. Start a new one today!`,
            icon: 'fa-heart-broken'
        });
    },
    
    // 3. Milestone Notifications
    addMilestoneNotification: function(testCount) {
        let message, icon;
        
        if (testCount === 1) {
            message = "You've completed your first test! Great start!";
            icon = 'fa-star';
        } else if (testCount === 5) {
            message = "You've completed 5 tests! You're getting started!";
            icon = 'fa-fire';
        } else if (testCount === 10) {
            message = "You've completed 10 tests! You're on fire!";
            icon = 'fa-dragon';
        } else if (testCount === 25) {
            message = "You've completed 25 tests! You're a veteran!";
            icon = 'fa-crown';
        } else if (testCount === 50) {
            message = "You've completed 50 tests! You're a legend!";
            icon = 'fa-star';
        } else {
            return; // Only notify at specific milestones
        }
        
        StorageManager.addNotification({
            type: 'milestone',
            title: 'Milestone Reached!',
            message: message,
            icon: icon
        });
    },
    
    addPerfectScoreNotification: function(courseName, score) {
        StorageManager.addNotification({
            type: 'perfect',
            title: 'PERFECT SCORE!',
            message: `Amazing! You got 100% on ${courseName}!`,
            icon: 'fa-crown'
        });
    },
    
    // 4. Daily/Weekly Reminders
    addMorningReminder: function() {
        StorageManager.addNotification({
            type: 'reminder',
            title: 'Good Morning!',
            message: 'Start your day with a quick practice test to keep your mind sharp!',
            icon: 'fa-sun'
        });
    },
    
    addEveningReminder: function() {
        const profile = StorageManager.getProfile();
        const today = new Date().toDateString();
        const lastActive = profile.lastActive ? new Date(profile.lastActive).toDateString() : null;
        
        if (lastActive !== today) {
            StorageManager.addNotification({
                type: 'reminder',
                title: 'Don\'t Break Your Streak!',
                message: 'Quick 5-minute test before bed to maintain your streak!',
                icon: 'fa-moon'
            });
        }
    },
    
    addWeeklyReportNotification: function() {
        const tests = StorageManager.getTests();
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
        
        const weekTests = tests.filter(t => new Date(t.date) > oneWeekAgo);
        const weekCount = weekTests.length;
        
        if (weekCount === 0) {
            StorageManager.addNotification({
                type: 'summary',
                title: 'Weekly Report',
                message: 'You didn\'t take any tests this week. Set a goal for next week!',
                icon: 'fa-chart-line'
            });
        } else {
            const avgScore = Math.round(weekTests.reduce((sum, t) => sum + t.percentage, 0) / weekCount);
            StorageManager.addNotification({
                type: 'summary',
                title: 'Weekly Report',
                message: `You took ${weekCount} test${weekCount > 1 ? 's' : ''} this week. Average score: ${avgScore}%`,
                icon: 'fa-chart-line'
            });
        }
    },
    
    // 5. Performance Alerts
    addPerformanceAlert: function(prevAvgScore, newAvgScore, subject) {
        if (newAvgScore < prevAvgScore - 10) {
            StorageManager.addNotification({
                type: 'warning',
                title: 'Performance Alert',
                message: `Your scores dropped in ${subject}. Time to review the basics!`,
                icon: 'fa-exclamation-triangle'
            });
        } else if (newAvgScore > prevAvgScore + 10) {
            StorageManager.addNotification({
                type: 'improvement',
                title: 'Great Improvement!',
                message: `Your ${subject} scores improved by ${newAvgScore - prevAvgScore}% this week!`,
                icon: 'fa-arrow-up'
            });
        }
    },
    
    // 6. Study Recommendations
    addWeakSubjectNotification: function(subjectName, score) {
        StorageManager.addNotification({
            type: 'recommendation',
            title: 'Need More Practice',
            message: `Your weakest subject is ${subjectName} (${score}%). Try a practice test!`,
            icon: 'fa-book-open'
        });
    },
    
    addReviewReminderNotification: function(subjectName, daysSince) {
        StorageManager.addNotification({
            type: 'review',
            title: 'Time to Review',
            message: `It's been ${daysSince} days since your last ${subjectName} test. Refresh your memory!`,
            icon: 'fa-clock'
        });
    },
    
    // 7. Study Material Updates
    addNewContentNotification: function(subjectName, topicName) {
        StorageManager.addNotification({
            type: 'content',
            title: 'New Study Notes!',
            message: `New ${subjectName} topic added: ${topicName}`,
            icon: 'fa-plus-circle'
        });
    },
    
    addStudyTipNotification: function(tip) {
        const tips = [
            'Remember to eliminate wrong answers first',
            'Take short breaks between subjects',
            'Review your incorrect answers to learn from mistakes',
            'Practice daily for better retention',
            'Use the flag feature to review difficult questions',
            'Read questions carefully before answering',
            'Manage your time - don\'t spend too long on one question'
        ];
        
        StorageManager.addNotification({
            type: 'tips',
            title: 'Study Tip',
            message: tips[Math.floor(Math.random() * tips.length)],
            icon: 'fa-lightbulb'
        });
    },
    
    // ===== REMINDER SYSTEM =====
    
    setupReminders: function() {
        // Check every hour
        setInterval(() => this.checkDailyReminders(), 3600000);
    },
    
    checkDailyReminders: function() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = now.getMinutes();
        const lastReminderDate = localStorage.getItem('venus_last_reminder_date');
        const today = now.toDateString();
        
        // Morning reminder (9 AM)
        if (hours === 9 && minutes < 10 && lastReminderDate !== today) {
            this.addMorningReminder();
            localStorage.setItem('venus_last_reminder_date', today);
        }
        
        // Evening reminder (8 PM)
        if (hours === 20 && minutes < 10 && lastReminderDate !== today) {
            this.addEveningReminder();
            localStorage.setItem('venus_last_reminder_date', today);
        }
        
        // Weekly report (Sunday at 6 PM)
        if (now.getDay() === 0 && hours === 18 && minutes < 10) {
            const lastWeekReport = localStorage.getItem('venus_last_week_report');
            const thisWeek = this.getWeekNumber(now);
            
            if (lastWeekReport != thisWeek) {
                this.addWeeklyReportNotification();
                localStorage.setItem('venus_last_week_report', thisWeek);
            }
        }
        
        // Random study tip (every 3 days)
        const lastTipDate = localStorage.getItem('venus_last_tip_date');
        const daysSinceTip = lastTipDate ? Math.floor((now - new Date(lastTipDate)) / (1000 * 60 * 60 * 24)) : 999;
        
        if (daysSinceTip >= 3) {
            this.addStudyTipNotification();
            localStorage.setItem('venus_last_tip_date', now.toISOString());
        }
    },
    
    getWeekNumber: function(date) {
        const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
        const pastDaysOfYear = (date - firstDayOfYear) / 86400000;
        return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
    }
};