/**
 * POS IndexedDB Helper
 * إدارة التخزين المحلي للمعاملات والأصناف
 */

class POSIndexedDB {
    constructor() {
        this.dbName = 'POSDB';
        this.version = 2; // رفعنا الـ version لإضافة الـ stores الجديدة
        this.db = null;
    }

    async open() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => { this.db = request.result; resolve(this.db); };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                if (!db.objectStoreNames.contains('items')) {
                    const s = db.createObjectStore('items', { keyPath: 'id' });
                    s.createIndex('code', 'code', { unique: false });
                    s.createIndex('name', 'name', { unique: false });
                }

                if (!db.objectStoreNames.contains('categories')) {
                    db.createObjectStore('categories', { keyPath: 'id' });
                }

                if (!db.objectStoreNames.contains('transactions')) {
                    const s = db.createObjectStore('transactions', { keyPath: 'local_id' });
                    s.createIndex('sync_status', 'sync_status', { unique: false });
                    s.createIndex('created_at', 'created_at', { unique: false });
                    s.createIndex('server_id', 'server_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('held_orders')) {
                    const s = db.createObjectStore('held_orders', { keyPath: 'local_id' });
                    s.createIndex('sync_status', 'sync_status', { unique: false });
                    s.createIndex('server_id', 'server_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('customers')) {
                    const s = db.createObjectStore('customers', { keyPath: 'local_id' });
                    s.createIndex('sync_status', 'sync_status', { unique: false });
                    s.createIndex('phone', 'phone', { unique: false });
                }

                if (!db.objectStoreNames.contains('payouts')) {
                    const s = db.createObjectStore('payouts', { keyPath: 'local_id' });
                    s.createIndex('sync_status', 'sync_status', { unique: false });
                }
            };
        });
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    // ===== GENERIC HELPERS =====
    _put(storeName, data) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([storeName], 'readwrite');
            const req = tx.objectStore(storeName).put(data);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    _get(storeName, key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([storeName], 'readonly');
            const req = tx.objectStore(storeName).get(key);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    _getAll(storeName) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([storeName], 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    _getAllByIndex(storeName, indexName, value) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([storeName], 'readonly');
            const req = tx.objectStore(storeName).index(indexName).getAll(value);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    _delete(storeName, key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([storeName], 'readwrite');
            const req = tx.objectStore(storeName).delete(key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }

    async _updateField(storeName, key, fields) {
        if (!this.db) await this.open();
        const record = await this._get(storeName, key);
        if (!record) return;
        return this._put(storeName, { ...record, ...fields, updated_at: new Date().toISOString() });
    }

    // ===== ITEMS =====
    async saveItem(item) {
        if (!this.db) await this.open();
        return this._put('items', item);
    }

    async saveItems(items) {
        if (!this.db) await this.open();
        const tx = this.db.transaction(['items'], 'readwrite');
        const store = tx.objectStore('items');
        return Promise.all(items.map(item => new Promise((res, rej) => {
            const r = store.put(item);
            r.onsuccess = () => res();
            r.onerror = () => rej(r.error);
        })));
    }

    async getItem(itemId) {
        if (!this.db) await this.open();
        return this._get('items', itemId);
    }

    async getAllItems() {
        if (!this.db) await this.open();
        return this._getAll('items');
    }

    async searchItems(term) {
        if (!this.db) await this.open();
        const items = await this._getAll('items');
        const t = term.toLowerCase();
        return items.filter(i =>
            (i.name && String(i.name).toLowerCase().includes(t)) ||
            (i.code && String(i.code).toLowerCase().includes(t))
        );
    }

    async searchByBarcode(barcode) {
        if (!this.db) await this.open();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['items'], 'readonly');
            const store = tx.objectStore('items');
            const index = store.index('code');
            const exactReq = index.get(barcode);
            exactReq.onsuccess = () => {
                if (exactReq.result) { resolve([exactReq.result]); return; }
                const getAllReq = store.getAll();
                getAllReq.onsuccess = () => {
                    const b = String(barcode).toLowerCase();
                    resolve(getAllReq.result.filter(i => i.code && String(i.code).toLowerCase().includes(b)).slice(0, 10));
                };
                getAllReq.onerror = () => reject(getAllReq.error);
            };
            exactReq.onerror = () => reject(exactReq.error);
        });
    }

    async saveCategories(categories) {
        if (!this.db) await this.open();
        const tx = this.db.transaction(['categories'], 'readwrite');
        const store = tx.objectStore('categories');
        return Promise.all(categories.map(c => new Promise((res, rej) => {
            const r = store.put(c);
            r.onsuccess = () => res();
            r.onerror = () => rej(r.error);
        })));
    }

    async getCategories() {
        if (!this.db) await this.open();
        return this._getAll('categories');
    }

    // ===== TRANSACTIONS =====
    /**
     * حفظ معاملة — يرجع { local_id } دايماً
     */
    async saveTransaction(data) {
        if (!this.db) await this.open();
        const local_id = data.local_id || this.generateUUID();
        const record = {
            ...data,
            local_id,
            server_id: data.server_id || null,
            sync_status: 'pending',
            created_at: data.created_at || new Date().toISOString(),
            updated_at: new Date().toISOString(),
        };
        await this._put('transactions', record);
        return { local_id };
    }

    async getPendingTransactions() {
        if (!this.db) await this.open();
        return this._getAllByIndex('transactions', 'sync_status', 'pending');
    }

    async updateTransactionServerId(localId, serverId) {
        return this._updateField('transactions', localId, { server_id: serverId, sync_status: 'synced' });
    }

    async updateTransactionStatus(localId, status) {
        return this._updateField('transactions', localId, { sync_status: status });
    }

    async deleteTransaction(localId) {
        if (!this.db) await this.open();
        return this._delete('transactions', localId);
    }

    // ===== HELD ORDERS =====
    async saveHeldOrder(data) {
        if (!this.db) await this.open();
        const local_id = data.local_id || this.generateUUID();
        const record = {
            ...data,
            local_id,
            server_id: data.server_id || null,
            sync_status: data.sync_status || 'pending',
            held_at: data.held_at || new Date().toISOString(),
            updated_at: new Date().toISOString(),
        };
        await this._put('held_orders', record);
        return { local_id };
    }

    async getHeldOrders() {
        if (!this.db) await this.open();
        return this._getAll('held_orders');
    }

    async updateHeldOrderServerId(localId, serverId) {
        return this._updateField('held_orders', localId, { server_id: serverId, sync_status: 'synced' });
    }

    async deleteHeldOrder(localId) {
        if (!this.db) await this.open();
        return this._delete('held_orders', localId);
    }

    // ===== CUSTOMERS =====
    async saveCustomers(customers) {
        if (!this.db) await this.open();
        const tx = this.db.transaction(['customers'], 'readwrite');
        const store = tx.objectStore('customers');
        return Promise.all(customers.map(c => new Promise((res, rej) => {
            const record = { ...c, local_id: String(c.id || c.local_id || this.generateUUID()), sync_status: 'synced' };
            const r = store.put(record);
            r.onsuccess = () => res();
            r.onerror = () => rej(r.error);
        })));
    }

    async saveCustomer(data) {
        if (!this.db) await this.open();
        const local_id = data.local_id || this.generateUUID();
        const record = {
            ...data,
            local_id,
            server_id: data.server_id || null,
            sync_status: data.sync_status || 'pending',
            created_at: data.created_at || new Date().toISOString(),
            updated_at: new Date().toISOString(),
        };
        await this._put('customers', record);
        return { local_id };
    }

    async getPendingCustomers() {
        if (!this.db) await this.open();
        return this._getAllByIndex('customers', 'sync_status', 'pending');
    }

    async markCustomerSynced(localId, serverId) {
        return this._updateField('customers', localId, { server_id: serverId, sync_status: 'synced' });
    }

    async searchCustomersByPhone(phone) {
        if (!this.db) await this.open();
        const all = await this._getAll('customers');
        const p = String(phone).toLowerCase();
        return all.filter(c => c.phone && String(c.phone).toLowerCase().includes(p));
    }

    // ===== PAYOUTS =====
    async savePayout(data) {
        if (!this.db) await this.open();
        const local_id = data.local_id || this.generateUUID();
        const record = {
            ...data,
            local_id,
            sync_status: 'pending',
            created_at: data.created_at || new Date().toISOString(),
            updated_at: new Date().toISOString(),
        };
        await this._put('payouts', record);
        return { local_id };
    }

    async getPendingPayouts() {
        if (!this.db) await this.open();
        return this._getAllByIndex('payouts', 'sync_status', 'pending');
    }

    async markPayoutSynced(localId) {
        return this._updateField('payouts', localId, { sync_status: 'synced' });
    }

    // ===== RECENT TRANSACTIONS (localStorage) =====
    async saveRecentTransactions(transactions) {
        try {
            localStorage.setItem('pos_recent_transactions', JSON.stringify({
                data: transactions,
                saved_at: new Date().toISOString(),
            }));
        } catch (e) {}
        return Promise.resolve();
    }

    async getRecentTransactions(limit = 50) {
        try {
            const raw = localStorage.getItem('pos_recent_transactions');
            if (!raw) return [];
            return (JSON.parse(raw).data || []).slice(0, limit);
        } catch (e) { return []; }
    }
}

window.POSIndexedDB = POSIndexedDB;
