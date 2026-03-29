import { QueryClient } from '@tanstack/react-query';

// Configure React Query Client for optimal Offline-First caching
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 60, // Data stays fresh for 1 hour (TradeX Performance)
      gcTime: 1000 * 60 * 60 * 24, // Keep cached data for 24 hours (IndexedDB prep)
      refetchOnWindowFocus: false, // Do not refetch on tab switch (Saves bandwidth)
      retry: 2, // Retry failed requests twice
    },
  },
});