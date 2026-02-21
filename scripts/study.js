const StudyManager = {
  notes: null,
  bookmarks: [],
  recentTopics: [],
  currentTopic: null,
  
  init: function(studyNotes) {
    this.notes = studyNotes;
    this.loadBookmarks();
    this.loadRecent();
    this.setupEventListeners();
    this.setupSearch();
    this.updateBookmarkCount();
  },
  
  setupEventListeners: function() {
    // Use event delegation for subtopic items
    document.addEventListener('click', (e) => {
      const subtopicItem = e.target.closest('.subtopic-item');
      
      if (subtopicItem) {
        e.preventDefault();
        e.stopPropagation();
        
        const subjectId = subtopicItem.dataset.subject;
        const topicId = subtopicItem.dataset.topic;
        const subtopicId = subtopicItem.dataset.subtopic;
        const subjectName = subtopicItem.dataset.subjectName;
        const topicName = subtopicItem.dataset.topicName;
        const subtopicName = subtopicItem.dataset.subtopicName;
        
        if (subjectId && topicId && subtopicId) {
          this.loadTopic(subjectId, topicId, subtopicId, subjectName, topicName, subtopicName);
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
  
  // ===== NAVIGATION =====
  toggleSubject: function(subjectId) {
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
  
  toggleTopic: function(subjectId, topicId, event) {
    if (event) event.stopPropagation();
    
    const subtopicsDiv = document.getElementById(`subtopics-${subjectId}-${topicId}`);
    const topicHeader = event?.currentTarget;
    
    if (!subtopicsDiv) return;
    
    if (subtopicsDiv.style.display === 'none' || subtopicsDiv.style.display === '') {
      subtopicsDiv.style.display = 'block';
      if (topicHeader) topicHeader.classList.add('expanded');
    } else {
      subtopicsDiv.style.display = 'none';
      if (topicHeader) topicHeader.classList.remove('expanded');
    }
  },
  
  // ===== LOAD TOPIC INTO STUDY VIEW =====
  loadTopic: function(subjectId, topicId, subtopicId, subjectName, topicName, subtopicName) {
    // Get content from notes
    const subject = this.notes[subjectId];
    if (!subject) return;
    
    const topic = subject.topics[topicId];
    if (!topic) return;
    
    const subtopic = topic.subtopics[subtopicId];
    if (!subtopic) return;
    
    this.currentTopic = {
      subjectId, topicId, subtopicId,
      subjectName: subjectName || subject.name,
      topicName: topicName || topic.name,
      subtopicName: subtopicName || subtopic.name,
      content: subtopic.content
    };
    
    // Add to recent (max 3)
    this.addToRecent(this.currentTopic);
    
    // Switch views
    document.getElementById('browseView').style.display = 'none';
    document.getElementById('studyView').style.display = 'flex';
    
    // Update breadcrumb (format: Subject > Topic >)
    document.getElementById('breadcrumbSubject').textContent = this.currentTopic.subjectName;
    document.getElementById('breadcrumbTopic').textContent = this.currentTopic.topicName;
    
    // Load content
    document.getElementById('studyContent').innerHTML = `
      <div class="mobile-content">
        <h1>${this.currentTopic.subtopicName}</h1>
        ${subtopic.content}
      </div>
    `;
    
    // Update bookmark button
    this.updateBookmarkButton();
    
    // Hide search results
    this.hideSearchResults();
  },
  
  backToBrowse: function() {
    document.getElementById('browseView').style.display = 'block';
    document.getElementById('studyView').style.display = 'none';
    this.currentTopic = null;
  },
  
  // ===== SEARCH =====
  setupSearch: function() {
    const searchInput = document.getElementById('mobileSearch');
    if (!searchInput) return;
    
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
    const results = [];
    const searchTerm = query.toLowerCase();
    
    for (const subjectId in this.notes) {
      const subject = this.notes[subjectId];
      for (const topicId in subject.topics) {
        const topic = subject.topics[topicId];
        for (const subtopicId in topic.subtopics) {
          const subtopic = topic.subtopics[subtopicId];
          
          if (subtopic.name.toLowerCase().includes(searchTerm)) {
            results.push({
              subjectId, topicId, subtopicId,
              subjectName: subject.name,
              topicName: topic.name,
              subtopicName: subtopic.name
            });
          }
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
        <div class="search-result-item" onclick="StudyManager.loadTopic('${result.subjectId}', '${result.topicId}', '${result.subtopicId}', '${result.subjectName}', '${result.topicName}', '${result.subtopicName}')">
          <div class="subject">${result.subjectName}</div>
          <div class="title">${result.subtopicName}</div>
          <div class="path">${result.topicName}</div>
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
  
  // ===== BOOKMARKS =====
  toggleBookmark: function() {
    if (!this.currentTopic) return;
    
    const exists = this.bookmarks.some(b => 
      b.subjectId === this.currentTopic.subjectId &&
      b.topicId === this.currentTopic.topicId &&
      b.subtopicId === this.currentTopic.subtopicId
    );
    
    if (exists) {
      this.bookmarks = this.bookmarks.filter(b => 
        !(b.subjectId === this.currentTopic.subjectId &&
          b.topicId === this.currentTopic.topicId &&
          b.subtopicId === this.currentTopic.subtopicId)
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
      b.topicId === this.currentTopic.topicId &&
      b.subtopicId === this.currentTopic.subtopicId
    );
    
    if (exists) {
      btn.innerHTML = '<i class="fas fa-bookmark"></i>';
      btn.classList.add('bookmarked');
    } else {
      btn.innerHTML = '<i class="far fa-bookmark"></i>';
      btn.classList.remove('bookmarked');
    }
  },
  
  loadBookmarks: function() {
    const saved = localStorage.getItem('venus_study_bookmarks');
    if (saved) {
      try {
        this.bookmarks = JSON.parse(saved);
      } catch (e) {
        this.bookmarks = [];
      }
    }
  },
  
  saveBookmarks: function() {
    localStorage.setItem('venus_study_bookmarks', JSON.stringify(this.bookmarks));
  },
  
  updateBookmarkCount: function() {
    const badges = document.querySelectorAll('#mobileBookmarkCount, .bookmarks-count');
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
    
    this.bookmarks.forEach((bookmark, index) => {
      html += `
        <div class="bookmark-item">
          <div class="bookmark-info" onclick="StudyManager.loadTopic('${bookmark.subjectId}', '${bookmark.topicId}', '${bookmark.subtopicId}', '${bookmark.subjectName}', '${bookmark.topicName}', '${bookmark.subtopicName}'); document.querySelectorAll('.modal-overlay, .bookmarks-modal').forEach(el => el.remove());">
            <div class="bookmark-subject">${bookmark.subjectName}</div>
            <div class="bookmark-title">${bookmark.topicName} > ${bookmark.subtopicName}</div>
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
  
  // ===== RECENT TOPICS (Max 3) =====
  addToRecent: function(topic) {
    if (!topic) return;
    
    // Remove if already exists
    this.recentTopics = this.recentTopics.filter(t => 
      !(t.subjectId === topic.subjectId && 
        t.topicId === topic.topicId && 
        t.subtopicId === topic.subtopicId)
    );
    
    // Add to beginning
    this.recentTopics.unshift(topic);
    
    // Keep only last 3
    if (this.recentTopics.length > 3) {
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
        this.updateRecentList();
      } catch (e) {
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
        <div class="recent-item" onclick="StudyManager.loadTopic('${topic.subjectId}', '${topic.topicId}', '${topic.subtopicId}', '${topic.subjectName}', '${topic.topicName}', '${topic.subtopicName}')">
          <div class="subject">${topic.subjectName}</div>
          <div class="topic">${topic.topicName} > ${topic.subtopicName}</div>
        </div>
      `;
    });
    
    list.innerHTML = html;
  },
  
  // ===== PDF DOWNLOAD =====
  downloadPDF: function() {
    if (!this.currentTopic) return;
    
    const element = document.querySelector('.mobile-content');
    if (!element) return;
    
    const filename = `${this.currentTopic.subjectName}_${this.currentTopic.subtopicName}.pdf`.replace(/\s+/g, '_');
    
    html2pdf().from(element).set({
      margin: 0.5,
      filename: filename,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    }).save();
  },
  
  // ===== SHARE =====
  shareTopic: function() {
    if (!this.currentTopic) return;
    
    if (navigator.share) {
      navigator.share({
        title: this.currentTopic.subtopicName,
        text: `Studying ${this.currentTopic.subtopicName} from ${this.currentTopic.subjectName}`,
        url: window.location.href
      }).catch(() => {});
    } else {
      navigator.clipboard.writeText(this.currentTopic.subtopicName);
    }
  },
  
  // ===== UTILITIES =====
  scrollToTop: function() {
    const content = document.querySelector('.study-content');
    if (content) {
      content.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }
  }
};

// Make StudyManager globally available
window.StudyManager = StudyManager;

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  if (typeof studyNotes !== 'undefined') {
    StudyManager.init(studyNotes);
  }
});