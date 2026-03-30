/**
 * Performance Monitoring & Optimization
 * DTZ Learning Platform
 */

// Performance Monitor
const PerformanceMonitor = {
    metrics: {},
    observers: [],
    
    init() {
        // Core Web Vitals
        this.measureLCP();
        this.measureFID();
        this.measureCLS();
        this.measureFCP();
        this.measureTTFB();
        
        // Resource loading
        this.measureResourceTiming();
        
        // Navigation timing
        this.measureNavigationTiming();
        
        // Custom metrics
        this.measureCustomMetrics();
        
        // Log initial metrics
        window.addEventListener('load', () => {
            setTimeout(() => this.logMetrics(), 0);
        });
    },
    
    // Largest Contentful Paint
    measureLCP() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                this.metrics.lcp = lastEntry.startTime;
            });
            
            observer.observe({ entryTypes: ['largest-contentful-paint'] });
            this.observers.push(observer);
        } catch (e) {
            console.warn('LCP measurement not supported');
        }
    },
    
    // First Input Delay
    measureFID() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.processingStart) {
                        this.metrics.fid = entry.processingStart - entry.startTime;
                    }
                }
            });
            
            observer.observe({ entryTypes: ['first-input'] });
            this.observers.push(observer);
        } catch (e) {
            console.warn('FID measurement not supported');
        }
    },
    
    // Cumulative Layout Shift
    measureCLS() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            let clsValue = 0;
            
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
                this.metrics.cls = clsValue;
            });
            
            observer.observe({ entryTypes: ['layout-shift'] });
            this.observers.push(observer);
        } catch (e) {
            console.warn('CLS measurement not supported');
        }
    },
    
    // First Contentful Paint
    measureFCP() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.name === 'first-contentful-paint') {
                        this.metrics.fcp = entry.startTime;
                    }
                }
            });
            
            observer.observe({ entryTypes: ['paint'] });
            this.observers.push(observer);
        } catch (e) {
            console.warn('FCP measurement not supported');
        }
    },
    
    // Time to First Byte
    measureTTFB() {
        const navigation = performance.getEntriesByType('navigation')[0];
        if (navigation) {
            this.metrics.ttfb = navigation.responseStart - navigation.startTime;
        }
    },
    
    // Resource Timing
    measureResourceTiming() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            const observer = new PerformanceObserver((list) => {
                const resources = list.getEntries();
                
                this.metrics.resources = resources.map((r) => ({
                    name: r.name.split('/').pop(),
                    type: r.initiatorType,
                    duration: Math.round(r.duration * 100) / 100,
                    size: r.transferSize
                }));
                
                // Calculate total size
                this.metrics.totalTransferSize = resources.reduce(
                    (sum, r) => sum + r.transferSize, 
                    0
                );
            });
            
            observer.observe({ entryTypes: ['resource'] });
            this.observers.push(observer);
        } catch (e) {
            console.warn('Resource timing not supported');
        }
    },
    
    // Navigation Timing
    measureNavigationTiming() {
        const navigation = performance.getEntriesByType('navigation')[0];
        if (!navigation) return;
        
        this.metrics.navigation = {
            dns: Math.round((navigation.domainLookupEnd - navigation.domainLookupStart) * 100) / 100,
            connect: Math.round((navigation.connectEnd - navigation.connectStart) * 100) / 100,
            ttfb: Math.round((navigation.responseStart - navigation.startTime) * 100) / 100,
            domInteractive: Math.round((navigation.domInteractive - navigation.startTime) * 100) / 100,
            domComplete: Math.round((navigation.domComplete - navigation.startTime) * 100) / 100,
            loadComplete: Math.round((navigation.loadEventEnd - navigation.startTime) * 100) / 100
        };
    },
    
    // Custom Metrics
    measureCustomMetrics() {
        // Time to Interactive (approximation)
        window.addEventListener('load', () => {
            setTimeout(() => {
                const timing = performance.getEntriesByType('navigation')[0];
                if (timing) {
                    this.metrics.tti = Math.round(
                        (timing.domInteractive - timing.startTime) * 100
                    ) / 100;
                }
            }, 0);
        });
    },
    
    // Mark custom timing
    mark(name) {
        performance.mark(name);
        this.metrics[name] = performance.now();
    },
    
    // Measure between marks
    measure(name, startMark, endMark) {
        try {
            performance.measure(name, startMark, endMark);
            const entries = performance.getEntriesByName(name, 'measure');
            if (entries.length > 0) {
                this.metrics[name] = Math.round(entries[0].duration * 100) / 100;
            }
        } catch (e) {
            console.warn('Measurement failed:', e);
        }
    },
    
    // Log all metrics
    logMetrics() {
        console.group('📊 Performance Metrics');
        
        if (this.metrics.fcp) {
            console.log(`FCP: ${this.metrics.fcp.toFixed(2)}ms`, this.getRating('fcp', this.metrics.fcp));
        }
        if (this.metrics.lcp) {
            console.log(`LCP: ${this.metrics.lcp.toFixed(2)}ms`, this.getRating('lcp', this.metrics.lcp));
        }
        if (this.metrics.fid) {
            console.log(`FID: ${this.metrics.fid.toFixed(2)}ms`, this.getRating('fid', this.metrics.fid));
        }
        if (this.metrics.cls !== undefined) {
            console.log(`CLS: ${this.metrics.cls.toFixed(3)}`, this.getRating('cls', this.metrics.cls));
        }
        if (this.metrics.ttfb) {
            console.log(`TTFB: ${this.metrics.ttfb.toFixed(2)}ms`, this.getRating('ttfb', this.metrics.ttfb));
        }
        if (this.metrics.tti) {
            console.log(`TTI: ${this.metrics.tti.toFixed(2)}ms`);
        }
        
        if (this.metrics.totalTransferSize) {
            const sizeKB = (this.metrics.totalTransferSize / 1024).toFixed(2);
            console.log(`Total Transfer Size: ${sizeKB} KB`);
        }
        
        if (this.metrics.navigation) {
            console.group('Navigation Timing');
            console.log(`DNS: ${this.metrics.navigation.dns}ms`);
            console.log(`Connect: ${this.metrics.navigation.connect}ms`);
            console.log(`DOM Interactive: ${this.metrics.navigation.domInteractive}ms`);
            console.log(`DOM Complete: ${this.metrics.navigation.domComplete}ms`);
            console.groupEnd();
        }
        
        console.groupEnd();
        
        return this.metrics;
    },
    
    // Get rating for metric
    getRating(metric, value) {
        const thresholds = {
            fcp: { good: 1800, poor: 3000 },
            lcp: { good: 2500, poor: 4000 },
            fid: { good: 100, poor: 300 },
            cls: { good: 0.1, poor: 0.25 },
            ttfb: { good: 600, poor: 1000 }
        };
        
        const t = thresholds[metric];
        if (!t) return '';
        
        if (value <= t.good) return '✅ Good';
        if (value <= t.poor) return '⚠️ Needs Improvement';
        return '❌ Poor';
    },
    
    // Get metrics summary
    getSummary() {
        return {
            ...this.metrics,
            rating: this.getOverallRating()
        };
    },
    
    getOverallRating() {
        const scores = [];
        
        if (this.metrics.lcp) {
            scores.push(this.metrics.lcp <= 2500 ? 1 : this.metrics.lcp <= 4000 ? 0.5 : 0);
        }
        if (this.metrics.fid) {
            scores.push(this.metrics.fid <= 100 ? 1 : this.metrics.fid <= 300 ? 0.5 : 0);
        }
        if (this.metrics.cls !== undefined) {
            scores.push(this.metrics.cls <= 0.1 ? 1 : this.metrics.cls <= 0.25 ? 0.5 : 0);
        }
        
        const avg = scores.reduce((a, b) => a + b, 0) / scores.length;
        
        if (avg >= 0.9) return 'good';
        if (avg >= 0.5) return 'needs-improvement';
        return 'poor';
    }
};

// Memory Monitor
const MemoryMonitor = {
    init() {
        if (!('memory' in performance)) {
            console.log('Memory API not available');
            return;
        }
        
        // Log memory usage every 30 seconds
        setInterval(() => this.logMemory(), 30000);
        
        // Initial log
        setTimeout(() => this.logMemory(), 5000);
    },
    
    logMemory() {
        const memory = performance.memory;
        if (!memory) return;
        
        const used = (memory.usedJSHeapSize / 1048576).toFixed(2);
        const total = (memory.totalJSHeapSize / 1048576).toFixed(2);
        const limit = (memory.jsHeapSizeLimit / 1048576).toFixed(2);
        
        console.log(`🧠 Memory: ${used}MB / ${total}MB (Limit: ${limit}MB)`);
        
        return { used, total, limit };
    },
    
    getUsage() {
        if (!('memory' in performance)) return null;
        
        const memory = performance.memory;
        return {
            usedMB: Math.round(memory.usedJSHeapSize / 1048576),
            totalMB: Math.round(memory.totalJSHeapSize / 1048576),
            limitMB: Math.round(memory.jsHeapSizeLimit / 1048576),
            percent: Math.round((memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100)
        };
    }
};

// Preloading utilities
const Preloader = {
    // Preload critical resources
    preloadCritical() {
        const criticalResources = [
            '/frontend/css/styles.css',
            '/frontend/js/auth.js',
            '/frontend/js/ui.js'
        ];
        
        criticalResources.forEach((url) => this.preloadLink(url, 'fetch'));
    },
    
    // Preload images
    preloadImages(urls) {
        urls.forEach((url) => {
            const img = new Image();
            img.src = url;
        });
    },
    
    // Add preload link
    preloadLink(url, as = 'fetch', type = null) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = url;
        link.as = as;
        if (type) link.type = type;
        
        document.head.appendChild(link);
    },
    
    // Prefetch resources for next page
    prefetch(urls) {
        urls.forEach((url) => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = url;
            document.head.appendChild(link);
        });
    },
    
    // Preconnect to domains
    preconnect(domains) {
        domains.forEach((domain) => {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = domain;
            document.head.appendChild(link);
            
            const dnsLink = document.createElement('link');
            dnsLink.rel = 'dns-prefetch';
            dnsLink.href = domain;
            document.head.appendChild(dnsLink);
        });
    }
};

// Animation performance optimization
const AnimationOptimizer = {
    willChangeElements: new Set(),
    
    // Enable will-change for animated element
    enable(element) {
        if (!this.willChangeElements.has(element)) {
            element.style.willChange = 'transform, opacity';
            this.willChangeElements.add(element);
        }
    },
    
    // Disable will-change after animation
    disable(element) {
        if (this.willChangeElements.has(element)) {
            element.style.willChange = 'auto';
            this.willChangeElements.delete(element);
        }
    },
    
    // Use requestAnimationFrame for smooth animations
    raf(callback) {
        return requestAnimationFrame(() => {
            requestAnimationFrame(callback);
        });
    },
    
    // Throttle to animation frame
    throttleToRAF(callback) {
        let ticking = false;
        
        return function(...args) {
            if (!ticking) {
                requestAnimationFrame(() => {
                    callback.apply(this, args);
                    ticking = false;
                });
                ticking = true;
            }
        };
    }
};

// Long task monitoring
const LongTaskMonitor = {
    init() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    console.warn('⚠️ Long task detected:', {
                        duration: entry.duration,
                        startTime: entry.startTime
                    });
                    
                    // Could send to analytics here
                }
            });
            
            observer.observe({ entryTypes: ['longtask'] });
        } catch (e) {
            console.log('Long task monitoring not supported');
        }
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Start performance monitoring
    PerformanceMonitor.init();
    
    // Start memory monitoring
    MemoryMonitor.init();
    
    // Start long task monitoring
    LongTaskMonitor.init();
    
    // Preload critical resources
    Preloader.preloadCritical();
});

// Export
window.PerformanceMonitor = PerformanceMonitor;
window.MemoryMonitor = MemoryMonitor;
window.Preloader = Preloader;
window.AnimationOptimizer = AnimationOptimizer;
