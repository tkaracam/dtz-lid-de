/**
 * IndexedDB Wrapper for Offline Support
 * DTZ Learning Platform
 */

const DB_NAME = 'DTZLearningDB';
const DB_VERSION = 1;

const STORES = {
    USER_DATA: 'userData',
    QUESTIONS: 'questions',
    ANSWERS: 'answers',
    PROGRESS: 'progress',
    CACHE: 'apiCache',
    SETTINGS: 'settings'
};

class DTZDatabase {
    constructor() {
        this.db = null;
        this.initPromise = null;
    }

    async init() {
        if (this.db) return this.db;
        if (this.initPromise) return this.initPromise;

        this.initPromise = new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // User data store
                if (!db.objectStoreNames.contains(STORES.USER_DATA)) {
                    const userStore = db.createObjectStore(STORES.USER_DATA, { keyPath: 'key' });
                    userStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // Questions store
                if (!db.objectStoreNames.contains(STORES.QUESTIONS)) {
                    const qStore = db.createObjectStore(STORES.QUESTIONS, { keyPath: 'id' });
                    qStore.createIndex('module', 'module', { unique: false });
                    qStore.createIndex('teil', 'teil', { unique: false });
                    qStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // Answers store (for offline sync)
                if (!db.objectStoreNames.contains(STORES.ANSWERS)) {
                    const aStore = db.createObjectStore(STORES.ANSWERS, { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    aStore.createIndex('synced', 'synced', { unique: false });
                    aStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // Progress store
                if (!db.objectStoreNames.contains(STORES.PROGRESS)) {
                    const pStore = db.createObjectStore(STORES.PROGRESS, { keyPath: 'key' });
                    pStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // API Cache store
                if (!db.objectStoreNames.contains(STORES.CACHE)) {
                    const cStore = db.createObjectStore(STORES.CACHE, { keyPath: 'key' });
                    cStore.createIndex('expires', 'expires', { unique: false });
                }

                // Settings store
                if (!db.objectStoreNames.contains(STORES.SETTINGS)) {
                    db.createObjectStore(STORES.SETTINGS, { keyPath: 'key' });
                }
            };
        });

        return this.initPromise;
    }

    // Generic CRUD operations
    async put(storeName, data) {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            
            const item = {
                ...data,
                timestamp: Date.now()
            };
            
            const request = store.put(item);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async get(storeName, key) {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const request = store.get(key);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getAll(storeName) {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const request = store.getAll();
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async delete(storeName, key) {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.delete(key);
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async clear(storeName) {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.clear();
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // Query by index
    async getByIndex(storeName, indexName, value) {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(value);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // API Cache methods with expiration
    async cacheApiResponse(key, data, ttlMinutes = 60) {
        const cacheItem = {
            key,
            data,
            expires: Date.now() + (ttlMinutes * 60 * 1000),
            timestamp: Date.now()
        };
        return this.put(STORES.CACHE, cacheItem);
    }

    async getCachedApiResponse(key) {
        const cached = await this.get(STORES.CACHE, key);
        
        if (!cached) return null;
        
        // Check expiration
        if (cached.expires < Date.now()) {
            await this.delete(STORES.CACHE, key);
            return null;
        }
        
        return cached.data;
    }

    // Offline answers methods
    async saveOfflineAnswer(answerData) {
        const item = {
            ...answerData,
            synced: false,
            timestamp: Date.now()
        };
        return this.put(STORES.ANSWERS, item);
    }

    async getUnsyncedAnswers() {
        return this.getByIndex(STORES.ANSWERS, 'synced', false);
    }

    async markAnswerSynced(id) {
        const answer = await this.get(STORES.ANSWERS, id);
        if (answer) {
            answer.synced = true;
            await this.put(STORES.ANSWERS, answer);
        }
    }

    // Settings methods
    async setSetting(key, value) {
        return this.put(STORES.SETTINGS, { key, value });
    }

    async getSetting(key, defaultValue = null) {
        const item = await this.get(STORES.SETTINGS, key);
        return item ? item.value : defaultValue;
    }

    // User data methods
    async setUserData(key, value) {
        return this.put(STORES.USER_DATA, { key, value });
    }

    async getUserData(key) {
        const item = await this.get(STORES.USER_DATA, key);
        return item ? item.value : null;
    }

    // Progress tracking
    async saveProgress(key, progress) {
        return this.put(STORES.PROGRESS, { key, ...progress });
    }

    async getProgress(key) {
        return this.get(STORES.PROGRESS, key);
    }

    // Cache cleanup
    async cleanupExpiredCache() {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORES.CACHE, 'readwrite');
            const store = tx.objectStore(STORES.CACHE);
            const index = store.index('expires');
            const now = Date.now();
            
            const request = index.openCursor(IDBKeyRange.upperBound(now));
            let deleted = 0;
            
            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    store.delete(cursor.primaryKey);
                    deleted++;
                    cursor.continue();
                } else {
                    resolve(deleted);
                }
            };
            
            request.onerror = () => reject(request.error);
        });
    }

    // Get database stats
    async getStats() {
        const stores = Object.values(STORES);
        const stats = {};
        
        for (const storeName of stores) {
            const items = await this.getAll(storeName);
            stats[storeName] = items.length;
        }
        
        return stats;
    }
}

// Create global instance
const db = new DTZDatabase();

// Offline API wrapper
const OfflineAPI = {
    async fetch(url, options = {}, cacheOptions = {}) {
        const { 
            useCache = true, 
            cacheTtl = 60,
            cacheKey = null 
        } = cacheOptions;
        
        const key = cacheKey || `${options.method || 'GET'}:${url}`;
        
        // Try cache first if offline or useCache is enabled
        if (useCache) {
            const cached = await db.getCachedApiResponse(key);
            if (cached) {
                console.log('[OfflineAPI] Serving from cache:', url);
                return { data: cached, fromCache: true };
            }
        }
        
        // Try network
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            // Cache successful response
            if (useCache) {
                await db.cacheApiResponse(key, data, cacheTtl);
            }
            
            return { data, fromCache: false };
        } catch (error) {
            // Network failed, try cache as fallback
            if (useCache) {
                const cached = await db.getCachedApiResponse(key);
                if (cached) {
                    console.log('[OfflineAPI] Network failed, serving from cache:', url);
                    return { data: cached, fromCache: true, offline: true };
                }
            }
            
            throw error;
        }
    },
    
    // Queue answer for later sync
    async queueAnswer(answerData) {
        const id = await db.saveOfflineAnswer(answerData);
        
        // Register for background sync if available
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            const registration = await navigator.serviceWorker.ready;
            await registration.sync.register('sync-answers');
        }
        
        return id;
    },
    
    // Sync pending answers
    async syncPendingAnswers() {
        const pending = await db.getUnsyncedAnswers();
        const results = [];
        
        for (const answer of pending) {
            try {
                const response = await fetch(`${API_BASE}/questions/submit.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${getToken()}`
                    },
                    body: JSON.stringify(answer.data)
                });
                
                if (response.ok) {
                    await db.markAnswerSynced(answer.id);
                    results.push({ id: answer.id, success: true });
                } else {
                    results.push({ id: answer.id, success: false, error: 'Server error' });
                }
            } catch (error) {
                results.push({ id: answer.id, success: false, error: error.message });
            }
        }
        
        return results;
    }
};

// Network status monitoring
const NetworkStatus = {
    isOnline: navigator.onLine,
    listeners: [],
    
    init() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.notifyListeners(true);
            console.log('[NetworkStatus] Back online');
            
            // Sync pending data
            OfflineAPI.syncPendingAnswers().then((results) => {
                const successCount = results.filter(r => r.success).length;
                if (successCount > 0) {
                    Toast.success(`${successCount} Antworten synchronisiert`);
                }
            });
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.notifyListeners(false);
            console.log('[NetworkStatus] Gone offline');
            Toast.warning('Sie sind offline. Antworten werden lokal gespeichert.');
        });
    },
    
    addListener(callback) {
        this.listeners.push(callback);
    },
    
    removeListener(callback) {
        this.listeners = this.listeners.filter(cb => cb !== callback);
    },
    
    notifyListeners(online) {
        this.listeners.forEach(cb => cb(online));
    }
};

// Initialize network monitoring
document.addEventListener('DOMContentLoaded', () => {
    NetworkStatus.init();
});

// Export
window.db = db;
window.OfflineAPI = OfflineAPI;
window.NetworkStatus = NetworkStatus;
