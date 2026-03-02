const StorageManager = {
  init: function() {
    if (!localStorage.getItem('venus_settings')) {
      const defaultSettings = {
        theme: 'dark',
        fontSize: 'medium'
      };
      localStorage.setItem('venus_settings', JSON.stringify(defaultSettings));
    }
    
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
    
    this.applyFontSizeToHtml(settings.fontSize);
  },
  
  applyFontSizeToHtml: function(size) {
    const fontSize = size || this.getSettings().fontSize || 'medium';
    
    document.documentElement.classList.remove('font-size-small', 'font-size-medium', 'font-size-large');
    
    document.documentElement.classList.add(`font-size-${fontSize}`);
    
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
    
    this.updateStats(testData);
    
    return testData;
  },
  
  updateStats: function(testResult) {
    const settings = this.getSettings();
    
    if (!settings.stats) {
      settings.stats = {
        totalTests: 0,
        averageScore: 0,
        bestScore: 0,
        bestCourse: 'N/A'
      };
    }
    
    const stats = settings.stats;
    
    stats.totalTests++;
    
    const oldTotal = stats.averageScore * (stats.totalTests - 1);
    stats.averageScore = Math.round((oldTotal + testResult.percentage) / stats.totalTests);
    
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

StorageManager.init();

document.addEventListener('DOMContentLoaded', function() {
  const settings = StorageManager.getSettings();
  StorageManager.applyFontSizeToHtml(settings.fontSize);
});