"use client";

import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/lib/queryClient';
import { ProductGrid } from '@/components/pos/ProductGrid';
import { Cart } from '@/components/pos/Cart';
import { CheckoutModal } from '@/components/pos/CheckoutModal';
import { PosInitializer } from '@/components/pos/PosInitializer';
import { OfflineSyncManager } from '@/components/pos/OfflineSyncManager';
import { SettingsModal } from '@/components/pos/SettingsModal';

import { useState } from 'react';
import { ShoppingBag, LayoutGrid } from 'lucide-react';
import { usePosStore } from '@/store/posStore';

export default function PosPage() {
  const [activeMobileTab, setActiveMobileTab] = useState<'products' | 'cart'>('products');
  const cartItemsCount = usePosStore((state) => state.cart.length);

  return (
    <QueryClientProvider client={queryClient}>
      <PosInitializer />
      <OfflineSyncManager />
      <main className="flex flex-col md:flex-row h-[100dvh] bg-white overflow-hidden text-gray-800 relative w-full">
        
        {/* Left Side: Product Grid & Search */}
        <section className={`flex-1 min-h-0 shadow-2xl z-10 w-full md:w-2/3 ${activeMobileTab === 'products' ? 'flex flex-col' : 'hidden md:flex md:flex-col'}`}>
          <ProductGrid />
        </section>

        {/* Right Side: The Cashier Cart */}
        <section className={`flex-1 md:flex-none min-h-0 shadow-[-10px_0_30px_-15px_rgba(0,0,0,0.1)] z-20 relative w-full md:w-1/3 md:min-w-[350px] md:max-w-[450px] ${activeMobileTab === 'cart' ? 'flex flex-col' : 'hidden md:flex md:flex-col'}`}>
          <Cart />
        </section>

        {/* Mobile Tab Navigation */}
        <div className="md:hidden flex bg-[#fbfbfd]/90 backdrop-blur-xl border-t border-black/10 z-50">
          <button 
            onClick={() => setActiveMobileTab('products')}
            className={`flex-1 py-3 text-[11px] font-semibold flex flex-col items-center justify-center gap-1 transition-colors ${activeMobileTab === 'products' ? 'text-[#0071e3]' : 'text-[#86868b] hover:text-[#1d1d1f]'}`}
          >
            <LayoutGrid className="w-6 h-6" />
            Products
          </button>
          <button 
            onClick={() => setActiveMobileTab('cart')}
            className={`flex-1 py-3 text-[11px] font-semibold flex flex-col items-center justify-center gap-1 relative transition-colors ${activeMobileTab === 'cart' ? 'text-[#0071e3]' : 'text-[#86868b] hover:text-[#1d1d1f]'}`}
          >
            <div className="relative">
              <ShoppingBag className="w-6 h-6" />
              {cartItemsCount > 0 && (
                <span className="absolute -top-1 -right-2 bg-[#ff3b30] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                  {cartItemsCount}
                </span>
              )}
            </div>
            Current Bag
          </button>
        </div>

        {/* Checkout Modal Overlay */}
        <CheckoutModal />

        {/* Settings Modal Overlay */}
        <SettingsModal />
      </main>
    </QueryClientProvider>
  );
}