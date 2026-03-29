"use client";

import { useEffect, useState } from 'react';
import { getAllQueue, deleteFromQueue, STORES } from '@/lib/db';
import { apiClient } from '@/lib/apiClient';
import { Wifi, WifiOff, RefreshCcw } from 'lucide-react';

export function OfflineSyncManager() {
  const [isOnline, setIsOnline] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [queueCount, setQueueCount] = useState(0);

  const checkQueue = async () => {
    try {
      const items = await getAllQueue(STORES.SYNC_QUEUE);
      setQueueCount(items.length);
    } catch (e) {
      console.error('Error reading queue', e);
    }
  };

  const processQueue = async () => {
    if (!navigator.onLine || syncing) return;
    
    try {
      const items = await getAllQueue(STORES.SYNC_QUEUE);
      if (items.length === 0) return;
      
      setSyncing(true);
      
      for (const item of items) {
        try {
          // Re-attempt checkout via direct apiClient to avoid interceptor queue loops
          await apiClient.post('/pos/checkout', item.payload);
          await deleteFromQueue(STORES.SYNC_QUEUE, item.id);
        } catch (err: any) {
          // If it's a 4xx error (e.g., validation failed), it won't succeed next time either
          // Usually we'd move it to a dead-letter queue, but for simplicity we'll delete it 
          // or alert the user. If it's a 5xx or network err, we keep it in queue.
          if (err.response && err.response.status >= 400 && err.response.status < 500) {
            console.error('Validation error for offline checkout', err.response.data);
            await deleteFromQueue(STORES.SYNC_QUEUE, item.id);
          }
        }
      }
    } finally {
      setSyncing(false);
      checkQueue();
    }
  };

  useEffect(() => {
    if (typeof window === 'undefined') return;

    setIsOnline(navigator.onLine);
    checkQueue();

    const handleOnline = () => {
      setIsOnline(true);
      processQueue();
    };

    const handleOffline = () => {
      setIsOnline(false);
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // Periodically check queue just in case
    const interval = setInterval(() => {
      checkQueue();
      if (navigator.onLine) processQueue();
    }, 30000);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      clearInterval(interval);
    };
  }, []);

  if (isOnline && queueCount === 0) return null;

  return (
    <div className={`fixed bottom-6 right-6 px-4 py-3 rounded-2xl shadow-xl flex items-center gap-3 backdrop-blur-xl z-50 transition-all ${
      !isOnline 
        ? 'bg-[#ff3b30]/90 text-white border border-[#ff3b30]' 
        : 'bg-[#0071e3]/90 text-white border border-[#0071e3]'
    }`}>
      {!isOnline ? (
        <>
          <WifiOff className="w-5 h-5" />
          <span className="font-semibold text-[15px]">Offline Mode</span>
        </>
      ) : (
        <>
          <Wifi className="w-5 h-5" />
          <span className="font-semibold text-[15px]">Online</span>
        </>
      )}

      {queueCount > 0 && (
        <div className="flex items-center gap-2 pl-3 border-l border-white/20">
          <span className="text-[13px] font-medium bg-black/20 px-2 py-0.5 rounded-md">
            {queueCount} pending
          </span>
          {syncing && <RefreshCcw className="w-4 h-4 animate-spin" />}
        </div>
      )}
    </div>
  );
}
