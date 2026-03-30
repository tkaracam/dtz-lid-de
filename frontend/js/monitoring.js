/**
 * Monitoring and Error Tracking for DTZ Platform
 */

const Monitoring = {
    endpoint: '/api/metrics/log',
    sessionId: null,
    
    init() {
        this.sessionId = this.generateSessionId();
        this.setupErrorHandlers();
        this.trackPageLoad();
        this.startHeartbeat();
    },
    
    generateSessionId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    },
    
    setupErrorHandlers() {
        // Global error handler
        window.addEventListener('error', (e) => {
            this.logError('javascript', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error?.stack
            });
        });
        
        // Unhandled promise rejections
        window.addEventListener('unhandledrejection', (e) => {
            this.logError('promise', {
                message: e.reason?.message || String(e.reason),
                stack: e.reason?.stack
            });
        });
        
        // API error tracking
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const start = performance.now();
            try {
                const response = await originalFetch(...args);
                const duration = performance.now() - start;
                
                // Log slow requests
                if (duration > 2000) {
                    this.logMetric('slow_request', {
                        url: args[0],
                        duration: Math.round(duration),
                        status: response.status
                    });
                }
                
                // Log API errors
                if (!response.ok && response.status >= 500) {
                    this.logError('api', {
                        url: args[0],
                        status: response.status,
                        statusText: response.statusText
                    });
                }
                
                return response;
            } catch (error) {
                this.logError('network', {
                    url: args[0],
                    message: error.message
                });
                throw error;
            }
        };
    },
    
    trackPageLoad() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const timing = performance.getEntriesByType('navigation')[0];
                if (timing) {
                    this.logMetric('page_load', {
                        url: window.location.pathname,
                        loadTime: Math.round(timing.loadEventEnd - timing.startTime),
                        domContentLoaded: Math.round(timing.domContentLoadedEventEnd - timing.startTime),
                        transferSize: timing.transferSize
                    });
                }
            }, 0);
        });
    },
    
    startHeartbeat() {
        // Send heartbeat every 30 seconds
        setInterval(() => {
            this.send({
                type: 'heartbeat',
                timestamp: Date.now(),
                sessionId: this.sessionId,
                url: window.location.pathname,
                online: navigator.onLine
            });
        }, 30000);
    },
    
    logError(type, details) {
        this.send({
            type: 'error',
            errorType: type,
            timestamp: Date.now(),
            sessionId: this.sessionId,
            url: window.location.pathname,
            userAgent: navigator.userAgent,
            details: this.sanitize(details)
        });
    },
    
    logMetric(name, data) {
        this.send({
            type: 'metric',
            name: name,
            timestamp: Date.now(),
            sessionId: this.sessionId,
            url: window.location.pathname,
            data: data
        });
    },
    
    logEvent(category, action, label = null, value = null) {
        this.send({
            type: 'event',
            timestamp: Date.now(),
            sessionId: this.sessionId,
            category: category,
            action: action,
            label: label,
            value: value
        });
    },
    
    sanitize(obj) {
        // Remove sensitive data
        const sensitive = ['password', 'token', 'secret', 'credit_card'];
        const sanitized = {};
        
        for (const [key, value] of Object.entries(obj)) {
            const isSensitive = sensitive.some(s => key.toLowerCase().includes(s));
            sanitized[key] = isSensitive ? '[REDACTED]' : value;
        }
        
        return sanitized;
    },
    
    async send(data) {
        // Use Beacon API if available (sends on page unload)
        if (navigator.sendBeacon && data.type === 'error') {
            navigator.sendBeacon(this.endpoint, JSON.stringify(data));
            return;
        }
        
        // Otherwise use fetch with keepalive
        try {
            await fetch(this.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                keepalive: true
            });
        } catch (e) {
            // Silently fail - don't cause infinite loops
            console.debug('Failed to send metric:', e);
        }
    }
};

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Monitoring.init());
} else {
    Monitoring.init();
}

window.Monitoring = Monitoring;
