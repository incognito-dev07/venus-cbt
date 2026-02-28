const SettingsManager = {
    init: function() {
        this.loadSettings();
        this.setupEventListeners();
    },
    
    loadSettings: function() {
        const settings = StorageManager.getSettings();
        
        // Theme
        const themeSwitch = document.getElementById('themeSwitch');
        const themeOn = document.querySelector('.theme-on');
        const themeOff = document.querySelector('.theme-off');
        
        if (themeSwitch) {
            themeSwitch.checked = settings.theme === 'light';
            document.body.classList.toggle('light-mode', settings.theme === 'light');
            
            if (themeOn && themeOff) {
                if (settings.theme === 'light') {
                    themeOn.classList.remove('active');
                    themeOff.classList.add('active');
                } else {
                    themeOn.classList.add('active');
                    themeOff.classList.remove('active');
                }
            }
        }
        
        // Font size - update display
        this.updateFontSizeDisplay(settings.fontSize || 'medium');
    },
    
    setupEventListeners: function() {
        // Theme toggle
        const themeSwitch = document.getElementById('themeSwitch');
        if (themeSwitch) {
            themeSwitch.addEventListener('change', (e) => {
                this.toggleTheme(e.target.checked);
            });
        }
    },
    
    toggleTheme: function(isLight) {
        const theme = isLight ? 'light' : 'dark';
        
        if (isLight) {
            document.body.classList.add('light-mode');
        } else {
            document.body.classList.remove('light-mode');
        }
        
        // Update status text
        const themeOn = document.querySelector('.theme-on');
        const themeOff = document.querySelector('.theme-off');
        if (themeOn && themeOff) {
            if (isLight) {
                themeOn.classList.remove('active');
                themeOff.classList.add('active');
            } else {
                themeOn.classList.add('active');
                themeOff.classList.remove('active');
            }
        }
        
        const settings = StorageManager.getSettings();
        settings.theme = theme;
        StorageManager.saveSettings(settings);
    },
    
    increaseFontSize: function() {
        const settings = StorageManager.getSettings();
        const current = settings.fontSize || 'medium';
        
        let newSize = 'medium';
        if (current === 'small') newSize = 'medium';
        else if (current === 'medium') newSize = 'large';
        else newSize = 'large';
        
        settings.fontSize = newSize;
        StorageManager.saveSettings(settings);
        
        // Update display
        this.updateFontSizeDisplay(newSize);
    },
    
    decreaseFontSize: function() {
        const settings = StorageManager.getSettings();
        const current = settings.fontSize || 'medium';
        
        let newSize = 'medium';
        if (current === 'large') newSize = 'medium';
        else if (current === 'medium') newSize = 'small';
        else newSize = 'small';
        
        settings.fontSize = newSize;
        StorageManager.saveSettings(settings);
        
        // Update display
        this.updateFontSizeDisplay(newSize);
    },
    
    updateFontSizeDisplay: function(size) {
        const display = document.getElementById('fontSizeValue');
        if (display) {
            display.textContent = size.charAt(0).toUpperCase() + size.slice(1);
        }
    },
    
    clearAllData: function() {
        if (confirm('WARNING: This will delete all your test history. This cannot be undone. Continue?')) {
            StorageManager.clearAllData();
            Utils.showMessage('All data cleared!', 'success');
        }
    }
};

// Initialize on page load
if (window.location.pathname.includes('settings.html')) {
    document.addEventListener('DOMContentLoaded', () => SettingsManager.init());
}