const SettingsManager = {
    init: function() {
        this.loadCurrentUsername();
    },
    
    loadCurrentUsername: function() {
        const profile = StorageManager.getProfile();
        const currentUsername = document.getElementById('currentUsername');
        if (profile && currentUsername) {
            currentUsername.value = profile.username;
        }
    },
    
    updateUsername: function() {
        const newUsername = document.getElementById('newUsername').value.trim();
        
        if (!newUsername) {
            Utils.showMessage('Username cannot be empty!', 'error');
            return;
        }
        
        if (newUsername.length < 3) {
            Utils.showMessage('Username must be at least 3 characters!', 'error');
            return;
        }
        
        if (newUsername.length > 50) {
            Utils.showMessage('Username too long!', 'error');
            return;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(newUsername)) {
            Utils.showMessage('Username can only contain letters, numbers, and underscores!', 'error');
            return;
        }
        
        const profile = StorageManager.getProfile();
        profile.username = newUsername;
        StorageManager.saveProfile(profile);
        
        const currentUsername = document.getElementById('currentUsername');
        if (currentUsername) {
            currentUsername.value = newUsername;
        }
        
        document.getElementById('newUsername').value = '';
        Utils.showMessage('Username updated successfully!', 'success');
    },
    
    saveTheme: function(theme) {
        const profile = StorageManager.getProfile();
        profile.settings = profile.settings || {};
        profile.settings.theme = theme;
        StorageManager.saveProfile(profile);
    },
    
    clearAllData: function() {
        const confirmed = StorageManager.clearAllData();
        if (confirmed) {
            Utils.showMessage('All data cleared! The page will refresh.', 'success');
            setTimeout(() => window.location.reload(), 2000);
        }
    }
};