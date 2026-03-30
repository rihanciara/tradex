"use client";

import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { fetchInit } from '@/lib/api';
import { usePosStore } from '@/store/posStore';

export function PosInitializer() {
  const setInitData = usePosStore((state) => state.setInitData);

  const { data, isLoading, error } = useQuery({
    queryKey: ['posInit'],
    queryFn: () => fetchInit(),
  });

  useEffect(() => {
    if (data?.success && data.data) {
      setInitData(data.data);
    }
  }, [data, setInitData]);

  if (isLoading) {
    return (
      <div className="absolute inset-0 bg-white/80 z-50 flex items-center justify-center backdrop-blur-sm">
        <div className="flex flex-col items-center space-y-4">
          <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          <p className="text-gray-600 font-medium">Initializing POS Terminal...</p>
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
             {(error as any)?.response?.data?.msg || (error as any)?.response?.data?.message || error?.message || "Unknown Network Error"}
          </div>
        </div>
      </div>
    );
  }

  return null;
}
