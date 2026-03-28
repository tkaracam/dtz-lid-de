/**
 * DTZ Learning Platform - Service Worker
 * Provides offline functionality and caching
 */

const CACHE_VERSION = 'v3';
const STATIC_CACHE = `dtz-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dtz-dynamic-${CACHE_VERSION}`;

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/frontend/css/styles.css',
  '/frontend/css/mobile.css',
  '/frontend/js/auth.js',
  '/frontend/js/api.js',
  '/frontend/js/ui.js',
  '/frontend/js/app.js',
  '/frontend/js/config.js',
  '/frontend/dashboard.html',
  '/frontend/learn.html',
  '/frontend/progress.html',
  '/frontend/modelltest.html',
  '/frontend/writing.html',
  '/frontend/settings.html',
  '/frontend/login.html',
  '/frontend/register.html',
  '/frontend/offline.html',
  '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[SW] Installing version:', CACHE_VERSION);
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[SW] Install complete');
        return self.skipWaiting();
      })
      .catch(err => {
        console.error('[SW] Cache failed:', err);
        // Continue even if some assets fail
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating version:', CACHE_VERSION);
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => {
            // Delete old versioned caches
            return name.startsWith('dtz-') && 
                   !name.includes(CACHE_VERSION);
          })
          .map(name => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    })
    .then(() => {
      console.log('[SW] Activation complete');
      return self.clients.claim();
    })
  );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') return;
  
  // Skip API calls - always go to network, no cache
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/auth/')) {
    event.respondWith(
      fetch(request).catch(() => {
        // If network fails, return a JSON error
        return new Response(
          JSON.stringify({ error: 'Offline - Bitte Internetverbindung prüfen' }),
          { 
            status: 503, 
            headers: { 'Content-Type': 'application/json' }
          }
        );
      })
    );
    return;
  }
  
  // HTML navigation requests - network first
  if (request.mode === 'navigate') {
    event.respondWith(networkFirstWithOfflineFallback(request));
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
  
  if (cached) {
    return cached;
  }
  
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.error('[SW] Cache first failed:', request.url);
    // Return a minimal error response instead of throwing
    return new Response('Network error', { status: 408 });
  }
}

async function networkFirstWithOfflineFallback(request) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Update cache with fresh version
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
      return networkResponse;
    }
    
    throw new Error('Network response not ok');
  } catch (error) {
    console.log('[SW] Network failed, serving from cache:', request.url);
    
    // Try cache
    const cache = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);
    
    if (cached) {
      return cached;
    }
    
    // Try static cache as fallback
    const staticCache = await caches.open(STATIC_CACHE);
    const staticCached = await staticCache.match(request);
    
    if (staticCached) {
      return staticCached;
    }
    
    // Last resort: offline page
    const offlinePage = await staticCache.match('/frontend/offline.html');
    if (offlinePage) {
      return offlinePage;
    }
    
    // Absolute fallback
    return new Response(
      '<html><body><h1>Offline</h1><p>Bitte Internetverbindung prüfen</p></body></html>',
      { headers: { 'Content-Type': 'text/html' } }
    );
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  const cached = await cache.match(request);
  
  const fetchPromise = fetch(request)
    .then(response => {
      if (response.ok) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(err => {
      console.log('[SW] Fetch failed, serving cached:', request.url);
      // Return cached if available, otherwise throw
      if (cached) return cached;
      throw err;
    });
  
  // Return cached immediately if available, otherwise wait for fetch
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
}

// Push notifications
self.addEventListener('push', (event) => {
  if (!event.data) return;
  
  try {
    const data = event.data.json();
    const options = {
      body: data.body || 'Neue Benachrichtigung',
      icon: '/frontend/img/icon-192x192.svg',
      badge: '/frontend/img/icon-72x72.svg',
      tag: data.tag || 'default',
      requireInteraction: data.requireInteraction || false,
      actions: data.actions || []
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title || 'DTZ Lernen', options)
    );
  } catch (e) {
    console.error('[SW] Push notification error:', e);
  }
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  const action = event.action;
  const notification = event.notification;
  
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then(clientList => {
      // If a window is already open, focus it
      for (const client of clientList) {
        if (client.url.includes('/frontend/') && 'focus' in client) {
          return client.focus();
        }
      }
      // Otherwise open new window
      if (clients.openWindow) {
        return clients.openWindow(notification.data?.url || '/frontend/dashboard.html');
      }
    })
  );
});

// Message handler from main thread
self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
  
  if (event.data === 'getVersion') {
    event.ports[0].postMessage({ version: CACHE_VERSION });
  }
  
  if (event.data === 'clearCache') {
    event.waitUntil(
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(name => caches.delete(name))
        );
      }).then(() => {
        event.ports[0].postMessage({ cleared: true });
      })
    );
  }
});
