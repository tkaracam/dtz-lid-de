/**
 * DTZ Learning Platform - PWA App Module
 * Handles service worker registration, offline detection, and mobile UX
 */

class DTZApp {
    constructor() {
        this.swRegistration = null;
        this.isOnline = navigator.onLine;
        this.deferredPrompt = null;
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                           window.navigator.standalone === true;
        
        this.init();
    }
    
    init() {
        this.registerServiceWorker();
        this.setupOfflineDetection();
        this.setupInstallPrompt();
        this.setupMobileUX();
        this.setupPullToRefresh();
    }
    
    /**
     * Register Service Worker
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.log('[App] Service Worker not supported');
            return;
        }
        
        try {
            this.swRegistration = await navigator.serviceWorker.register('/frontend/sw.js');
            console.log('[App] SW registered:', this.swRegistration.scope);
            
            // Handle updates
            this.swRegistration.addEventListener('updatefound', () => {
                const newWorker = this.swRegistration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New version available
                        this.showUpdateNotification();
                    }
                });
            });
        } catch (error) {
            console.error('[App] SW registration failed:', error);
        }
    }
    
    /**
     * Setup offline/online detection
     */
    setupOfflineDetection() {
        const offlineIndicator = document.createElement('div');
        offlineIndicator.className = 'offline-indicator';
        offlineIndicator.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 8px;">
                <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
            </svg>
            Du bist offline
        `;
        document.body.appendChild(offlineIndicator);
        
        const updateOnlineStatus = () => {
            this.isOnline = navigator.onLine;
            if (this.isOnline) {
                offlineIndicator.classList.remove('show');
                document.body.classList.remove('is-offline');
                // Sync when coming back online
                this.syncOfflineData();
            } else {
                offlineIndicator.classList.add('show');
                document.body.classList.add('is-offline');
            }
        };
        
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Initial check
        updateOnlineStatus();
    }
    
    /**
     * Setup install prompt
     */
    setupInstallPrompt() {
        // Don't show if already installed
        if (this.isStandalone) return;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent default prompt
            e.preventDefault();
            this.deferredPrompt = e;
            
            // Show custom install prompt after delay
            setTimeout(() => this.showInstallPrompt(), 5000);
        });
        
        // Hide prompt if installed
        window.addEventListener('appinstalled', () => {
            this.deferredPrompt = null;
            this.hideInstallPrompt();
            console.log('[App] PWA was installed');
        });
    }
    
    showInstallPrompt() {
        // Check if user previously dismissed
        if (localStorage.getItem('installPromptDismissed')) return;
        
        const prompt = document.createElement('div');
        prompt.className = 'install-prompt';
        prompt.id = 'install-prompt';
        prompt.innerHTML = `
            <div class="install-prompt-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <div class="install-prompt-content">
                <div class="install-prompt-title">DTZ App installieren</div>
                <div class="install-prompt-text">Schneller Zugriff, auch offline</div>
            </div>
            <div class="install-prompt-actions">
                <button class="install-prompt-btn primary" onclick="app.installPWA()">Installieren</button>
                <button class="install-prompt-btn secondary" onclick="app.dismissInstallPrompt()">Später</button>
            </div>
        `;
        document.body.appendChild(prompt);
    }
    
    hideInstallPrompt() {
        const prompt = document.getElementById('install-prompt');
        if (prompt) prompt.remove();
    }
    
    dismissInstallPrompt() {
        this.hideInstallPrompt();
        localStorage.setItem('installPromptDismissed', Date.now().toString());
    }
    
    async installPWA() {
        if (!this.deferredPrompt) return;
        
        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('[App] User accepted install');
        }
        
        this.deferredPrompt = null;
        this.hideInstallPrompt();
    }
    
    /**
     * Setup mobile-specific UX enhancements
     */
    setupMobileUX() {
        // Add active state to touch elements
        document.addEventListener('touchstart', function() {}, { passive: true });
        
        // Handle iOS viewport height issues
        this.setupViewportHeight();
        
        // Add page transition animation
        document.body.classList.add('page-transition');
    }
    
    setupViewportHeight() {
        const setVH = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };
        
        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', setVH);
    }
    
    /**
     * Setup pull-to-refresh
     */
    setupPullToRefresh() {
        let startY = 0;
        let isPulling = false;
        const ptrThreshold = 80;
        
        const container = document.querySelector('.main-content') || document.body;
        
        container.addEventListener('touchstart', (e) => {
            if (container.scrollTop === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        }, { passive: true });
        
        container.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            const currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            
            if (diff > 0 && diff < ptrThreshold) {
                container.style.transform = `translateY(${diff * 0.5}px)`;
            }
        }, { passive: true });
        
        container.addEventListener('touchend', () => {
            if (!isPulling) return;
            
            const currentTransform = parseInt(container.style.transform?.replace('translateY(', '') || 0);
            
            if (currentTransform > ptrThreshold * 0.5) {
                // Trigger refresh
                window.location.reload();
            } else {
                container.style.transform = '';
            }
            
            isPulling = false;
        });
    }
    
    /**
     * Show update notification
     */
    showUpdateNotification() {
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-title">Update verfügbar</div>
                <div class="toast-message">Neue Version der App verfügbar</div>
            </div>
            <button class="toast-action" onclick="window.location.reload()">Aktualisieren</button>
        `;
        
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
    }
    
    /**
     * Sync offline data when coming back online
     */
    async syncOfflineData() {
        if (!('serviceWorker' in navigator) || !navigator.serviceWorker.controller) return;
        
        // Trigger background sync
        try {
            await this.swRegistration.sync.register('sync-submissions');
            console.log('[App] Background sync registered');
        } catch (error) {
            console.log('[App] Background sync failed:', error);
        }
    }
    
    /**
     * Request notification permission
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) return false;
        
        const permission = await Notification.requestPermission();
        return permission === 'granted';
    }
    
    /**
     * Schedule study reminder
     */
    scheduleReminder(title, body, delay) {
        if (!('serviceWorker' in navigator)) return;
        
        setTimeout(() => {
            navigator.serviceWorker.ready.then(registration => {
                registration.showNotification(title, {
                    body,
                    icon: '/frontend/img/icon-192x192.png',
                    badge: '/frontend/img/icon-72x72.png',
                    tag: 'study-reminder',
                    requireInteraction: true,
                    actions: [
                        { action: 'open', title: 'Öffnen' },
                        { action: 'dismiss', title: 'Später' }
                    ]
                });
            });
        }, delay);
    }
    
    /**
     * Vibrate device (mobile)
     */
    vibrate(pattern = 50) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    }
    
    /**
     * Share content (using Web Share API)
     */
    async share(data) {
        if (!navigator.share) {
            console.log('[App] Web Share API not supported');
            return false;
        }
        
        try {
            await navigator.share(data);
            return true;
        } catch (error) {
            console.log('[App] Share cancelled or failed:', error);
            return false;
        }
    }
}

// Initialize app
const app = new DTZApp();

// Export for global access
window.DTZApp = DTZApp;
window.app = app;
