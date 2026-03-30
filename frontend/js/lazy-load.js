/**
 * Lazy Loading Utilities
 * Images, components, and infinite scroll
 */

// Image Lazy Loading
const ImageLazyLoader = {
    observer: null,
    imageQueue: [],
    
    init(options = {}) {
        const defaultOptions = {
            rootMargin: '50px 0px',
            threshold: 0.01,
            placeholder: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E'
        };
        
        const config = { ...defaultOptions, ...options };
        
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        }, config);
        
        // Find and observe all lazy images
        document.querySelectorAll('img[data-src]').forEach((img) => {
            this.setupImage(img, config.placeholder);
        });
        
        // Watch for dynamically added images
        this.observeNewImages();
    },
    
    setupImage(img, placeholder) {
        // Set placeholder if no src
        if (!img.src || img.src === window.location.href) {
            img.src = placeholder;
        }
        
        // Add loading class
        img.classList.add('lazy-image');
        
        // Handle load event
        img.addEventListener('load', () => {
            img.classList.add('loaded');
            img.classList.remove('loading');
        });
        
        img.addEventListener('error', () => {
            img.classList.add('error');
            img.classList.remove('loading');
        });
        
        this.observer.observe(img);
    },
    
    loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;
        
        if (!src) return;
        
        img.classList.add('loading');
        
        // Create a new image to preload
        const preloadImg = new Image();
        
        preloadImg.onload = () => {
            img.src = src;
            if (srcset) img.srcset = srcset;
            img.removeAttribute('data-src');
            img.removeAttribute('data-srcset');
        };
        
        preloadImg.onerror = () => {
            img.classList.add('error');
            img.classList.remove('loading');
        };
        
        preloadImg.src = src;
    },
    
    observeNewImages() {
        // Use MutationObserver to detect new images
        const mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check if the added node is an image
                        if (node.matches && node.matches('img[data-src]')) {
                            this.setupImage(node);
                        }
                        
                        // Check for images inside the added node
                        if (node.querySelectorAll) {
                            node.querySelectorAll('img[data-src]').forEach((img) => {
                                this.setupImage(img);
                            });
                        }
                    }
                });
            });
        });
        
        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    },
    
    // Manual refresh (useful after dynamic content load)
    refresh() {
        document.querySelectorAll('img[data-src]').forEach((img) => {
            if (!img.classList.contains('lazy-image')) {
                this.setupImage(img);
            }
        });
    }
};

// Component Lazy Loading
const ComponentLazyLoader = {
    components: new Map(),
    observer: null,
    
    register(selector, loadFn, options = {}) {
        this.components.set(selector, { loadFn, options, loaded: false });
    },
    
    init() {
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.loadComponent(entry.target);
                }
            });
        }, {
            rootMargin: '100px 0px',
            threshold: 0.01
        });
        
        // Start observing registered components
        this.components.forEach((config, selector) => {
            document.querySelectorAll(selector).forEach((el) => {
                this.observer.observe(el);
            });
        });
    },
    
    async loadComponent(element) {
        // Find matching component config
        for (const [selector, config] of this.components) {
            if (element.matches(selector) && !config.loaded) {
                config.loaded = true;
                
                try {
                    // Show loading state
                    element.classList.add('component-loading');
                    
                    // Call load function
                    await config.loadFn(element);
                    
                    element.classList.remove('component-loading');
                    element.classList.add('component-loaded');
                } catch (error) {
                    console.error('Failed to load component:', error);
                    element.classList.add('component-error');
                }
                
                this.observer.unobserve(element);
                break;
            }
        }
    }
};

// Infinite Scroll
const InfiniteScroll = {
    observer: null,
    callback: null,
    isLoading: false,
    hasMore: true,
    
    init(options = {}) {
        const {
            container = document,
            triggerSelector = '.infinite-scroll-trigger',
            threshold = 100,
            onLoad = null,
            onError = null
        } = options;
        
        this.callback = onLoad;
        this.onError = onError;
        
        const trigger = container.querySelector?.(triggerSelector) || 
                       document.querySelector(triggerSelector);
        
        if (!trigger) {
            console.warn('Infinite scroll trigger not found:', triggerSelector);
            return;
        }
        
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !this.isLoading && this.hasMore) {
                    this.loadMore();
                }
            });
        }, {
            rootMargin: `${threshold}px 0px`
        });
        
        this.observer.observe(trigger);
    },
    
    async loadMore() {
        if (!this.callback || this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const result = await this.callback();
            
            if (result === false || result?.hasMore === false) {
                this.hasMore = false;
                this.hideLoading();
            }
        } catch (error) {
            console.error('Infinite scroll error:', error);
            if (this.onError) this.onError(error);
        } finally {
            this.isLoading = false;
        }
    },
    
    showLoading() {
        const loader = document.querySelector('.infinite-scroll-loader');
        if (loader) loader.style.display = 'block';
    },
    
    hideLoading() {
        const loader = document.querySelector('.infinite-scroll-loader');
        if (loader) loader.style.display = 'none';
        
        const trigger = document.querySelector('.infinite-scroll-trigger');
        if (trigger) trigger.style.display = 'none';
    },
    
    reset() {
        this.isLoading = false;
        this.hasMore = true;
        
        const trigger = document.querySelector('.infinite-scroll-trigger');
        if (trigger) {
            trigger.style.display = 'block';
            this.observer?.observe(trigger);
        }
    },
    
    destroy() {
        this.observer?.disconnect();
        this.observer = null;
        this.callback = null;
    }
};

// Virtual Scrolling for long lists
const VirtualScroller = {
    container: null,
    itemHeight: 0,
    totalItems: 0,
    visibleCount: 0,
    buffer: 3,
    renderFn: null,
    
    init(options = {}) {
        const {
            container,
            itemHeight,
            totalItems,
            renderFn,
            buffer = 3
        } = options;
        
        this.container = container;
        this.itemHeight = itemHeight;
        this.totalItems = totalItems;
        this.renderFn = renderFn;
        this.buffer = buffer;
        
        this.setupContainer();
        this.calculateVisibleCount();
        this.render();
        
        // Listen for scroll
        this.container.addEventListener('scroll', this.throttle(() => {
            this.render();
        }, 16)); // ~60fps
        
        // Listen for resize
        window.addEventListener('resize', this.throttle(() => {
            this.calculateVisibleCount();
            this.render();
        }, 100));
    },
    
    setupContainer() {
        // Create spacer for total height
        const totalHeight = this.totalItems * this.itemHeight;
        
        this.container.style.position = 'relative';
        this.container.style.overflow = 'auto';
        
        // Add spacer element if not exists
        let spacer = this.container.querySelector('.virtual-spacer');
        if (!spacer) {
            spacer = document.createElement('div');
            spacer.className = 'virtual-spacer';
            this.container.appendChild(spacer);
        }
        spacer.style.height = `${totalHeight}px`;
    },
    
    calculateVisibleCount() {
        const containerHeight = this.container.clientHeight;
        this.visibleCount = Math.ceil(containerHeight / this.itemHeight) + (this.buffer * 2);
    },
    
    render() {
        const scrollTop = this.container.scrollTop;
        const startIdx = Math.max(0, Math.floor(scrollTop / this.itemHeight) - this.buffer);
        const endIdx = Math.min(this.totalItems, startIdx + this.visibleCount);
        
        // Clear current items
        const items = this.container.querySelectorAll('.virtual-item');
        items.forEach((item) => item.remove());
        
        // Render visible items
        for (let i = startIdx; i < endIdx; i++) {
            const item = this.renderFn(i);
            if (item) {
                item.classList.add('virtual-item');
                item.style.position = 'absolute';
                item.style.top = `${i * this.itemHeight}px`;
                item.style.left = '0';
                item.style.right = '0';
                item.style.height = `${this.itemHeight}px`;
                this.container.appendChild(item);
            }
        }
    },
    
    throttle(fn, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                fn.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    updateTotal(newTotal) {
        this.totalItems = newTotal;
        this.setupContainer();
        this.render();
    },
    
    scrollToIndex(index) {
        this.container.scrollTop = index * this.itemHeight;
    }
};

// Progressive Loading for heavy content
const ProgressiveLoader = {
    async load(urls, options = {}) {
        const {
            concurrent = 3,
            onProgress = null,
            onComplete = null,
            onError = null
        } = options;
        
        const results = [];
        const queue = [...urls];
        let loaded = 0;
        let failed = 0;
        
        async function loadItem(url) {
            try {
                const response = await fetch(url);
                const data = await response.json();
                results.push({ url, data, success: true });
                loaded++;
            } catch (error) {
                results.push({ url, error, success: false });
                failed++;
                if (onError) onError(url, error);
            }
            
            if (onProgress) {
                onProgress({
                    loaded,
                    failed,
                    total: urls.length,
                    percent: ((loaded + failed) / urls.length) * 100
                });
            }
        }
        
        // Process queue with concurrency limit
        async function processQueue() {
            const active = [];
            
            while (queue.length > 0 || active.length > 0) {
                while (active.length < concurrent && queue.length > 0) {
                    const url = queue.shift();
                    const promise = loadItem(url).then(() => {
                        const idx = active.indexOf(promise);
                        if (idx > -1) active.splice(idx, 1);
                    });
                    active.push(promise);
                }
                
                if (active.length > 0) {
                    await Promise.race(active);
                }
            }
        }
        
        await processQueue();
        
        if (onComplete) {
            onComplete(results);
        }
        
        return results;
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize image lazy loading
    ImageLazyLoader.init();
    
    // Add CSS for lazy images
    const style = document.createElement('style');
    style.textContent = `
        .lazy-image {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .lazy-image.loading {
            opacity: 0.5;
            background: linear-gradient(90deg, 
                rgba(255,255,255,0.05) 25%, 
                rgba(255,255,255,0.1) 50%, 
                rgba(255,255,255,0.05) 75%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        .lazy-image.loaded {
            opacity: 1;
        }
        
        .lazy-image.error {
            opacity: 1;
            filter: grayscale(100%);
        }
        
        .component-loading {
            min-height: 100px;
            background: linear-gradient(90deg, 
                rgba(255,255,255,0.05) 25%, 
                rgba(255,255,255,0.1) 50%, 
                rgba(255,255,255,0.05) 75%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 12px;
        }
    `;
    document.head.appendChild(style);
});

// Export
window.ImageLazyLoader = ImageLazyLoader;
window.ComponentLazyLoader = ComponentLazyLoader;
window.InfiniteScroll = InfiniteScroll;
window.VirtualScroller = VirtualScroller;
window.ProgressiveLoader = ProgressiveLoader;
