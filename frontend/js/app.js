/**
 * DTZ Learning Platform - App Module (No Service Worker)
 */

class DTZApp {
    constructor() {
        this.isOnline = navigator.onLine;
        this.init();
    }
    
    init() {
        this.setupOfflineDetection();
        this.clearOldServiceWorker();
    }
    
    // Clear any old service worker
    async clearOldServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const regs = await navigator.serviceWorker.getRegistrations();
                for (const reg of regs) {
                    await reg.unregister();
                    console.log('Old SW unregistered');
                }
                // Clear caches
                if ('caches' in window) {
                    const keys = await caches.keys();
                    for (const key of keys) {
                        await caches.delete(key);
                    }
                }
            } catch (e) {
                console.log('SW cleanup:', e);
            }
        }
    }
    
    setupOfflineDetection() {
        // Simple online/offline detection
        const updateStatus = () => {
            this.isOnline = navigator.onLine;
            if (!this.isOnline) {
                console.log('Offline mode');
            }
        };
        
        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        updateStatus();
    }
}

// Initialize
const app = new DTZApp();
window.app = app;
