document.addEventListener('DOMContentLoaded', function() {
    
  const passwordFields = document.querySelectorAll('input[type="password"]');
  passwordFields.forEach(field => {
    if (field.parentElement.classList.contains('password-wrapper')) return;
    
    const wrapper = document.createElement('div');
    wrapper.className = 'password-wrapper';
    wrapper.style.position = 'relative';
    field.parentNode.insertBefore(wrapper, field);
    wrapper.appendChild(field);
        
    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    toggleBtn.className = 'password-toggle';
    toggleBtn.style.position = 'absolute';
    toggleBtn.style.right = '10px';
    toggleBtn.style.top = '50%';
    toggleBtn.style.transform = 'translateY(-50%)';
    toggleBtn.style.background = 'none';
    toggleBtn.style.border = 'none';
    toggleBtn.style.color = '#bb86fc';
    toggleBtn.style.cursor = 'pointer';
    toggleBtn.style.zIndex = '10';
        
    toggleBtn.addEventListener('click', function() {
      if (field.type === 'password') {
        field.type = 'text';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        field.type = 'password';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
        
    wrapper.appendChild(toggleBtn);
  });
    
  const messages = document.querySelectorAll('.error, .success');
  messages.forEach(msg => {
    setTimeout(() => {
      msg.style.transition = 'opacity 0.5s';
      msg.style.opacity = '0';
      setTimeout(() => {
        if (msg.parentNode) {
          msg.remove();
        }
      }, 500);
    }, 5000);
  });
  
  const tables = document.querySelectorAll('.test-history-table table, .leaderboard-table');
  tables.forEach(table => {
    const wrapper = document.createElement('div');
    wrapper.className = 'table-responsive';
    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);
  });
});

function toggleMenu() {
  const menu = document.getElementById('menuDropdown');
  const hamburger = document.querySelector('.hamburger');
  if (menu && hamburger) {
    menu.classList.toggle('show');
    hamburger.classList.toggle('active');
  }
}

function showMessage(message, type) {
  const messageDiv = document.createElement('div');
  messageDiv.className = type;
  messageDiv.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${message}`;
    
  const container = document.querySelector('.container');
  if (container) {
    container.insertBefore(messageDiv, container.firstChild);
    
    setTimeout(() => {
      messageDiv.style.transition = 'opacity 0.5s';
      messageDiv.style.opacity = '0';
      setTimeout(() => {
        if (messageDiv.parentNode) {
          messageDiv.remove();
        }
      }, 500);
    }, 3000);
  }
}

function logout() {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = '/api/logout';
  }
  return false;
}

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}

function autoSaveTest(courseId, questionIndex, answers) {
  const testData = {
    courseId: courseId,
    questionIndex: questionIndex,
    answers: answers,
    timestamp: new Date().getTime()
  };
  localStorage.setItem('venus_cbt_test', JSON.stringify(testData));
}

function loadSavedTest() {
  const saved = localStorage.getItem('venus_cbt_test');
  if (saved) {
    const testData = JSON.parse(saved);
    if (new Date().getTime() - testData.timestamp < 24 * 60 * 60 * 1000) {
      return testData;
    }
  }
  return null;
}

function clearSavedTest() {
  localStorage.removeItem('venus_cbt_test');
}