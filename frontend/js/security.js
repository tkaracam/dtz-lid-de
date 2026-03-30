/**
 * Security utilities for DTZ Learning Platform
 * XSS protection, input sanitization, and security helpers
 */

const Security = {
    /**
     * Escape HTML special characters to prevent XSS
     * @param {string} text - Raw text to escape
     * @returns {string} Escaped HTML-safe text
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const str = String(text);
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Sanitize user input - removes dangerous HTML tags
     * @param {string} input - User input
     * @returns {string} Sanitized text
     */
    sanitizeInput(input) {
        if (typeof input !== 'string') return '';
        
        // Remove script tags and event handlers
        return input
            .replace(/<script[^>]*>.*?<\/script>/gi, '')
            .replace(/<script[^>]*>/gi, '')
            .replace(/javascript:/gi, '')
            .replace(/on\w+\s*=/gi, '')
            .replace(/<iframe[^>]*>.*?<\/iframe>/gi, '');
    },

    /**
     * Create safe HTML element with text content
     * @param {string} tag - HTML tag name
     * @param {string} text - Text content
     * @param {object} attrs - Optional attributes
     * @returns {HTMLElement}
     */
    createElement(tag, text = '', attrs = {}) {
        const el = document.createElement(tag);
        el.textContent = text;
        
        // Only allow safe attributes
        const safeAttrs = ['class', 'id', 'data-id', 'data-action', 'href', 'src', 'alt', 'title'];
        for (const [key, value] of Object.entries(attrs)) {
            if (safeAttrs.includes(key)) {
                if (key === 'href' || key === 'src') {
                    // Sanitize URLs
                    el.setAttribute(key, this.sanitizeUrl(value));
                } else {
                    el.setAttribute(key, String(value));
                }
            }
        }
        
        return el;
    },

    /**
     * Sanitize URL to prevent javascript: injection
     * @param {string} url - URL to sanitize
     * @returns {string} Safe URL or empty string
     */
    sanitizeUrl(url) {
        if (!url) return '';
        const str = String(url).trim().toLowerCase();
        
        // Block javascript: and data: URLs
        if (str.startsWith('javascript:') || str.startsWith('data:')) {
            return '';
        }
        
        return url;
    },

    /**
     * Validate email format
     * @param {string} email - Email to validate
     * @returns {boolean}
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase().trim());
    },

    /**
     * Validate password strength
     * @param {string} password - Password to validate
     * @returns {object} Validation result
     */
    validatePassword(password) {
        const result = {
            valid: false,
            errors: []
        };

        if (password.length < 8) {
            result.errors.push('Mindestens 8 Zeichen');
        }
        if (!/[A-Z]/.test(password)) {
            result.errors.push('Mindestens ein Großbuchstabe');
        }
        if (!/[a-z]/.test(password)) {
            result.errors.push('Mindestens ein Kleinbuchstabe');
        }
        if (!/[0-9]/.test(password)) {
            result.errors.push('Mindestens eine Zahl');
        }

        result.valid = result.errors.length === 0;
        return result;
    },

    /**
     * Generate CSRF token
     * @returns {string} Random token
     */
    generateToken() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    },

    /**
     * Store CSRF token in sessionStorage
     */
    initCsrfToken() {
        let token = sessionStorage.getItem('csrf_token');
        if (!token) {
            token = this.generateToken();
            sessionStorage.setItem('csrf_token', token);
        }
        return token;
    },

    /**
     * Get CSRF token for API requests
     * @returns {string|null}
     */
    getCsrfToken() {
        return sessionStorage.getItem('csrf_token');
    },

    /**
     * Clear all sensitive data from storage
     */
    clearSensitiveData() {
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        sessionStorage.removeItem('csrf_token');
    },

    /**
     * Check for common injection patterns
     * @param {string} input - Input to check
     * @returns {boolean} True if suspicious
     */
    isSuspiciousInput(input) {
        if (typeof input !== 'string') return false;
        
        const patterns = [
            /<script/i,
            /javascript:/i,
            /on\w+\s*=/i,
            /\.\.\//,
            /%3Cscript/i,  // URL encoded
            /%22%3E/,      // ">
            /union\s+select/i,
            /drop\s+table/i,
            /insert\s+into/i,
            /delete\s+from/i
        ];
        
        return patterns.some(pattern => pattern.test(input));
    },

    /**
     * Rate limiter for API calls
     * @param {string} key - Rate limit key
     * @param {number} limit - Max calls
     * @param {number} windowMs - Time window in ms
     * @returns {boolean} True if allowed
     */
    rateLimit(key, limit = 10, windowMs = 60000) {
        const now = Date.now();
        const storageKey = `ratelimit_${key}`;
        const data = JSON.parse(sessionStorage.getItem(storageKey) || '{"count":0,"reset":0}');
        
        if (now > data.reset) {
            data.count = 0;
            data.reset = now + windowMs;
        }
        
        data.count++;
        sessionStorage.setItem(storageKey, JSON.stringify(data));
        
        return data.count <= limit;
    },

    /**
     * Secure API wrapper with CSRF and rate limiting
     */
    async secureApi(url, options = {}) {
        // Rate limiting
        const endpoint = url.split('/').pop();
        if (!this.rateLimit(endpoint, 30, 60000)) {
            throw new Error('Zu viele Anfragen. Bitte warten Sie einen Moment.');
        }

        const csrfToken = this.getCsrfToken();
        const headers = {
            ...options.headers,
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        };

        return fetch(url, {
            ...options,
            headers,
            credentials: 'same-origin'
        });
    }
};

// Initialize CSRF token on load
Security.initCsrfToken();

// Export
window.Security = Security;
