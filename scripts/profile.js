const ProfileManager = {
    init: function() {
        this.loadProfile();
    },
    
    loadProfile: function() {
    const profile = StorageManager.getProfile();
    if (!profile) return;
    
    document.getElementById('displayUsername').textContent = profile.username;
    
    // Update streak with proper pluralization
    const streakValue = document.getElementById('streakValue');
    const streakText = document.getElementById('streakText');
    
    // Make sure stats exist
    if (!profile.stats) {
        profile.stats = {
            currentStreak: 0,
            longestStreak: 0,
            totalTests: 0,
            averageScore: 0,
            bestScore: 0,
            bestCourse: 'N/A'
        };
    }
    
    const currentStreak = profile.stats.currentStreak || 0;
    streakValue.textContent = currentStreak;
    
    // Proper pluralization
    if (currentStreak === 1) {
        streakText.textContent = 'day';
    } else {
        streakText.textContent = 'days';
    }
    
    document.getElementById('bioDisplay').textContent = profile.bio || 'No bio yet';
    
    // Format join date
    const joinDate = new Date(profile.createdAt);
    const formattedDate = joinDate.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
    document.getElementById('joinDate').textContent = formattedDate;
    
    document.getElementById('testsTaken').textContent = profile.stats.totalTests || 0;
    document.getElementById('avgScore').textContent = (profile.stats.averageScore || 0) + '%';
    document.getElementById('bestCourse').textContent = profile.stats.bestCourse || 'N/A';
    document.getElementById('bestScore').textContent = (profile.stats.bestScore || 0) + '%';
    
    if (profile.avatar) {
        document.getElementById('profileImage').src = profile.avatar;
        document.getElementById('profileImage').style.display = 'block';
        document.getElementById('defaultAvatar').style.display = 'none';
    }
    
    this.loadAchievements();
},    
    loadAchievements: function() {
        const achievements = StorageManager.getAchievements();
        const grid = document.getElementById('achievementsGrid');
        
        if (!achievements || achievements.earned.length === 0) {
            return;
        }
        
        let html = '';
        achievements.earned.forEach(achievement => {
            html += `
                <div class="achievement-card earned">
                    <div class="achievement-header">
                        <i class="fas ${achievement.icon}"></i>
                        <h4>${achievement.name}</h4>
                    </div>
                    <p>${achievement.description}</p>
                </div>
            `;
        });
        
        if (html) {
            grid.innerHTML = html;
        }
    },
    
    updateBio: function(bio) {
    // Add validation
    if (!bio || bio.trim() === '') {
        bio = 'No bio yet'; // Set default if empty
    }
    
    if (bio.length > 200) {
        Utils.showMessage('Bio must be 200 characters or less!', 'error');
        return;
    }
    
    const profile = StorageManager.getProfile();
    profile.bio = bio;
    StorageManager.saveProfile(profile);
    document.getElementById('bioDisplay').textContent = bio;
    Utils.showMessage('Bio updated successfully!', 'success');
    
    // Clear the input field
    document.getElementById('bioInput').value = '';
    },
    
    updateAvatar: function(imageData) {
        const profile = StorageManager.getProfile();
        profile.avatar = imageData;
        StorageManager.saveProfile(profile);
        
        document.getElementById('profileImage').src = imageData;
        document.getElementById('profileImage').style.display = 'block';
        document.getElementById('defaultAvatar').style.display = 'none';
        
        Utils.showMessage('Profile image updated successfully!', 'success');
    }
};