/**
 * DTZ Learning Platform - Service Worker
 * Provides offline functionality and caching
 */

const CACHE_NAME = 'dtz-learning-v1';
const STATIC_CACHE = 'dtz-static-v1';
const DYNAMIC_CACHE = 'dtz-dynamic-v1';

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/frontend/css/style.css',
  '/frontend/css/mobile.css',
  '/frontend/js/auth.js',
  '/frontend/js/api.js',
  '/frontend/js/ui.js',
  '/frontend/dashboard.html',
  '/frontend/learn.html',
  '/frontend/progress.html',
  '/frontend/modelltest.html',
  '/frontend/writing.html',
  '/frontend/speaking.html',
  '/frontend/settings.html',
  '/manifest.json'
];

// CDN resources to cache
const CDN_ASSETS = [
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
  'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiJ-Ek-_0ew.woff2'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[SW] Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .catch(err => console.error('[SW] Static cache failed:', err))
  );
  
  // Cache CDN assets separately
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Caching CDN assets');
        return cache.addAll(CDN_ASSETS);
      })
      .catch(err => console.error('[SW] CDN cache failed:', err))
  );
  
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating...');
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => {
            return name.startsWith('dtz-') && 
                   name !== STATIC_CACHE && 
                   name !== DYNAMIC_CACHE &&
                   name !== CACHE_NAME;
          })
          .map(name => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    })
  );
  
  self.clients.claim();
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') return;
  
  // Skip API calls - always go to network
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/auth/')) {
    event.respondWith(networkFirst(request));
    return;
  }
  
  // HTML pages - network first with cache fallback
  if (request.mode === 'navigate' || request.headers.get('accept').includes('text/html')) {
    event.respondWith(networkFirst(request));
    return;
  }
  
  // Static assets - cache first
  if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/)) {
    event.respondWith(cacheFirst(request));
    return;
  }
  
  // Everything else - stale while revalidate
  event.respondWith(staleWhileRevalidate(request));
});

// Cache strategies
async function cacheFirst(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request);
  
  if (cached) return cached;
  
  try {
    const response = await fetch(request);
    cache.put(request, response.clone());
    return response;
  } catch (error) {
    console.error('[SW] Cache first failed:', error);
    throw error;
  }
}

async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[SW] Network failed, trying cache:', request.url);
    const cache = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);
    
    if (cached) return cached;
    
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
      const staticCache = await caches.open(STATIC_CACHE);
      return staticCache.match('/frontend/offline.html');
    }
    
    throw error;
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  const cached = await cache.match(request);
  
  const fetchPromise = fetch(request).then(response => {
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  }).catch(err => {
    console.log('[SW] Revalidate failed:', err);
  });
  
  return cached || fetchPromise;
}

// Background sync for offline form submissions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-submissions') {
    event.waitUntil(syncSubmissions());
  }
});

async function syncSubmissions() {
  console.log('[SW] Syncing pending submissions...');
  // This would sync pending writing/speaking submissions when back online
  // Implementation would read from IndexedDB and send to API
}

// Push notifications (for study reminders)
self.addEventListener('push', (event) => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/frontend/img/icon-192x192.png',
    badge: '/frontend/img/icon-72x72.png',
    tag: data.tag,
    requireInteraction: data.requireInteraction || false,
    actions: data.actions || []
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  const action = event.action;
  const notification = event.notification;
  
  if (action === 'open') {
    event.waitUntil(
      clients.openWindow(notification.data?.url || '/frontend/dashboard.html')
    );
  } else {
    event.waitUntil(
      clients.openWindow('/frontend/dashboard.html')
    );
  }
});

// Message handler from main thread
self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});
