const CACHE_NAME = 'venus-cbt-data';
const DATA_CACHE_NAME = 'venus-cbt-data';

// Only cache data files, NOT UI files
const dataUrlsToCache = [
  '/storage/study_notes.json',
  '/storage/courses.json',
  '/storage/mathematics.json',
  '/storage/physics.json',
  '/storage/statistics.json',
  '/storage/computer.json',
  '/storage/literacy.json'
];

// Install service worker
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(DATA_CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching data files');
        return cache.addAll(dataUrlsToCache);
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
          // Keep only our data cache, delete anything else
          if (cacheName !== DATA_CACHE_NAME) {
            console.log('Service Worker: Clearing old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - ONLY cache data files, never UI
self.addEventListener('fetch', event => {
  const url = event.request.url;
  
  // Only handle data file requests
  if (url.includes('/storage/') && url.endsWith('.json')) {
    event.respondWith(
      caches.match(event.request)
        .then(cachedResponse => {
          // Return cached data if available
          if (cachedResponse) {
            return cachedResponse;
          }
          
          // Otherwise fetch from network
          return fetch(event.request)
            .then(response => {
              // Cache the new data
              if (response && response.status === 200) {
                const responseToCache = response.clone();
                caches.open(DATA_CACHE_NAME)
                  .then(cache => {
                    cache.put(event.request, responseToCache);
                  });
              }
              return response;
            })
            .catch(() => {
              // If offline and no cache, return empty data
              return new Response(JSON.stringify({}), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
              });
            });
        })
    );
  }
  // For all other requests (UI, CSS, JS, etc.) - always go to network
  else {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          // If offline and not a data file, just show a simple error
          return new Response('You are offline. Please check your connection.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain' }
          });
        })
    );
  }
});

// Background sync for offline test submissions
self.addEventListener('sync', event => {
  if (event.tag === 'sync-tests') {
    console.log('Service Worker: Syncing offline tests');
    event.waitUntil(syncOfflineTests());
  }
});

// Function to sync offline tests when back online
async function syncOfflineTests() {
  try {
    const cache = await caches.open('offline-tests');
    const requests = await cache.keys();
    
    for (const request of requests) {
      try {
        const response = await cache.match(request);
        const testData = await response.json();
        
        // Send to server (if you had a server)
        // await fetch('/api/save-test', { method: 'POST', body: testData });
        
        // Delete from cache after successful sync
        await cache.delete(request);
      } catch (error) {
        console.log('Failed to sync test:', error);
      }
    }
  } catch (error) {
    console.log('Sync failed:', error);
  }
}