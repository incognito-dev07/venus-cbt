const StudyManager = {
  notes: null,
  bookmarks: [],
  recentTopics: [],
  currentTopic: null,
  
  init: function(studyNotes) {
    console.log('StudyManager initializing with notes:', studyNotes);
    this.notes = studyNotes;
    this.loadBookmarks();
    this.loadRecent();
    this.renderSubjects();
    this.setupSearch();
    this.updateBookmarkCount();
    this.setupEventDelegation();
    this.setupBackButtonHandler();
  },
  
  setupBackButtonHandler: function() {
    // Handle browser back button
    window.addEventListener('popstate', (event) => {
      const studyView = document.getElementById('studyView');
      const browseView = document.getElementById('browseView');
      
      if (studyView && studyView.style.display !== 'none') {
        event.preventDefault();
        this.backToBrowse();
        
        // Push state again to handle next back press
        history.pushState({study: true}, '', window.location.pathname);
      }
    });
    
    // Initial push state
    history.pushState({study: true}, '', window.location.pathname);
  },
  
  setupEventDelegation: function() {
    // Use event delegation for topic items
    document.addEventListener('click', (e) => {
      const topicItem = e.target.closest('.topic-item');
      
      if (topicItem) {
        e.preventDefault();
        e.stopPropagation();
        
        const subjectId = topicItem.dataset.subject;
        const topicId = topicItem.dataset.topic;
        const subjectName = topicItem.dataset.subjectName;
        const topicName = topicItem.dataset.topicName;
        
        console.log('Topic clicked:', { subjectId, topicId });
        
        if (subjectId && topicId) {
          this.loadTopic(subjectId, topicId, subjectName, topicName);
        }
      }
    });
    
    // Close search when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-container')) {
        this.hideSearchResults();
      }
    });
  },
  
  renderSubjects: function() {
    const container = document.getElementById('subjectsAccordion');
    if (!container) {
      console.error('Subjects accordion container not found');
      return;
    }
    
    if (!this.notes) {
      console.error('No study notes available');
      container.innerHTML = '<div class="error" style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle"></i> Failed to load study materials</div>';
      return;
    }
    
    console.log('Rendering subjects:', Object.keys(this.notes));
    let html = '';
    
    for (const subjectId in this.notes) {
      const subject = this.notes[subjectId];
      if (!subject) continue;
      
      html += `
        <div class="subject-block" data-subject="${subjectId}">
          <div class="subject-header" onclick="StudyManager.toggleSubject('${subjectId}')">
            <div class="subject-title">
              <div class="subject-icon">
                <i class="fas ${subject.icon || 'fa-book'}"></i>
              </div>
              <div>
                <h3>${this.escapeHtml(subject.name)}</h3>
                <span class="topic-count">${Object.keys(subject.topics || {}).length} topics</span>
              </div>
            </div>
            <i class="fas fa-chevron-down arrow-icon" id="arrow-${subjectId}"></i>
          </div>

          <div class="topics-wrapper" id="topics-${subjectId}" style="display: none;">`;
      
      for (const topicId in subject.topics) {
        const topic = subject.topics[topicId];
        
        html += `
          <div class="topic-item" 
               data-subject="${subjectId}" 
               data-topic="${topicId}"
               data-subject-name="${this.escapeHtml(subject.name)}"
               data-topic-name="${this.escapeHtml(topic.name)}">
            <i class="fas fa-file-alt"></i>
            <span>${this.escapeHtml(topic.name)}</span>
            <i class="fas fa-arrow-right"></i>
          </div>`;
      }
      
      html += `</div></div>`;
    }
    
    container.innerHTML = html;
    console.log('Subjects rendered successfully');
  },
  
  toggleSubject: function(subjectId) {
    console.log('Toggling subject:', subjectId);
    const subjectBlock = document.querySelector(`[data-subject="${subjectId}"]`);
    const topicsDiv = document.getElementById(`topics-${subjectId}`);
    
    if (!topicsDiv) return;
    
    if (topicsDiv.style.display === 'none' || topicsDiv.style.display === '') {
      topicsDiv.style.display = 'block';
      if (subjectBlock) subjectBlock.classList.add('expanded');
    } else {
      topicsDiv.style.display = 'none';
      if (subjectBlock) subjectBlock.classList.remove('expanded');
    }
  },
  
  loadTopic: function(subjectId, topicId, subjectName, topicName) {
    console.log('Loading topic:', { subjectId, topicId });
    
    if (!this.notes) {
      console.error('No study notes available');
      return;
    }
    
    const subject = this.notes[subjectId];
    if (!subject) {
      console.error('Subject not found:', subjectId);
      return;
    }
    
    const topic = subject.topics[topicId];
    if (!topic) {
      console.error('Topic not found:', topicId);
      return;
    }
    
    this.currentTopic = {
      subjectId, topicId,
      subjectName: subjectName || subject.name,
      topicName: topicName || topic.name,
      content: topic.content
    };
    
    this.addToRecent(this.currentTopic);
    
    // Switch views
    const browseView = document.getElementById('browseView');
    const studyView = document.getElementById('studyView');
    
    if (browseView) browseView.style.display = 'none';
    if (studyView) studyView.style.display = 'flex';
    
    // Update breadcrumb
    const breadcrumbSubject = document.getElementById('breadcrumbSubject');
    const breadcrumbTopic = document.getElementById('breadcrumbTopic');
    
    if (breadcrumbSubject) breadcrumbSubject.textContent = this.currentTopic.subjectName;
    if (breadcrumbTopic) breadcrumbTopic.textContent = this.currentTopic.topicName;
    
    // Load content
    const studyContent = document.getElementById('studyContent');
    if (studyContent) {
      studyContent.innerHTML = `
        <div class="mobile-content">
          ${topic.content}
        </div>
      `;
    }
    
    this.updateBookmarkButton();
    this.hideSearchResults();
  },
  
  backToBrowse: function() {
    console.log('Returning to browse view');
    const browseView = document.getElementById('browseView');
    const studyView = document.getElementById('studyView');
    
    if (browseView) browseView.style.display = 'block';
    if (studyView) studyView.style.display = 'none';
    this.currentTopic = null;
  },
  
  setupSearch: function() {
    const searchInput = document.getElementById('mobileSearch');
    if (!searchInput) {
      console.error('Search input not found');
      return;
    }
    
    console.log('Setting up search');
    
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.trim();
      if (query.length < 2) {
        this.hideSearchResults();
        return;
      }
      this.performSearch(query);
    });
    
    searchInput.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        searchInput.value = '';
        this.hideSearchResults();
      }
    });
  },
  
  performSearch: function(query) {
    if (!this.notes) return;
    
    const results = [];
    const searchTerm = query.toLowerCase();
    
    for (const subjectId in this.notes) {
      const subject = this.notes[subjectId];
      if (!subject || !subject.topics) continue;
      
      for (const topicId in subject.topics) {
        const topic = subject.topics[topicId];
        
        if (topic.name.toLowerCase().includes(searchTerm)) {
          results.push({
            subjectId, topicId,
            subjectName: subject.name,
            topicName: topic.name
          });
        }
      }
    }
    
    this.displaySearchResults(results.slice(0, 5));
  },
  
  displaySearchResults: function(results) {
    const container = document.getElementById('mobileSearchResults');
    if (!container) return;
    
    if (results.length === 0) {
      container.style.display = 'none';
      return;
    }
    
    let html = '';
    results.forEach(result => {
      html += `
        <div class="search-result-item" onclick="StudyManager.loadTopic('${result.subjectId}', '${result.topicId}', '${this.escapeHtml(result.subjectName)}', '${this.escapeHtml(result.topicName)}')">
          <div class="subject">${this.escapeHtml(result.subjectName)}</div>
          <div class="title">${this.escapeHtml(result.topicName)}</div>
        </div>
      `;
    });
    
    container.innerHTML = html;
    container.style.display = 'block';
  },
  
  hideSearchResults: function() {
    const container = document.getElementById('mobileSearchResults');
    if (container) {
      container.style.display = 'none';
    }
  },
  
  toggleBookmark: function() {
    if (!this.currentTopic) return;
    
    const exists = this.bookmarks.some(b => 
      b.subjectId === this.currentTopic.subjectId &&
      b.topicId === this.currentTopic.topicId
    );
    
    if (exists) {
      this.bookmarks = this.bookmarks.filter(b => 
        !(b.subjectId === this.currentTopic.subjectId &&
          b.topicId === this.currentTopic.topicId)
      );
    } else {
      this.bookmarks.push({
        ...this.currentTopic,
        bookmarkedAt: new Date().toISOString()
      });
    }
    
    this.saveBookmarks();
    this.updateBookmarkButton();
    this.updateBookmarkCount();
  },
  
  updateBookmarkButton: function() {
    const btn = document.getElementById('studyBookmarkBtn');
    if (!btn || !this.currentTopic) return;
    
    const exists = this.bookmarks.some(b => 
      b.subjectId === this.currentTopic.subjectId &&
      b.topicId === this.currentTopic.topicId
    );
    
    btn.innerHTML = exists ? '<i class="fas fa-bookmark"></i>' : '<i class="far fa-bookmark"></i>';
    btn.classList.toggle('bookmarked', exists);
  },
  
  loadBookmarks: function() {
    const saved = localStorage.getItem('venus_study_bookmarks');
    if (saved) {
      try {
        this.bookmarks = JSON.parse(saved);
        console.log('Loaded bookmarks:', this.bookmarks.length);
      } catch (e) {
        console.error('Error loading bookmarks:', e);
        this.bookmarks = [];
      }
    }
  },
  
  saveBookmarks: function() {
    localStorage.setItem('venus_study_bookmarks', JSON.stringify(this.bookmarks));
  },
  
  updateBookmarkCount: function() {
    const badges = document.querySelectorAll('#mobileBookmarkCount');
    badges.forEach(badge => {
      if (badge) {
        badge.textContent = this.bookmarks.length;
        badge.style.display = this.bookmarks.length > 0 ? 'flex' : 'none';
      }
    });
  },
  
  showBookmarks: function() {
    if (this.bookmarks.length === 0) {
      alert('No bookmarks yet');
      return;
    }
    
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.onclick = () => {
      overlay.remove();
      modal.remove();
    };
    
    const modal = document.createElement('div');
    modal.className = 'bookmarks-modal';
    
    let html = '<h3><i class="fas fa-bookmark"></i> Your Bookmarks</h3>';
    
    this.bookmarks.sort((a, b) => new Date(b.bookmarkedAt) - new Date(a.bookmarkedAt)).forEach((bookmark, index) => {
      html += `
        <div class="bookmark-item">
          <div class="bookmark-info" onclick="StudyManager.loadTopic('${bookmark.subjectId}', '${bookmark.topicId}', '${this.escapeHtml(bookmark.subjectName)}', '${this.escapeHtml(bookmark.topicName)}'); document.querySelectorAll('.modal-overlay, .bookmarks-modal').forEach(el => el.remove());">
            <div class="bookmark-subject">${this.escapeHtml(bookmark.subjectName)}</div>
            <div class="bookmark-title">${this.escapeHtml(bookmark.topicName)}</div>
          </div>
          <button class="action-btn" onclick="event.stopPropagation(); StudyManager.removeBookmark(${index});">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      `;
    });
    
    modal.innerHTML = html;
    document.body.appendChild(overlay);
    document.body.appendChild(modal);
  },
  
  removeBookmark: function(index) {
    this.bookmarks.splice(index, 1);
    this.saveBookmarks();
    this.updateBookmarkCount();
    this.updateBookmarkButton();
    
    // Refresh modal
    const modal = document.querySelector('.bookmarks-modal');
    const overlay = document.querySelector('.modal-overlay');
    if (modal && overlay) {
      modal.remove();
      overlay.remove();
      this.showBookmarks();
    }
  },
  
  addToRecent: function(topic) {
    if (!topic) return;
    
    // Remove if already exists
    this.recentTopics = this.recentTopics.filter(t => 
      !(t.subjectId === topic.subjectId && 
        t.topicId === topic.topicId)
    );
    
    // Add to beginning
    this.recentTopics.unshift(topic);
    
    // Keep only last 2
    if (this.recentTopics.length > 2) {
      this.recentTopics.pop();
    }
    
    this.saveRecent();
    this.updateRecentList();
  },
  
  loadRecent: function() {
    const saved = localStorage.getItem('venus_study_recent');
    if (saved) {
      try {
        this.recentTopics = JSON.parse(saved);
        console.log('Loaded recent topics:', this.recentTopics.length);
        this.updateRecentList();
      } catch (e) {
        console.error('Error loading recent:', e);
        this.recentTopics = [];
      }
    }
  },
  
  saveRecent: function() {
    localStorage.setItem('venus_study_recent', JSON.stringify(this.recentTopics));
  },
  
  updateRecentList: function() {
    const list = document.getElementById('recentList');
    if (!list) return;
    
    if (this.recentTopics.length === 0) {
      list.innerHTML = `
        <div class="empty-recent">
          <i class="fas fa-book-open"></i>
          <p>No recent topics</p>
        </div>
      `;
      return;
    }
    
    let html = '';
    this.recentTopics.forEach(topic => {
      html += `
        <div class="recent-item" onclick="StudyManager.loadTopic('${topic.subjectId}', '${topic.topicId}', '${this.escapeHtml(topic.subjectName)}', '${this.escapeHtml(topic.topicName)}')">
          <div class="subject">${this.escapeHtml(topic.subjectName)}</div>
          <div class="topic">${this.escapeHtml(topic.topicName)}</div>
        </div>
      `;
    });
    
    list.innerHTML = html;
  },
  
  downloadPDF: function() {
    if (!this.currentTopic) return;
    
    const element = document.querySelector('.mobile-content');
    if (!element || typeof html2pdf === 'undefined') {
      alert('PDF download is not available');
      return;
    }
    
    const filename = `${this.currentTopic.subjectName}_${this.currentTopic.topicName}.pdf`.replace(/\s+/g, '_');
    
    html2pdf().from(element).set({
      margin: 0.5,
      filename: filename,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    }).save();
  },
  
  shareTopic: function() {
    if (!this.currentTopic) return;
    
    if (navigator.share) {
      navigator.share({
        title: this.currentTopic.topicName,
        text: `Studying ${this.currentTopic.topicName} from ${this.currentTopic.subjectName}`,
        url: window.location.href
      }).catch(() => {});
    } else {
      navigator.clipboard.writeText(this.currentTopic.topicName);
      alert('Topic name copied to clipboard!');
    }
  },
  
  scrollToTop: function() {
    const content = document.querySelector('.study-content');
    if (content) {
      content.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }
  },
  
  escapeHtml: function(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
};

// Make StudyManager globally available
window.StudyManager = StudyManager;