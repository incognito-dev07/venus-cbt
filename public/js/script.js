document.addEventListener('DOMContentLoaded', function() {
    
  const currentLocation = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('.nav-links a:not(.logout-btn)');
  navLinks.forEach(link => {
    if (link.getAttribute('href') === currentLocation) {
      link.classList.add('active');
    }
  });
    
  const registerForm = document.querySelector('form[action*="register"]');
  if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
      const password = document.querySelector('input[name="password"]').value;
      const confirm = document.querySelector('input[name="confirm_password"]').value;
            
      if (password !== confirm) {
        e.preventDefault();
        showMessage('Passwords do not match!', 'error');
      } else if (password.length < 6) {
        e.preventDefault();
        showMessage('Password must be at least 6 characters!', 'error');
      }
    });
  }
    
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
    
  const imageInput = document.getElementById('profileImageInput');
  if (imageInput) {
    imageInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 5 * 1024 * 1024) {
          showMessage('Image size must be less than 5MB!', 'error');
          this.value = '';
          return;
        }
        
        if (!file.type.match('image.*')) {
          showMessage('Only image files are allowed!', 'error');
          this.value = '';
          return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
          const profileImage = document.getElementById('profileImage');
          if (profileImage && profileImage.tagName === 'IMG') {
            profileImage.src = e.target.result;
          } else {
            const wrapper = document.querySelector('.profile-image-wrapper');
            if (wrapper) {
              const defaultAvatar = document.querySelector('.default-avatar');
              if (defaultAvatar) {
                const newImg = document.createElement('img');
                newImg.src = e.target.result;
                newImg.alt = 'Profile';
                newImg.className = 'profile-image profile-image-preview';
                newImg.id = 'profileImage';
                wrapper.replaceChild(newImg, defaultAvatar);
              }
            }
          }
        };
        reader.readAsDataURL(file);
        
        document.getElementById('imageUploadForm').submit();
      }
    });
  }
  
  document.addEventListener('click', function(event) {
    const menu = document.getElementById('menuDropdown');
    const hamburger = document.querySelector('.hamburger');
    
    if (menu && hamburger && !menu.contains(event.target) && !hamburger.contains(event.target)) {
      menu.classList.remove('show');
      hamburger.classList.remove('active');
    }
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

function startTimer(duration, display) {
  let timer = duration, minutes, seconds;
  const interval = setInterval(function () {
    minutes = parseInt(timer / 60, 10);
    seconds = parseInt(timer % 60, 10);

    minutes = minutes < 10 ? "0" + minutes : minutes;
    seconds = seconds < 10 ? "0" + seconds : seconds;

    if (display) {
      display.textContent = minutes + ":" + seconds;
    }

    if (--timer < 0) {
      clearInterval(interval);
      const submitBtn = document.querySelector('.btn-success');
      if (submitBtn && submitBtn.textContent.includes('Submit')) {
        submitBtn.click();
      }
    }
  }, 1000);
  return interval;
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

document.addEventListener('keydown', function(e) {
  if (!window.location.pathname.includes('take-test')) return;
  
  if (e.key >= '1' && e.key <= '4') {
    e.preventDefault();
    const option = parseInt(e.key) - 1;
    const optionCards = document.querySelectorAll('.option-card');
    if (optionCards[option]) {
      optionCards[option].click();
    }
  }
  
  if (e.key === 'ArrowLeft') {
    e.preventDefault();
    const prevBtn = document.querySelector('.prev-btn');
    if (prevBtn && !prevBtn.disabled) {
      prevBtn.click();
    }
  }
  
  if (e.key === 'ArrowRight') {
    e.preventDefault();
    const nextBtn = document.querySelector('.next-btn');
    if (nextBtn) {
      nextBtn.click();
    }
  }
  
  if (e.key.toLowerCase() === 'f') {
    e.preventDefault();
    const flagBtn = document.querySelector('.fa-flag');
    if (flagBtn) {
      const btn = flagBtn.closest('button');
      if (btn) btn.click();
    }
  }
});