const StorageManager = {
    init: function() {
        // Initialize user profile if not exists
        if (!localStorage.getItem('venus_profile')) {
            const defaultProfile = {
                username: 'Learner',
                bio: 'Welcome to my profile!',
                avatar: null,
                createdAt: new Date().toISOString(),
                lastActive: null,
                stats: {
                    totalTests: 0,
                    averageScore: 0,
                    bestScore: 0,
                    bestCourse: 'N/A',
                    currentStreak: 0,
                    longestStreak: 0,
                    lastTestDate: null
                },
                settings: {
                    theme: 'dark'
                }
            };
            localStorage.setItem('venus_profile', JSON.stringify(defaultProfile));
        } else {
            // Ensure existing profiles have all required fields
            const profile = this.getProfile();
            let needsUpdate = false;
            
            if (!profile.stats) {
                profile.stats = {
                    totalTests: 0,
                    averageScore: 0,
                    bestScore: 0,
                    bestCourse: 'N/A',
                    currentStreak: 0,
                    longestStreak: 0,
                    lastTestDate: null
                };
                needsUpdate = true;
            }
            
            if (profile.lastActive === undefined) {
                profile.lastActive = null;
                needsUpdate = true;
            }
            
            if (!profile.settings) {
                profile.settings = { theme: 'dark' };
                needsUpdate = true;
            }
            
            if (needsUpdate) {
                this.saveProfile(profile);
            }
        }
        
        // Initialize notifications if not exists
        if (!localStorage.getItem('venus_notifications')) {
            localStorage.setItem('venus_notifications', JSON.stringify([]));
        }
        
        // Initialize achievements if not exists
        if (!localStorage.getItem('venus_achievements')) {
            this.initAchievements();
        }
        
        // Initialize tests if not exists
        if (!localStorage.getItem('venus_tests')) {
            localStorage.setItem('venus_tests', JSON.stringify([]));
        }
        
        this.updateNotificationBadge();
    },
    
    initAchievements: function() {
        const achievements = {
            earned: [],
            available: [
                { id: 'first_test', name: 'First Test', description: 'Completed your first test', icon: 'fa-star', requirement: 1, progress: 0, type: 'milestone' },
                { id: 'getting_started', name: 'Getting Started', description: 'Complete 5 tests', icon: 'fa-fire', requirement: 5, progress: 0, type: 'milestone' },
                { id: 'test_master', name: 'Test Master', description: 'Complete 10 tests', icon: 'fa-dragon', requirement: 10, progress: 0, type: 'milestone' },
                { id: 'veteran', name: 'Veteran', description: 'Complete 25 tests', icon: 'fa-crown', requirement: 25, progress: 0, type: 'milestone' },
                { id: 'legend', name: 'Legend', description: 'Complete 50 tests', icon: 'fa-star', requirement: 50, progress: 0, type: 'milestone' },
                { id: 'perfect_score', name: 'Perfect Score', description: 'Get 100% on any test', icon: 'fa-star', requirement: 100, progress: 0, type: 'performance' },
                { id: 'high_achiever', name: 'High Achiever', description: 'Score 90%+ on any test', icon: 'fa-brain', requirement: 90, progress: 0, type: 'performance' },
                { id: 'scholar', name: 'Scholar', description: 'Achieve 80%+ average across 5 tests', icon: 'fa-graduation-cap', requirement: 80, progress: 0, type: 'performance' },
                { id: 'on_fire', name: 'On Fire', description: '3 day streak', icon: 'fa-fire', requirement: 3, progress: 0, type: 'streak' },
                { id: 'unstoppable', name: 'Unstoppable', description: '7 day streak', icon: 'fa-flame', requirement: 7, progress: 0, type: 'streak' },
                { id: 'dedicated', name: 'Dedicated', description: '30 day streak', icon: 'fa-calendar-check', requirement: 30, progress: 0, type: 'streak' }
            ]
        };
        localStorage.setItem('venus_achievements', JSON.stringify(achievements));
    },
    
    getProfile: function() {
        const profile = localStorage.getItem('venus_profile');
        return profile ? JSON.parse(profile) : null;
    },
    
    saveProfile: function(profile) {
        localStorage.setItem('venus_profile', JSON.stringify(profile));
    },
    
    getTests: function() {
        const tests = localStorage.getItem('venus_tests');
        return tests ? JSON.parse(tests) : [];
    },
    
    saveTest: function(testData) {
        const tests = this.getTests();
        tests.push(testData);
        localStorage.setItem('venus_tests', JSON.stringify(tests));
        return testData;
    },
    
    getNotifications: function() {
        const notifications = localStorage.getItem('venus_notifications');
        return notifications ? JSON.parse(notifications) : [];
    },
    
    saveNotifications: function(notifications) {
        localStorage.setItem('venus_notifications', JSON.stringify(notifications));
        this.updateNotificationBadge();
    },
    
    addNotification: function(notification) {
        const notifications = this.getNotifications();
        notification.id = Utils.generateId();
        notification.read = false;
        notification.createdAt = new Date().toISOString();
        notifications.unshift(notification);
        
        // Keep only last 50 notifications
        if (notifications.length > 50) {
            notifications.pop();
        }
        
        this.saveNotifications(notifications);
        return notification;
    },
    
    markNotificationAsRead: function(notificationId) {
        const notifications = this.getNotifications();
        const index = notifications.findIndex(n => n.id === notificationId);
        if (index !== -1) {
            notifications[index].read = true;
            this.saveNotifications(notifications);
        }
    },
    
    markAllNotificationsAsRead: function() {
        const notifications = this.getNotifications();
        notifications.forEach(n => n.read = true);
        this.saveNotifications(notifications);
    },
    
    clearAllNotifications: function() {
        localStorage.setItem('venus_notifications', JSON.stringify([]));
        this.updateNotificationBadge();
    },
    
    getUnreadNotificationCount: function() {
        const notifications = this.getNotifications();
        return notifications.filter(n => !n.read).length;
    },
    
    updateNotificationBadge: function() {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;
        
        const count = this.getUnreadNotificationCount();
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    },
    
    getAchievements: function() {
        const achievements = localStorage.getItem('venus_achievements');
        return achievements ? JSON.parse(achievements) : null;
    },
    
    saveAchievements: function(achievements) {
        localStorage.setItem('venus_achievements', JSON.stringify(achievements));
    },
    
    checkAndUpdateStreak: function() {
        const profile = this.getProfile();
        if (!profile) return 0;
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const lastActive = profile.lastActive ? new Date(profile.lastActive) : null;
        const oldStreak = profile.stats?.currentStreak || 0;
        
        if (!profile.stats) {
            profile.stats = {
                totalTests: 0,
                averageScore: 0,
                bestScore: 0,
                bestCourse: null,
                currentStreak: 0,
                longestStreak: 0,
                lastTestDate: null
            };
        }
        
        if (lastActive) {
            lastActive.setHours(0, 0, 0, 0);
            const diffTime = today.getTime() - lastActive.getTime();
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 1) {
                profile.stats.currentStreak++;
                // Notify streak saved
                if (typeof NotificationManager !== 'undefined') {
                    NotificationManager.addStreakSavedNotification(profile.stats.currentStreak);
                }
            } else if (diffDays > 1) {
                // Streak lost - notify
                if (oldStreak > 0 && typeof NotificationManager !== 'undefined') {
                    NotificationManager.addStreakLostNotification(oldStreak);
                }
                profile.stats.currentStreak = 1;
            }
        } else {
            profile.stats.currentStreak = 1;
        }
        
        if (profile.stats.currentStreak > profile.stats.longestStreak) {
            profile.stats.longestStreak = profile.stats.currentStreak;
        }
        
        profile.lastActive = new Date().toISOString();
        this.saveProfile(profile);
        
        return profile.stats.currentStreak;
    },
    
    clearAllData: function() {
        if (confirm('WARNING: This will delete all your data including profile, test history, and achievements. This cannot be undone. Continue?')) {
            localStorage.removeItem('venus_profile');
            localStorage.removeItem('venus_tests');
            localStorage.removeItem('venus_notifications');
            localStorage.removeItem('venus_achievements');
            localStorage.removeItem('venus_study_bookmarks');
            localStorage.removeItem('venus_study_recent');
            localStorage.removeItem('venus_viewed_topics');
            this.init();
            return true;
        }
        return false;
    }
};

// Initialize on load
StorageManager.init();