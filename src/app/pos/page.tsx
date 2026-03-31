"use client";

import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/lib/queryClient';
import { ProductGrid, ProductGridHandle } from '@/components/pos/ProductGrid';
import { Cart, CartHandle } from '@/components/pos/Cart';
import { CheckoutModal } from '@/components/pos/CheckoutModal';
import { PosInitializer } from '@/components/pos/PosInitializer';
import { OfflineSyncManager } from '@/components/pos/OfflineSyncManager';
import { SettingsModal } from '@/components/pos/SettingsModal';
import { RecentSalesModal } from '@/components/pos/RecentSalesModal';
import { RegisterModal } from '@/components/pos/RegisterModal';
import { KeyboardHelpModal } from '@/components/pos/KeyboardHelpModal';
import { usePosKeyboard } from '@/hooks/usePosKeyboard';

import { useState, useRef, useCallback } from 'react';
import { ShoppingBag, LayoutGrid, Keyboard } from 'lucide-react';
import { usePosStore } from '@/store/posStore';

function PosContent() {
  const [activeMobileTab, setActiveMobileTab] = useState<'products' | 'cart'>('products');
  const cartItemsCount = usePosStore((state) => state.cart.length);
  const setKeyboardHelpOpen = usePosStore((state) => state.setKeyboardHelpOpen);

  // Refs into child imperativeHandles
  const gridRef = useRef<ProductGridHandle>(null);
  const cartRef = useRef<CartHandle>(null);

  // Stable callbacks for the keyboard hook
  const handleGridNavigate = useCallback((delta: number) => {
    (gridRef.current as any)?.__gridNavigate?.(delta);
  }, []);
  const handleGridEnter = useCallback(() => {
    (gridRef.current as any)?.__gridEnter?.();
  }, []);
  const handleCartNavigate = useCallback((delta: number) => {
    cartRef.current?.handleCartNavigate(delta);
  }, []);
  const handleCartAdjustQty = useCallback((delta: number) => {
    cartRef.current?.handleCartAdjustQty(delta);
  }, []);
  const handleCartRemove = useCallback(() => {
    cartRef.current?.handleCartRemove();
  }, []);

  // Search input ref — ProductGrid exposes this via imperative handle
  const searchInputRef = useRef<HTMLInputElement>(null);

  // Mount the global keyboard hook
  usePosKeyboard({
    searchInputRef,
    onGridNavigate: handleGridNavigate,
    onGridEnter: handleGridEnter,
    onCartNavigate: handleCartNavigate,
    onCartAdjustQty: handleCartAdjustQty,
    onCartRemove: handleCartRemove,
    gridProductCount: 0, // ProductGrid updates this internally
  });

  return (
    <main className="flex flex-col md:flex-row h-[100dvh] bg-white overflow-hidden text-gray-800 relative w-full">

      {/* Left Side: Product Grid & Search */}
      <section className={`flex-1 min-h-0 shadow-2xl z-10 w-full md:w-2/3 ${activeMobileTab === 'products' ? 'flex flex-col' : 'hidden md:flex md:flex-col'}`}>
        <ProductGrid ref={gridRef} />
      </section>

      {/* Right Side: The Cashier Cart */}
      <section className={`flex-1 md:flex-none min-h-0 shadow-[-10px_0_30px_-15px_rgba(0,0,0,0.1)] z-20 relative w-full md:w-1/3 md:min-w-[350px] md:max-w-[450px] ${activeMobileTab === 'cart' ? 'flex flex-col' : 'hidden md:flex md:flex-col'}`}>
        <Cart ref={cartRef} />
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

      {/* Keyboard shortcut hint — bottom right */}
      <button
        onClick={() => setKeyboardHelpOpen(true)}
        className="fixed bottom-4 right-4 z-40 hidden md:flex items-center gap-1.5 px-3 py-1.5 bg-white/80 backdrop-blur-xl border border-black/10 rounded-full shadow-sm text-[12px] font-medium text-[#86868b] hover:text-[#1d1d1f] hover:border-black/20 transition-all apple-btn"
        title="Keyboard shortcuts"
      >
        <Keyboard className="w-3.5 h-3.5" />
        Press ? for shortcuts
      </button>

      {/* Modal Overlays */}
      <CheckoutModal />
      <SettingsModal />
      <RecentSalesModal />
      <RegisterModal />
      <KeyboardHelpModal />
    </main>
  );
}

export default function PosPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <PosInitializer />
      <OfflineSyncManager />
      <PosContent />
    </QueryClientProvider>
  );
}