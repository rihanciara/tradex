"use client";

import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { fetchInit } from '@/lib/api';
import { usePosStore } from '@/store/posStore';
import { getUserScope, setUserScope, isSameUser } from '@/lib/userScope';
import { nukeAllPosData } from '@/lib/db';

export function PosInitializer() {
  const setInitData = usePosStore((state) => state.setInitData);

  const { data, isLoading, error } = useQuery({
    queryKey: ['posInit'],
    queryFn: () => fetchInit(),
    // Never serve stale init data — always re-validate from server
    staleTime: 0,
    gcTime: 0,
  });

  useEffect(() => {
    if (!data?.success || !data.data) return;

    const { user, business: _b } = data.data;
    // The backend returns business info but not business_id directly.
    // We'll derive it from the user scope embedded in the init response.
    // The location_id is per-business so we use user.id + location_id as compound identity.
    const incomingUserId = user?.id;
    const incomingLocationId = data.data.location_id ?? 0;

    if (!incomingUserId) {
      setInitData(data.data);
      return;
    }

    const currentScope = getUserScope();

    // 🔴 CRITICAL: If a different user is loading the POS in the same browser,
    // destroy ALL cached data from the previous session before proceeding.
    if (currentScope && !isSameUser(incomingUserId, incomingLocationId)) {
      console.warn(
        `[POS Security] User changed: ${currentScope.userId}→${incomingUserId}. Nuking all cached data.`
      );
      nukeAllPosData().then(() => {
        setUserScope(incomingUserId, incomingLocationId);
        setInitData(data.data);
      });
    } else {
      // Same user or first load — set/refresh scope and proceed
      setUserScope(incomingUserId, incomingLocationId);
      setInitData(data.data);
    }
  }, [data, setInitData]);

  if (isLoading) {
    return (
      <div className="absolute inset-0 bg-white/80 z-50 flex items-center justify-center backdrop-blur-sm">
        <div className="flex flex-col items-center space-y-4">
          <div className="w-12 h-12 border-4 border-[#0071e3] border-t-transparent rounded-full animate-spin"></div>
          <p className="text-[#1d1d1f] font-semibold">Initializing POS Terminal...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="absolute inset-0 bg-red-50 z-50 flex items-center justify-center p-4 text-center">
        <div className="bg-white p-6 rounded-xl shadow-xl max-w-md">
          <div className="text-red-500 w-16 h-16 mx-auto mb-4">
             <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
             </svg>
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">Initialization Failed</h2>
          <p className="text-gray-600 font-medium mb-1">Could not initialize the Point of Sale system:</p>
          <div className="bg-red-100 p-3 rounded text-red-700 text-sm font-mono mt-3 break-words text-left">
             {(error as any)?.response?.data?.msg || (error as any)?.response?.data?.message || (error as Error)?.message || "Unknown Network Error"}
          </div>
        </div>
      </div>
    );
  }

  return null;
}
