const UserManager = {
    init: function() {
        this.loadStats();
        this.setupThemeToggle();
    },
    
    loadStats: function() {
        const stats = StorageManager.getStats();
        
        // Update stats display
        const totalTests = document.getElementById('totalTests');
        const avgScore = document.getElementById('avgScore');
        const bestScore = document.getElementById('bestScore');
        const bestCourse = document.getElementById('bestCourse');
        
        if (totalTests) totalTests.textContent = stats.totalTests || 0;
        if (avgScore) avgScore.textContent = (stats.averageScore || 0) + '%';
        if (bestScore) bestScore.textContent = (stats.bestScore || 0) + '%';
        if (bestCourse) bestCourse.textContent = stats.bestCourse || 'N/A';
    },
    
    setupThemeToggle: function() {
        const themeSwitch = document.getElementById('themeSwitch');
        if (!themeSwitch) return;
        
        // Set initial state
        const settings = StorageManager.getSettings();
        themeSwitch.checked = settings.theme === 'light';
        
        themeSwitch.addEventListener('change', function(e) {
            const isLightMode = this.checked;
            const theme = isLightMode ? 'light' : 'dark';
            
            const themeOn = document.querySelector('.theme-on');
            const themeOff = document.querySelector('.theme-off');
            
            if (themeOn) themeOn.classList.toggle('active', !isLightMode);
            if (themeOff) themeOff.classList.toggle('active', isLightMode);
            
            if (isLightMode) {
                document.body.classList.add('light-mode');
            } else {
                document.body.classList.remove('light-mode');
            }
            
            const settings = StorageManager.getSettings();
            settings.theme = theme;
            StorageManager.saveSettings(settings);
            
            // Update cookie for backward compatibility
            const date = new Date();
            date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.cookie = `theme=${theme}; path=/; expires=${date.toUTCString()}; SameSite=Lax`;
        });
    },
    
    clearAllData: function() {
        const confirmed = StorageManager.clearAllData();
        if (confirmed) {
            this.loadStats(); // Refresh stats display
            alert('All test history cleared!');
        }
    }
};