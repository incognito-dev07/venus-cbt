const CACHE_NAME = 'venus-cbt-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/study.php',
  '/select-test.php',
  '/profile.php',
  '/settings.php',
  '/history.php',
  '/notifications.php',
  '/styles/core.css',
  '/styles/component.css',
  '/styles/pages.css',
  '/styles/responsive.css',
  '/styles/study.css',
  '/scripts/utilities.js',
  '/scripts/storage.js',
  '/scripts/profile.js',
  '/scripts/settings.js',
  '/scripts/history.js',
  '/scripts/notifications.js',
  '/scripts/test.js',
  '/scripts/study.js',
  '/storage/study_notes.json',
  '/storage/courses.json',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2',
  'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'
];

// Install service worker
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching files');
        return cache.addAll(urlsToCache);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate service worker
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Clearing old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  // Skip cross-origin requests like CDN
  if (event.request.url.startsWith(self.location.origin) || 
      event.request.url.includes('cdnjs.cloudflare.com')) {
    
    event.respondWith(
      caches.match(event.request)
        .then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          return fetch(event.request)
            .then(response => {
              // Don't cache if not valid
              if (!response || response.status !== 200 || response.type !== 'basic') {
                return response;
              }
              
              // Clone the response
              const responseToCache = response.clone();
              
              caches.open(CACHE_NAME)
                .then(cache => {
                  cache.put(event.request, responseToCache);
                });
              
              return response;
            })
            .catch(() => {
              // If both cache and network fail, show offline page
              if (event.request.url.includes('.php')) {
                return caches.match('/');
              }
            });
        })
    );
  }
});

// Background sync for when offline
self.addEventListener('sync', event => {
  if (event.tag === 'sync-tests') {
    console.log('Service Worker: Syncing offline tests');
    // Handle syncing offline test results
  }
});