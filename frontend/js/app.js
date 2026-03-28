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
        this.updateAvailable = false;
        
        this.init();
    }
    
    init() {
        this.registerServiceWorker();
        this.setupOfflineDetection();
        this.setupInstallPrompt();
        this.setupMobileUX();
    }
    
    /**
     * Register Service Worker
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.log('[App] Service Worker not supported');
            this.showToast('Service Worker wird nicht unterstützt', 'warning');
            return;
        }
        
        try {
            // Check if service worker is already registered
            const existingReg = await navigator.serviceWorker.getRegistration('/frontend/sw.js');
            
            if (existingReg) {
                console.log('[App] SW already registered');
                this.swRegistration = existingReg;
                this.handleServiceWorkerUpdates(existingReg);
            } else {
                // Register new service worker
                this.swRegistration = await navigator.serviceWorker.register('/frontend/sw.js');
                console.log('[App] SW registered:', this.swRegistration.scope);
            }
            
            // Listen for controller changes (new version activated)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[App] New service worker activated');
                if (this.updateAvailable) {
                    this.showUpdateNotification();
                }
            });
            
        } catch (error) {
            console.error('[App] SW registration failed:', error);
            this.showToast('Offline-Modus nicht verfügbar', 'warning');
        }
    }
    
    /**
     * Handle service worker updates
     */
    handleServiceWorkerUpdates(registration) {
        // Check for updates periodically
        setInterval(() => {
            registration.update();
        }, 60000); // Check every minute
        
        // Handle new service worker waiting
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // New version available
                    console.log('[App] New version available');
                    this.updateAvailable = true;
                    this.showUpdateNotification();
                }
            });
        });
    }
    
    /**
     * Setup offline/online detection
     */
    setupOfflineDetection() {
        // Create offline indicator
        this.createOfflineIndicator();
        
        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateOnlineStatus();
            this.showToast('Du bist wieder online!', 'success');
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateOnlineStatus();
            this.showToast('Du bist offline. Einige Funktionen sind eingeschränkt.', 'warning');
        });
        
        // Initial check
        this.updateOnlineStatus();
        
        // Periodic connectivity check
        setInterval(() => this.checkConnectivity(), 30000);
    }
    
    createOfflineIndicator() {
        // Remove existing indicator if any
        const existing = document.getElementById('offline-indicator');
        if (existing) existing.remove();
        
        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        indicator.className = 'offline-indicator';
        indicator.innerHTML = `
            <span class="offline-icon">📡</span>
            <span>Du bist offline</span>
        `;
        document.body.appendChild(indicator);
    }
    
    updateOnlineStatus() {
        const indicator = document.getElementById('offline-indicator');
        if (!indicator) return;
        
        if (this.isOnline) {
            indicator.classList.remove('show');
            document.body.classList.remove('is-offline');
        } else {
            indicator.classList.add('show');
            document.body.classList.add('is-offline');
        }
    }
    
    async checkConnectivity() {
        if (!navigator.onLine) return;
        
        try {
            const response = await fetch('/api/health.php', { 
                method: 'HEAD',
                cache: 'no-store',
                timeout: 5000 
            });
            
            if (response.ok && !this.isOnline) {
                this.isOnline = true;
                this.updateOnlineStatus();
            }
        } catch (e) {
            if (this.isOnline) {
                this.isOnline = false;
                this.updateOnlineStatus();
            }
        }
    }
    
    /**
     * Setup install prompt
     */
    setupInstallPrompt() {
        if (this.isStandalone) return;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            
            // Show install prompt after delay
            setTimeout(() => this.showInstallPrompt(), 5000);
        });
        
        window.addEventListener('appinstalled', () => {
            this.deferredPrompt = null;
            this.hideInstallPrompt();
            this.showToast('App installiert!', 'success');
        });
    }
    
    showInstallPrompt() {
        if (localStorage.getItem('installPromptDismissed')) return;
        
        const prompt = document.createElement('div');
        prompt.className = 'install-prompt';
        prompt.id = 'install-prompt';
        prompt.innerHTML = `
            <div class="install-prompt-icon">📱</div>
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
        if (!this.deferredPrompt) {
            this.showToast('Installation nicht verfügbar. Nutze "Zum Home-Bildschirm hinzufügen".', 'info');
            return;
        }
        
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
        // Add touch feedback
        document.addEventListener('touchstart', function() {}, { passive: true });
        
        // Handle iOS viewport issues
        this.setupViewportHeight();
        
        // Page transition
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
     * Show update notification
     */
    showUpdateNotification() {
        const toast = document.createElement('div');
        toast.className = 'toast show update-toast';
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-title">🎉 Neue Version verfügbar</div>
                <div class="toast-message">Aktualisiere für neue Features</div>
            </div>
            <button class="toast-action" onclick="window.location.reload()">Aktualisieren</button>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Auto remove after 10 seconds
        setTimeout(() => {
            toast.remove();
        }, 10000);
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info', duration = 3000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} show`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        // Remove after duration
        if (duration > 0) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        
        return toast;
    }
    
    /**
     * Sync offline data when coming back online
     */
    async syncOfflineData() {
        if (!('serviceWorker' in navigator)) return;
        
        try {
            await this.swRegistration?.sync?.register('sync-submissions');
        } catch (error) {
            console.log('[App] Background sync not available');
        }
    }
    
    /**
     * Request notification permission
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            this.showToast('Benachrichtigungen werden nicht unterstützt', 'warning');
            return false;
        }
        
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            this.showToast('Benachrichtigungen aktiviert!', 'success');
        }
        return permission === 'granted';
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
            this.showToast('Teilen nicht unterstützt', 'warning');
            return false;
        }
        
        try {
            await navigator.share(data);
            return true;
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('[App] Share failed:', error);
            }
            return false;
        }
    }
    
    /**
     * Check if running as installed PWA
     */
    isInstalled() {
        return this.isStandalone || window.matchMedia('(display-mode: standalone)').matches;
    }
    
    /**
     * Get connection info
     */
    getConnectionInfo() {
        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return {
            online: navigator.onLine,
            type: conn?.effectiveType || 'unknown',
            downlink: conn?.downlink || 0,
            rtt: conn?.rtt || 0
        };
    }
}

// Initialize app when DOM is ready
let app;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        app = new DTZApp();
    });
} else {
    app = new DTZApp();
}

// Export for global access
window.DTZApp = DTZApp;
window.app = app;

// Service Worker message handling
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data === 'reload') {
            window.location.reload();
        }
    });
}
