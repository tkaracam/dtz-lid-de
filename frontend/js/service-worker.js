/**
 * Service Worker for DTZ Learning Platform
 * Caching strategies: Cache First for static, Network First for API
 */

const CACHE_VERSION = 'v2';
const STATIC_CACHE = `dtz-static-${CACHE_VERSION}`;
const API_CACHE = `dtz-api-${CACHE_VERSION}`;
const IMAGE_CACHE = `dtz-images-${CACHE_VERSION}`;

// Static assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/frontend/css/styles.css',
    '/frontend/css/mobile.css',
    '/frontend/css/animations.css',
    '/frontend/css/components.css',
    '/frontend/js/config.js',
    '/frontend/js/auth.js',
    '/frontend/js/ui.js',
    '/frontend/js/app.js',
    '/frontend/dashboard.html',
    '/frontend/learn.html',
    '/frontend/login.html',
    '/frontend/register.html',
    '/frontend/modelltest.html',
    '/manifest.json',
    '/frontend/img/icon-192x192.png',
    '/frontend/img/icon-512x512.png'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
            .catch((err) => console.error('[SW] Cache failed:', err))
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            return name.startsWith('dtz-') && 
                                   !name.includes(CACHE_VERSION);
                        })
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - apply caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }
    
    // Strategy: API calls (Network First with cache fallback)
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstStrategy(request, API_CACHE));
        return;
    }
    
    // Strategy: Images (Cache First with network fallback)
    if (request.destination === 'image') {
        event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
        return;
    }
    
    // Strategy: Static assets (Cache First with stale-while-revalidate)
    if (isStaticAsset(request)) {
        event.respondWith(staleWhileRevalidateStrategy(request, STATIC_CACHE));
        return;
    }
    
    // Default: Network with cache fallback
    event.respondWith(networkWithCacheFallback(request));
});

// Cache First Strategy
async function cacheFirstStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    if (cached) {
        // Return cached and update in background
        fetch(request)
            .then((response) => {
                if (response.ok) {
                    cache.put(request, response.clone());
                }
            })
            .catch(() => {});
        return cached;
    }
    
    // Not in cache, fetch and cache
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Network First Strategy
async function networkFirstStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Update cache with fresh data
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // Network failed, try cache
        const cached = await cache.match(request);
        
        if (cached) {
            console.log('[SW] Serving from cache:', request.url);
            return cached;
        }
        
        // Nothing in cache
        return new Response(
            JSON.stringify({ error: 'Offline', offline: true }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Stale While Revalidate Strategy
async function staleWhileRevalidateStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    // Always fetch from network in background
    const fetchPromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);
    
    // Return cached immediately if available
    return cached || fetchPromise;
}

// Network with Cache Fallback
async function networkWithCacheFallback(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        const cache = await caches.open(STATIC_CACHE);
        const cached = await cache.match(request);
        
        if (cached) {
            return cached;
        }
        
        throw error;
    }
}

// Check if request is for static asset
function isStaticAsset(request) {
    const staticExtensions = [
        '.css', '.js', '.html', '.json', '.png', '.jpg', 
        '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'
    ];
    
    return staticExtensions.some((ext) => 
        request.url.endsWith(ext)
    );
}

// Background sync for offline form submissions
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-answers') {
        event.waitUntil(syncPendingAnswers());
    }
});

async function syncPendingAnswers() {
    // This would sync pending answers from IndexedDB
    console.log('[SW] Syncing pending answers...');
}

// Push notifications
self.addEventListener('push', (event) => {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: '/frontend/img/icon-192x192.png',
        badge: '/frontend/img/icon-192x192.png',
        data: data.data,
        actions: data.actions || [],
        requireInteraction: data.requireInteraction || false,
        vibrate: [200, 100, 200]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    const notification = event.notification;
    const action = event.action;
    
    notification.close();
    
    if (action === 'dismiss') {
        return;
    }
    
    // Default action or 'open'
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            const url = notification.data?.url || '/frontend/dashboard.html';
            
            // Focus existing window if open
            for (const client of clientList) {
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // Open new window
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
