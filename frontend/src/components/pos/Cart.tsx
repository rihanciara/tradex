"use client"

import { usePosStore } from '@/store/posStore';
import { Minus, Plus, ShoppingCart, Apple } from 'lucide-react';
import { CustomerSelect } from './CustomerSelect';

export function Cart() {
  const { cart, removeFromCart, updateQuantity, clearCart, cartTotal, setCheckoutOpen } = usePosStore();

  const formattedTotal = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cartTotal());

  if (cart.length === 0) {
    return (
      <div className="h-full flex flex-col items-center justify-center bg-[#fbfbfd] p-6 apple-glass">
        <div className="w-28 h-28 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm border border-gray-100">
          <ShoppingCart className="w-10 h-10 text-gray-300 stroke-[1.5]" />
        </div>
        <p className="text-[#1d1d1f] text-[17px] font-semibold tracking-tight">Your cart is empty.</p>
        <p className="text-[#86868b] text-[15px] mt-1.5 font-medium">Scan an item or select a product.</p>
      </div>
    );
  }

  return (
    <div className="flex flex-col h-full bg-[#fbfbfd] apple-glass shadow-[-20px_0_40px_-20px_rgba(0,0,0,0.05)] border-l border-white/40">
      
      {/* Apple-style Header */}
      <div className="pt-8 pb-4 px-6 border-b border-black/5 flex justify-between items-center bg-white/60 backdrop-blur-xl sticky top-0 z-20">
        <h2 className="text-[24px] font-bold text-[#1d1d1f] tracking-[-0.01em] flex items-center">
          Bag
          <span className="ml-2.5 bg-[#f5f5f7] text-[#1d1d1f] py-0.5 px-2.5 rounded-full text-sm font-semibold border border-black/5">
            {cart.length}
          </span>
        </h2>
        <button 
          onClick={clearCart}
          className="text-[13px] font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors apple-btn flex items-center bg-[#f5f5f7] hover:bg-[#e8e8ed] px-3 py-1.5 rounded-full"
        >
          Clear
        </button>
      </div>

      {/* Customer Selection */}
      <div className="px-6 pt-4 pb-2 z-10 relative">
        <CustomerSelect />
      </div>

      {/* Cart Items List */}
      <div className="flex-1 overflow-y-auto px-6 py-2 pb-24">
        <div className="space-y-4 pt-2">
          {cart.map((item) => (
            <div 
              key={item.cart_id} 
              className="group flex flex-col pt-4 pb-5 border-b border-black/5 last:border-0 relative"
            >
              <div className="flex justify-between items-start">
                <div className="pr-4 flex-1">
                  <h4 className="text-[17px] font-semibold text-[#1d1d1f] leading-tight">
                    {item.product_name}
                  </h4>
                  {item.variation_name !== 'DUMMY' && item.variation_name !== item.product_name && (
                    <p className="text-[13px] text-[#86868b] mt-1 font-medium">{item.variation_name}</p>
                  )}
                  <p className="text-[11px] text-[#86868b] font-mono mt-1 opacity-60">
                    SKU: {item.variation_sku || item.product_sku}
                  </p>
                </div>
                
                <div className="text-right whitespace-nowrap pl-2">
                  <span className="text-[17px] font-semibold text-[#1d1d1f] block tracking-tight">
                    ${(item.final_price * item.quantity).toFixed(2)}
                  </span>
                </div>
              </div>

              {/* Minimal Quantity Controls */}
              <div className="flex justify-between items-center mt-4">
                <div className="flex items-center bg-white border border-black/5 rounded-full shadow-sm px-1 py-1">
                  <button 
                    onClick={() => updateQuantity(item.cart_id, Math.max(1, item.quantity - 1))}
                    className="w-7 h-7 flex items-center justify-center rounded-full text-[#1d1d1f] hover:bg-[#f5f5f7] apple-btn"
                  >
                    <Minus className="w-3.5 h-3.5" />
                  </button>
                  <input 
                    type="number" 
                    value={item.quantity}
                    onChange={(e) => updateQuantity(item.cart_id, Math.max(1, Number(e.target.value)))}
                    className="w-10 text-center text-[15px] font-semibold text-[#1d1d1f] bg-transparent border-none focus:ring-0 p-0"
                    min="1"
                  />
                  <button 
                    onClick={() => updateQuantity(item.cart_id, item.quantity + 1)}
                    className="w-7 h-7 flex items-center justify-center rounded-full text-[#1d1d1f] hover:bg-[#f5f5f7] apple-btn"
                  >
                    <Plus className="w-3.5 h-3.5" />
                  </button>
                </div>

                <button 
                  onClick={() => removeFromCart(item.cart_id)}
                  className="text-[13px] text-[#0071e3] hover:text-[#0077ed] font-medium apple-btn px-2"
                >
                  Remove
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Floating Apple Pay / Checkout Footer */}
      <div className="absolute bottom-0 left-0 right-0 p-6 bg-white/80 backdrop-blur-2xl border-t border-black/5 z-30 pb-8">
        <div className="flex justify-between items-baseline mb-5 px-1">
          <span className="text-[17px] text-[#1d1d1f] font-medium">Total</span>
          <span className="text-[28px] font-bold text-[#1d1d1f] tracking-tight">
            {formattedTotal}
          </span>
        </div>
        
        <div className="space-y-3">
          {/* Main Checkout Button */}
          <button 
            onClick={() => setCheckoutOpen(true)}
            className="w-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold py-3.5 rounded-2xl text-[17px] shadow-sm apple-btn flex items-center justify-center">
            Review Order
          </button>
          
          {/* Apple Pay / Secondary */}
          <button 
            onClick={() => setCheckoutOpen(true)}
            className="w-full bg-[#1d1d1f] hover:bg-black text-white font-semibold py-3.5 rounded-2xl text-[17px] shadow-sm apple-btn flex items-center justify-center">
            <Apple className="w-5 h-5 mr-1.5 mb-0.5" fill="currentColor" /> Pay
          </button>
        </div>
      </div>
    </div>
  );
}