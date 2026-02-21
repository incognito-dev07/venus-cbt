const NotificationManager = {
    init: function() {
        this.renderNotifications();
        StorageManager.updateNotificationBadge();
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
        notifications.forEach(notification => {
            html += this.renderNotificationItem(notification);
        });
        
        listContainer.innerHTML = html;
    },
    
    renderNotificationItem: function(notification) {
        const statusClass = notification.read ? 'read' : 'unread';
        
        return `
            <div class="notification-item ${statusClass}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="notification-content">
                    <p><strong>${notification.title}</strong></p>
                    <p>${notification.message}</p>
                    <span class="notification-time">${Utils.formatDate(notification.createdAt)}</span>
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
    
    addAchievementNotification: function(achievement) {
        StorageManager.addNotification({
            type: 'achievement',
            title: 'Achievement Unlocked!',
            message: `You earned "${achievement.name}"`,
            icon: achievement.icon
        });
    },
};