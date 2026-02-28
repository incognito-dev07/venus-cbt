const StorageManager = {
    init: function() {
        // Initialize user settings if not exists
        if (!localStorage.getItem('venus_settings')) {
            const defaultSettings = {
                theme: 'dark',
                fontSize: 'medium'
            };
            localStorage.setItem('venus_settings', JSON.stringify(defaultSettings));
        }
        
        // Initialize tests if not exists
        if (!localStorage.getItem('venus_tests')) {
            localStorage.setItem('venus_tests', JSON.stringify([]));
        }
    },
    
    getSettings: function() {
        const settings = localStorage.getItem('venus_settings');
        return settings ? JSON.parse(settings) : { 
            theme: 'dark',
            fontSize: 'medium'
        };
    },
    
    saveSettings: function(settings) {
        localStorage.setItem('venus_settings', JSON.stringify(settings));
        
        // When settings change, update the font size immediately
        this.applyFontSizeToHtml(settings.fontSize);
    },
    
    applyFontSizeToHtml: function(size) {
        const fontSize = size || this.getSettings().fontSize || 'medium';
        
        // Remove existing classes
        document.documentElement.classList.remove('font-size-small', 'font-size-medium', 'font-size-large');
        
        // Add new class
        document.documentElement.classList.add(`font-size-${fontSize}`);
        
        // Also set inline style as backup
        document.documentElement.style.fontSize = 
            fontSize === 'small' ? '14px' : 
            fontSize === 'large' ? '18px' : '16px';
    },
    
    getTests: function() {
        const tests = localStorage.getItem('venus_tests');
        return tests ? JSON.parse(tests) : [];
    },
    
    saveTest: function(testData) {
        const tests = this.getTests();
        tests.push(testData);
        localStorage.setItem('venus_tests', JSON.stringify(tests));
        
        // Update statistics
        this.updateStats(testData);
        
        return testData;
    },
    
    updateStats: function(testResult) {
        const settings = this.getSettings();
        
        // Initialize stats if not exists
        if (!settings.stats) {
            settings.stats = {
                totalTests: 0,
                averageScore: 0,
                bestScore: 0,
                bestCourse: 'N/A'
            };
        }
        
        const stats = settings.stats;
        
        // Update total tests
        stats.totalTests++;
        
        // Update average score
        const oldTotal = stats.averageScore * (stats.totalTests - 1);
        stats.averageScore = Math.round((oldTotal + testResult.percentage) / stats.totalTests);
        
        // Update best score and course
        if (testResult.percentage > stats.bestScore) {
            stats.bestScore = testResult.percentage;
            stats.bestCourse = testResult.courseId;
        }
        
        this.saveSettings(settings);
    },
    
    getStats: function() {
        const settings = this.getSettings();
        return settings.stats || {
            totalTests: 0,
            averageScore: 0,
            bestScore: 0,
            bestCourse: 'N/A'
        };
    },
    
    clearAllData: function() {
        if (confirm('WARNING: This will delete all your test history. This cannot be undone. Continue?')) {
            localStorage.removeItem('venus_tests');
            
            // Reset stats but keep theme and other settings
            const settings = this.getSettings();
            settings.stats = {
                totalTests: 0,
                averageScore: 0,
                bestScore: 0,
                bestCourse: 'N/A'
            };
            this.saveSettings(settings);
            
            return true;
        }
        return false;
    }
};

// Initialize on load
StorageManager.init();

// Apply font size on page load (but the inline script already did it)
document.addEventListener('DOMContentLoaded', function() {
    const settings = StorageManager.getSettings();
    StorageManager.applyFontSizeToHtml(settings.fontSize);
});