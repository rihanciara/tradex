"use client";

import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/lib/queryClient';
import { ProductGrid } from '@/components/pos/ProductGrid';
import { Cart } from '@/components/pos/Cart';
import { CheckoutModal } from '@/components/pos/CheckoutModal';
import { PosInitializer } from '@/components/pos/PosInitializer';
import { OfflineSyncManager } from '@/components/pos/OfflineSyncManager';
import { SettingsModal } from '@/components/pos/SettingsModal';

export default function PosPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <PosInitializer />
      <OfflineSyncManager />
      <main className="flex h-screen bg-white overflow-hidden text-gray-800">
        
        {/* Left Side: Product Grid & Search */}
        <section className="flex-1 h-full w-2/3 shadow-2xl z-10">
          <ProductGrid />
        </section>

        {/* Right Side: The Cashier Cart */}
        <section className="w-1/3 min-w-[350px] max-w-[450px] h-full shadow-[-10px_0_30px_-15px_rgba(0,0,0,0.1)] z-20 relative">
          <Cart />
        </section>

        {/* Checkout Modal Overlay */}
        <CheckoutModal />

        {/* Settings Modal Overlay */}
        <SettingsModal />
      </main>
    </QueryClientProvider>
  );
}