"use client"

/**
 * Offline Sync Worker
 * 
 * Listens for the browser `online` event. When the connection is restored,
 * it drains the SYNC_QUEUE from IndexedDB — sending each queued sale to the
 * real backend checkout endpoint one by one. Successfully synced items are
 * deleted from the queue. Failed items remain for next reconnection.
 */

import { useEffect, useRef, useState } from 'react';
import { getAllQueue, deleteFromQueue, STORES } from '@/lib/db';
import { apiClient } from '@/lib/apiClient';

export interface SyncStatus {
  isSyncing: boolean;
  lastSyncedCount: number;
  failedCount: number;
}

export function useOfflineSync(): SyncStatus {
  const [status, setStatus] = useState<SyncStatus>({
    isSyncing: false,
    lastSyncedCount: 0,
    failedCount: 0,
  });
  const isSyncingRef = useRef(false);

  const drainQueue = async () => {
    if (isSyncingRef.current) return;
    if (typeof window === 'undefined') return;

    let items: any[] = [];
    try {
      items = await getAllQueue(STORES.SYNC_QUEUE);
    } catch {
      return;
    }

    if (items.length === 0) return;

    isSyncingRef.current = true;
    setStatus({ isSyncing: true, lastSyncedCount: 0, failedCount: 0 });

    let synced = 0;
    let failed = 0;

    for (const item of items) {
      try {
        const response = await apiClient.post('/pos/checkout', item.payload);
        if (response.data?.success) {
          await deleteFromQueue(STORES.SYNC_QUEUE, item.id);
          synced++;
        } else {
          failed++;
        }
      } catch {
        // Network still flaky or auth issue — leave in queue, try next reconnect
        failed++;
      }
    }

    isSyncingRef.current = false;
    setStatus({ isSyncing: false, lastSyncedCount: synced, failedCount: failed });

    if (synced > 0) {
      console.info(`[OfflineSync] Synced ${synced} offline sale(s) to server.`);
    }
    if (failed > 0) {
      console.warn(`[OfflineSync] ${failed} sale(s) still pending — will retry on next reconnect.`);
    }
  };

  useEffect(() => {
    if (typeof window === 'undefined') return;

    // Try to drain on mount (in case app was closed while offline and re-opened online)
    if (navigator.onLine) {
      drainQueue();
    }

    const handleOnline = () => {
      console.info('[OfflineSync] Network restored. Attempting to sync offline sales...');
      drainQueue();
    };

    window.addEventListener('online', handleOnline);
    return () => window.removeEventListener('online', handleOnline);
  }, []);

  return status;
}
