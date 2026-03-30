/**
 * DTZ Learning Platform - App Module
 * With Service Worker, Offline Support & Performance Monitoring
 */

class DTZApp {
    constructor() {
        this.isOnline = navigator.onLine;
        this.swRegistered = false;
        this.dbReady = false;
        this.init();
    }
    
    async init() {
        console.log('🚀 DTZ App Initializing...');
        
        // Register Service Worker
        await this.registerServiceWorker();
        
        // Initialize IndexedDB
        await this.initDatabase();
        
        // Setup offline detection
        this.setupOfflineDetection();
        
        // Setup performance monitoring
        this.setupPerformanceMonitoring();
        
        // Cleanup expired cache periodically
        this.scheduleCacheCleanup();
        
        console.log('✅ DTZ App Initialized');
    }
    
    // Register Service Worker
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.log('Service Worker not supported');
            return;
        }
        
        try {
            // First, clear old service workers to avoid conflicts
            await this.clearOldServiceWorkers();
            
            // Register new service worker
            const registration = await navigator.serviceWorker.register('/frontend/js/service-worker.js', {
                scope: '/'
            });
            
            this.swRegistered = true;
            console.log('✅ Service Worker registered:', registration.scope);
            
            // Handle updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('🔄 New version available');
                        this.showUpdateNotification(newWorker);
                    }
                });
            });
            
        } catch (error) {
            console.error('❌ Service Worker registration failed:', error);
        }
    }
    
    // Clear old service workers
    async clearOldServiceWorkers() {
        try {
            const regs = await navigator.serviceWorker.getRegistrations();
            
            for (const reg of regs) {
                await reg.unregister();
                console.log('🗑️ Old SW unregistered');
            }
            
            // Clear old caches
            if ('caches' in window) {
                const keys = await caches.keys();
                for (const key of keys) {
                    await caches.delete(key);
                    console.log('🗑️ Cache cleared:', key);
                }
            }
        } catch (e) {
            console.log('SW cleanup error:', e);
        }
    }
    
    // Show update notification
    showUpdateNotification(worker) {
        if (window.Toast) {
            Toast.info('Neue Version verfügbar. Seite wird aktualisiert...', 'Update');
        }
        
        // Force reload after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
    
    // Initialize IndexedDB
    async initDatabase() {
        if (typeof db === 'undefined') {
            console.log('Database module not loaded');
            return;
        }
        
        try {
            await db.init();
            this.dbReady = true;
            console.log('✅ Database initialized');
            
            // Clean up expired cache on startup
            const deleted = await db.cleanupExpiredCache();
            if (deleted > 0) {
                console.log(`🗑️ Cleaned up ${deleted} expired cache entries`);
            }
        } catch (error) {
            console.error('❌ Database initialization failed:', error);
        }
    }
    
    // Setup offline detection
    setupOfflineDetection() {
        const updateStatus = (online) => {
            this.isOnline = online;
            
            if (online) {
                console.log('🌐 Back online');
                document.body.classList.remove('offline');
                
                // Sync pending data
                if (typeof OfflineAPI !== 'undefined') {
                    this.syncPendingData();
                }
            } else {
                console.log('📴 Offline mode');
                document.body.classList.add('offline');
                
                if (window.Toast) {
                    Toast.warning('Sie sind offline. Daten werden lokal gespeichert.', 'Offline');
                }
            }
        };
        
        window.addEventListener('online', () => updateStatus(true));
        window.addEventListener('offline', () => updateStatus(false));
        
        // Initial status
        updateStatus(navigator.onLine);
    }
    
    // Sync pending data when back online
    async syncPendingData() {
        try {
            const results = await OfflineAPI.syncPendingAnswers();
            const successCount = results.filter(r => r.success).length;
            
            if (successCount > 0 && window.Toast) {
                Toast.success(`${successCount} Antworten synchronisiert`);
            }
        } catch (error) {
            console.error('Sync failed:', error);
        }
    }
    
    // Setup performance monitoring
    setupPerformanceMonitoring() {
        if (typeof PerformanceMonitor !== 'undefined') {
            // Log metrics after page load
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const metrics = PerformanceMonitor.logMetrics();
                    
                    // Store metrics in DB for analysis
                    if (this.dbReady) {
                        db.setUserData('lastPerformanceMetrics', metrics);
                    }
                }, 1000);
            });
        }
    }
    
    // Schedule periodic cache cleanup
    scheduleCacheCleanup() {
        // Cleanup every hour
        setInterval(async () => {
            if (this.dbReady) {
                try {
                    const deleted = await db.cleanupExpiredCache();
                    if (deleted > 0) {
                        console.log(`🗑️ Scheduled cleanup: ${deleted} entries removed`);
                    }
                } catch (e) {
                    console.error('Cache cleanup error:', e);
                }
            }
        }, 60 * 60 * 1000);
    }
    
    // Check if app is ready
    isReady() {
        return this.swRegistered && this.dbReady;
    }
    
    // Get app status
    getStatus() {
        return {
            online: this.isOnline,
            swRegistered: this.swRegistered,
            dbReady: this.dbReady,
            memory: typeof MemoryMonitor !== 'undefined' ? MemoryMonitor.getUsage() : null
        };
    }
}

// Add offline styles
const offlineStyles = document.createElement('style');
offlineStyles.textContent = `
    body.offline::before {
        content: '📴 Offline Modus';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #f59e0b;
        color: #000;
        text-align: center;
        padding: 4px;
        font-size: 12px;
        font-weight: 600;
        z-index: 99999;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { transform: translateY(-100%); }
        to { transform: translateY(0); }
    }
`;
document.head.appendChild(offlineStyles);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.app = new DTZApp();
    });
} else {
    window.app = new DTZApp();
}

// Export for global access
window.DTZApp = DTZApp;
