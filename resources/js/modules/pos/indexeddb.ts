export interface OfflineOrder {
    id: string; // ULID temporal o idempotency key
    payload: unknown;
    created_at: number;
}

const DB_NAME = 'omnipos_db';
const DB_VERSION = 1;

export class IndexedDBService {
    private static db: IDBDatabase | null = null;

    static async init(): Promise<IDBDatabase> {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains('cart')) {
                    db.createObjectStore('cart', { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains('offline_orders')) {
                    db.createObjectStore('offline_orders', { keyPath: 'id' });
                }
            };

            request.onsuccess = () => {
                this.db = request.result;
                resolve(request.result);
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    // Carrito
    static async saveCart(items: unknown[]): Promise<void> {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction('cart', 'readwrite');
            const store = transaction.objectStore('cart');

            // Limpiar previo y guardar
            const clearReq = store.clear();
            clearReq.onsuccess = () => {
                const addReq = store.put({ id: 'current_cart', items });
                addReq.onsuccess = () => resolve();
                addReq.onerror = () => reject(addReq.error);
            };
            clearReq.onerror = () => reject(clearReq.error);
        });
    }

    static async getCart(): Promise<unknown[]> {
        const db = await this.init();
        return new Promise((resolve) => {
            const transaction = db.transaction('cart', 'readonly');
            const store = transaction.objectStore('cart');
            const request = store.get('current_cart');

            request.onsuccess = () => {
                resolve(request.result ? request.result.items : []);
            };
            request.onerror = () => {
                resolve([]);
            };
        });
    }

    // Cola offline
    static async saveOfflineOrder(order: OfflineOrder): Promise<void> {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction('offline_orders', 'readwrite');
            const store = transaction.objectStore('offline_orders');
            const request = store.put(order);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    static async getOfflineOrders(): Promise<OfflineOrder[]> {
        const db = await this.init();
        return new Promise((resolve) => {
            const transaction = db.transaction('offline_orders', 'readonly');
            const store = transaction.objectStore('offline_orders');
            const request = store.getAll();

            request.onsuccess = () => {
                resolve(request.result || []);
            };
            request.onerror = () => {
                resolve([]);
            };
        });
    }

    static async deleteOfflineOrder(id: string): Promise<void> {
        const db = await this.init();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction('offline_orders', 'readwrite');
            const store = transaction.objectStore('offline_orders');
            const request = store.delete(id);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
}
