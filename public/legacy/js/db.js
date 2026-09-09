/**
 * Gameday Score - IndexedDB Utility
 * Handles database operations for persistent storage.
 */

const DB_NAME = 'GamedayScoreDB';
const DB_VERSION = 1;

const STORES = {
    PREFERENCES: 'preferences',
    ACTIVE_GAME: 'activeGame',
    HISTORY: 'history'
};

const DB = {
    db: null,

    /**
     * Initialize the database
     */
    init() {
        return new Promise((resolve, reject) => {
            if (this.db) return resolve(this.db);

            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create stores if they don't exist
                if (!db.objectStoreNames.contains(STORES.PREFERENCES)) {
                    db.createObjectStore(STORES.PREFERENCES, { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains(STORES.ACTIVE_GAME)) {
                    db.createObjectStore(STORES.ACTIVE_GAME, { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains(STORES.HISTORY)) {
                    db.createObjectStore(STORES.HISTORY, { keyPath: 'id' });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error("Database error: " + event.target.errorCode);
                reject(event.target.errorCode);
            };
        });
    },

    /**
     * Generic get operation
     */
    async get(storeName, id) {
        await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(id);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Generic put (add/update) operation
     */
    async put(storeName, data) {
        await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(data);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Generic delete operation
     */
    async delete(storeName, id) {
        await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(id);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Get all items from a store
     */
    async getAll(storeName) {
        await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Clear all items from a store
     */
    async clear(storeName) {
        await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },

    // ----------------------------------------------------
    // Context-specific Helpers
    // ----------------------------------------------------

    async getSettings() {
        const record = await this.get(STORES.PREFERENCES, 'settings');
        return record ? record.value : null;
    },

    async saveSettings(settings) {
        return await this.put(STORES.PREFERENCES, { id: 'settings', value: settings });
    },

    async getCurrentGame() {
        const record = await this.get(STORES.ACTIVE_GAME, 'current');
        return record ? record.value : null;
    },

    async saveCurrentGame(game) {
        return await this.put(STORES.ACTIVE_GAME, { id: 'current', value: game });
    },

    async removeCurrentGame() {
        return await this.delete(STORES.ACTIVE_GAME, 'current');
    },

    async getHistory() {
        return await this.getAll(STORES.HISTORY);
    },

    async saveToHistory(game) {
        return await this.put(STORES.HISTORY, game);
    },

    async deleteFromHistory(id) {
        return await this.delete(STORES.HISTORY, id);
    },

    async clearHistory() {
        return await this.clear(STORES.HISTORY);
    }
};
