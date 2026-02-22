const ProfileManager = {
    init: function() {
        this.loadProfile();
    },
    
    loadProfile: function() {
        const profile = StorageManager.getProfile();
        if (!profile) return;
        
        const displayUsername = document.getElementById('displayUsername');
        if (displayUsername) {
            displayUsername.textContent = profile.username;
        }
        
        // Update streak with proper pluralization
        const streakValue = document.getElementById('streakValue');
        const streakText = document.getElementById('streakText');
        
        if (streakValue && streakText) {
            const currentStreak = profile.stats?.currentStreak || 0;
            streakValue.textContent = currentStreak;
            streakText.textContent = currentStreak === 1 ? 'day' : 'days';
        }
        
        const bioDisplay = document.getElementById('bioDisplay');
        if (bioDisplay) {
            bioDisplay.textContent = profile.bio || 'No bio yet';
        }
        
        // Format join date
        const joinDate = document.getElementById('joinDate');
        if (joinDate) {
            const date = new Date(profile.createdAt);
            joinDate.textContent = date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
        }
        
        // Update stats
        const testsTaken = document.getElementById('testsTaken');
        const avgScore = document.getElementById('avgScore');
        const bestCourse = document.getElementById('bestCourse');
        const bestScore = document.getElementById('bestScore');
        
        if (testsTaken) testsTaken.textContent = profile.stats?.totalTests || 0;
        if (avgScore) avgScore.textContent = (profile.stats?.averageScore || 0) + '%';
        if (bestCourse) bestCourse.textContent = profile.stats?.bestCourse || 'N/A';
        if (bestScore) bestScore.textContent = (profile.stats?.bestScore || 0) + '%';
        
        // Update avatar
        if (profile.avatar) {
            const profileImage = document.getElementById('profileImage');
            const defaultAvatar = document.getElementById('defaultAvatar');
            
            if (profileImage) {
                profileImage.src = profile.avatar;
                profileImage.style.display = 'block';
            }
            if (defaultAvatar) {
                defaultAvatar.style.display = 'none';
            }
        }
        
        this.loadAchievements();
    },
    
    loadAchievements: function() {
        const achievements = StorageManager.getAchievements();
        const grid = document.getElementById('achievementsGrid');
        
        if (!grid) return;
        
        if (!achievements || achievements.earned.length === 0) {
            return;
        }
        
        let html = '';
        achievements.earned.sort((a, b) => new Date(b.earnedAt) - new Date(a.earnedAt)).forEach(achievement => {
            html += `
                <div class="achievement-card earned">
                    <div class="achievement-header">
                        <i class="fas ${achievement.icon}"></i>
                        <h4>${achievement.name}</h4>
                    </div>
                    <p>${achievement.description}</p>
                    <small style="color: var(--text-secondary); font-size: 0.7rem; margin-top: 0.5rem;">
                        Earned: ${Utils.formatDate(achievement.earnedAt)}
                    </small>
                </div>
            `;
        });
        
        if (html) {
            grid.innerHTML = html;
        }
    },
    
    updateBio: function(bio) {
        if (!bio || bio.trim() === '') {
            bio = 'No bio yet';
        }
        
        if (bio.length > 200) {
            Utils.showMessage('Bio must be 200 characters or less!', 'error');
            return;
        }
        
        const profile = StorageManager.getProfile();
        profile.bio = bio;
        StorageManager.saveProfile(profile);
        
        const bioDisplay = document.getElementById('bioDisplay');
        if (bioDisplay) {
            bioDisplay.textContent = bio;
        }
        
        Utils.showMessage('Bio updated successfully!', 'success');
    },
    
    updateAvatar: function(imageData) {
        const profile = StorageManager.getProfile();
        profile.avatar = imageData;
        StorageManager.saveProfile(profile);
        
        const profileImage = document.getElementById('profileImage');
        const defaultAvatar = document.getElementById('defaultAvatar');
        
        if (profileImage) {
            profileImage.src = imageData;
            profileImage.style.display = 'block';
        }
        if (defaultAvatar) {
            defaultAvatar.style.display = 'none';
        }
        
        Utils.showMessage('Profile image updated successfully!', 'success');
    }
};