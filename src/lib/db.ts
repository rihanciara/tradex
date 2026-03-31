export const DB_NAME = 'tradex_pos_db';
export const DB_VERSION = 1;

export const STORES = {
  CATALOG: 'catalog',
  TAXONOMIES: 'taxonomies',
  CUSTOMERS: 'customers',
  SYNC_QUEUE: 'sync_queue',
};

export async function initDB(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    if (typeof window === 'undefined') {
      return reject(new Error('IndexedDB is not available on the server'));
    }

    const request = window.indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = (event) => {
      const db = (event.target as IDBOpenDBRequest).result;

      if (!db.objectStoreNames.contains(STORES.CATALOG)) {
        db.createObjectStore(STORES.CATALOG, { keyPath: 'key' });
      }
      if (!db.objectStoreNames.contains(STORES.TAXONOMIES)) {
        db.createObjectStore(STORES.TAXONOMIES, { keyPath: 'key' });
      }
      if (!db.objectStoreNames.contains(STORES.CUSTOMERS)) {
        db.createObjectStore(STORES.CUSTOMERS, { keyPath: 'key' });
      }
      if (!db.objectStoreNames.contains(STORES.SYNC_QUEUE)) {
        db.createObjectStore(STORES.SYNC_QUEUE, { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

export async function setVal(storeName: string, key: string, val: any): Promise<void> {
  const db = await initDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const request = store.put({ key, data: val });
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

export async function getVal(storeName: string, key: string): Promise<any> {
  const db = await initDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    const request = store.get(key);
    request.onsuccess = () => resolve(request.result?.data || null);
    request.onerror = () => reject(request.error);
  });
}

export async function addToQueue(storeName: string, val: any): Promise<number> {
  const db = await initDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const request = store.add({ payload: val, timestamp: new Date().toISOString() });
    request.onsuccess = () => resolve(request.result as number);
    request.onerror = () => reject(request.error);
  });
}

export async function getAllQueue(storeName: string): Promise<any[]> {
  const db = await initDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result || []);
    request.onerror = () => reject(request.error);
  });
}

export async function deleteFromQueue(storeName: string, id: number): Promise<void> {
  const db = await initDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const request = store.delete(id);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

/**
 * SECURITY: Wipe ALL data stores (catalog, taxonomies, customers).
 * Called on logout or user switch to prevent data leaks across sessions.
 * The sync_queue is preserved so offline sales are not lost.
 */
export async function nukeAllPosData(): Promise<void> {
  try {
    const db = await initDB();
    const storesToClear = [STORES.CATALOG, STORES.TAXONOMIES, STORES.CUSTOMERS];
    
    await Promise.all(storesToClear.map(storeName =>
      new Promise<void>((resolve, reject) => {
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const req = store.clear();
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
      })
    ));
    
    console.info('[POS Security] All cached data wiped for user session change.');
  } catch (err) {
    console.error('[POS Security] Failed to nuke cached data:', err);
  }
}

/**
 * Delete all records whose key starts with the given prefix.
 * Used for more granular scoped cleanup.
 */
export async function clearScopedKeys(storeName: string, prefix: string): Promise<void> {
  const db = await initDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const req = store.openCursor();
    req.onsuccess = (event) => {
      const cursor = (event.target as IDBRequest<IDBCursorWithValue>).result;
      if (cursor) {
        if (String(cursor.key).startsWith(prefix)) {
          cursor.delete();
        }
        cursor.continue();
      } else {
        resolve();
      }
    };
    req.onerror = () => reject(req.error);
  });
}
