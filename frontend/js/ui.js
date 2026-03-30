/**
 * Modern UI Components & Utilities
 * DTZ Learning Platform
 */

// ========== TOAST NOTIFICATIONS ==========
const Toast = {
    container: null,
    
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    
    show(message, type = 'info', title = '', duration = 5000) {
        this.init();
        
        // XSS protection: escape HTML in user-provided content
        const escape = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        const titles = {
            success: 'Erfolg',
            error: 'Fehler',
            warning: 'Warnung',
            info: 'Info'
        };
        
        const safeMessage = escape(message);
        const safeTitle = escape(title || titles[type]);
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type]}</span>
            <div class="toast-content">
                <div class="toast-title">${safeTitle}</div>
                <div class="toast-message">${safeMessage}</div>
            </div>
            <button class="toast-close" onclick="Toast.dismiss(this.parentElement)">×</button>
            <div class="toast-progress"></div>
        `;
        
        this.container.appendChild(toast);
        
        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(toast);
            }, duration);
        }
        
        return toast;
    },
    
    success(message, title = '') {
        return this.show(message, 'success', title);
    },
    
    error(message, title = '') {
        return this.show(message, 'error', title);
    },
    
    warning(message, title = '') {
        return this.show(message, 'warning', title);
    },
    
    info(message, title = '') {
        return this.show(message, 'info', title);
    },
    
    dismiss(toast) {
        if (!toast || toast.classList.contains('hiding')) return;
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }
};

// ========== MODAL ==========
const Modal = {
    create(options = {}) {
        const {
            title = '',
            content = '',
            showClose = true,
            onClose = null,
            className = ''
        } = options;
        
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal ${className}">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    ${showClose ? '<button class="modal-close">&times;</button>' : ''}
                </div>
                <div class="modal-body">${content}</div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Close handlers
        const closeModal = () => {
            overlay.classList.remove('active');
            setTimeout(() => {
                overlay.remove();
                if (onClose) onClose();
            }, 300);
        };
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });
        
        const closeBtn = overlay.querySelector('.modal-close');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        
        // Show modal
        requestAnimationFrame(() => {
            overlay.classList.add('active');
        });
        
        return {
            element: overlay,
            close: closeModal,
            setContent(html) {
                overlay.querySelector('.modal-body').innerHTML = html;
            }
        };
    },
    
    confirm(message, title = 'Bestätigen') {
        // XSS protection: escape HTML
        const escape = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
        
        return new Promise((resolve) => {
            const modal = this.create({
                title,
                content: `
                    <p style="margin-bottom: 1.5rem;">${escape(message)}</p>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button class="btn btn-secondary" id="modal-cancel">Abbrechen</button>
                        <button class="btn btn-primary" id="modal-confirm">Bestätigen</button>
                    </div>
                `,
                onClose: () => resolve(false)
            });
            
            modal.element.querySelector('#modal-cancel').addEventListener('click', () => {
                modal.close();
                resolve(false);
            });
            
            modal.element.querySelector('#modal-confirm').addEventListener('click', () => {
                modal.close();
                resolve(true);
            });
        });
    },
    
    alert(message, title = 'Hinweis') {
        return new Promise((resolve) => {
            const modal = this.create({
                title,
                content: `
                    <p style="margin-bottom: 1.5rem;">${message}</p>
                    <div style="display: flex; justify-content: flex-end;">
                        <button class="btn btn-primary" id="modal-ok">OK</button>
                    </div>
                `,
                onClose: () => resolve()
            });
            
            modal.element.querySelector('#modal-ok').addEventListener('click', () => {
                modal.close();
                resolve();
            });
        });
    }
};

// ========== SCROLL REVEAL ==========
const ScrollReveal = {
    init(selector = '.reveal', options = {}) {
        const defaultOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const config = { ...defaultOptions, ...options };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    if (!options.repeat) {
                        observer.unobserve(entry.target);
                    }
                } else if (options.repeat) {
                    entry.target.classList.remove('active');
                }
            });
        }, config);
        
        document.querySelectorAll(selector).forEach(el => {
            observer.observe(el);
        });
        
        return observer;
    }
};

// ========== LOADING STATES ==========
const Loading = {
    show(container, type = 'spinner') {
        if (typeof container === 'string') {
            container = document.querySelector(container);
        }
        if (!container) return;
        
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = type === 'skeleton' 
            ? this.getSkeletonHtml()
            : `<div class="spinner spinner-lg"></div>`;
        
        container.style.position = 'relative';
        container.appendChild(loader);
        
        return loader;
    },
    
    hide(loader) {
        if (loader) loader.remove();
    },
    
    getSkeletonHtml() {
        return `
            <div class="skeleton-wrapper" style="padding: 1rem;">
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text"></div>
            </div>
        `;
    },
    
    button(button, loading = true) {
        if (typeof button === 'string') {
            button = document.querySelector(button);
        }
        if (!button) return;
        
        if (loading) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<div class="spinner spinner-sm" style="border-color: rgba(255,255,255,0.3); border-top-color: #fff;"></div>`;
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.originalText || button.innerHTML;
            button.disabled = false;
        }
    }
};

// ========== SMOOTH SCROLL ==========
const SmoothScroll = {
    init() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const targetId = anchor.getAttribute('href');
                if (targetId === '#') return;
                
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
};

// ========== PARALLAX EFFECT ==========
const Parallax = {
    init(selector = '.parallax-element') {
        const elements = document.querySelectorAll(selector);
        if (!elements.length) return;
        
        const handleMove = (e) => {
            const { clientX, clientY } = e.touches ? e.touches[0] : e;
            const x = (clientX / window.innerWidth - 0.5) * 20;
            const y = (clientY / window.innerHeight - 0.5) * 20;
            
            elements.forEach(el => {
                const speed = el.dataset.speed || 1;
                el.style.transform = `translate(${x * speed}px, ${y * speed}px)`;
            });
        };
        
        document.addEventListener('mousemove', handleMove);
        document.addEventListener('touchmove', handleMove);
    }
};

// ========== TILT EFFECT ==========
const TiltEffect = {
    init(selector = '.tilt-card') {
        document.querySelectorAll(selector).forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            });
        });
    }
};

// ========== ANIMATION ON LOAD ==========
document.addEventListener('DOMContentLoaded', () => {
    // Initialize smooth scroll
    SmoothScroll.init();
    
    // Initialize scroll reveal
    ScrollReveal.init();
    
    // Add loaded class to body
    document.body.classList.add('page-loaded');
    
    // Stagger animations for lists
    document.querySelectorAll('.stagger-children').forEach(el => {
        el.classList.add('animate');
    });
});

// ========== UTILITY FUNCTIONS ==========
const UI = {
    // Debounce function
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle function
    throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Format number
    formatNumber(num) {
        return new Intl.NumberFormat('de-DE').format(num);
    },
    
    // Format date
    formatDate(date, options = {}) {
        const d = new Date(date);
        return d.toLocaleDateString('de-DE', options);
    },
    
    // Copy to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            Toast.success('In die Zwischenablage kopiert');
        } catch (err) {
            Toast.error('Kopieren fehlgeschlagen');
        }
    },
    
    // Random ID generator
    generateId(length = 8) {
        return Math.random().toString(36).substring(2, 2 + length);
    }
};

// Export for use in other scripts
window.Toast = Toast;
window.Modal = Modal;
window.Loading = Loading;
window.ScrollReveal = ScrollReveal;
window.UI = UI;
