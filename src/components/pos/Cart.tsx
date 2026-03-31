"use client"

import { usePosStore } from '@/store/posStore';
import { Minus, Plus, ShoppingCart, CreditCard, Tag, Receipt, FastForward } from 'lucide-react';
import { CustomerSelect } from './CustomerSelect';
import { useState } from 'react';

export function Cart() {
  const { 
    cart, 
    removeFromCart, 
    updateQuantity, 
    clearCart, 
    cartTotal,
    cartSubtotal,
    cartDiscountValue,
    cartTaxValue,
    setCartDiscount,
    setCartTaxId,
    cartDiscountAmount,
    cartDiscountType,
    cartTaxId,
    setCheckoutOpen, 
    triggerExpressCash,
    initData 
  } = usePosStore();


  const currencyCode = initData?.business?.currency_code || 'USD';
  const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(val);


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
      {/* Cart Items List */}
      <div className="flex-1 overflow-y-auto px-6 py-2">
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
                    {new Intl.NumberFormat('en-US', {
                      style: 'currency',
                      currency: initData?.business?.currency_code || 'USD',
                    }).format(item.final_price * item.quantity)}
                  </span>
                </div>
              </div>

              {/* Minimal Quantity Controls */}
              <div className="flex justify-between items-center mt-2">
                <div className="flex items-center bg-white border border-black/5 rounded-full shadow-sm px-1 py-0.5">
                  <button 
                    onClick={() => updateQuantity(item.cart_id, Math.max(1, item.quantity - 1))}
                    className="w-6 h-6 flex items-center justify-center rounded-full text-[#1d1d1f] hover:bg-[#f5f5f7] apple-btn"
                  >
                    <Minus className="w-3 h-3" />
                  </button>
                  <input 
                    type="number" 
                    value={item.quantity}
                    onChange={(e) => updateQuantity(item.cart_id, Math.max(1, Number(e.target.value)))}
                    className="w-8 text-center text-[13px] font-semibold text-[#1d1d1f] bg-transparent border-none focus:ring-0 p-0"
                    min="1"
                  />
                  <button 
                    onClick={() => updateQuantity(item.cart_id, item.quantity + 1)}
                    className="w-6 h-6 flex items-center justify-center rounded-full text-[#1d1d1f] hover:bg-[#f5f5f7] apple-btn"
                  >
                    <Plus className="w-3 h-3" />
                  </button>
                </div>

                <button 
                  onClick={() => removeFromCart(item.cart_id)}
                  className="text-[11px] text-[#ff3b30] hover:text-[#ff0000] font-medium apple-btn px-2"
                >
                  Remove
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Floating Apple Pay / Checkout Footer fixed to Flex column */}
      <div className="flex-none bg-white/90 backdrop-blur-2xl border-t border-black/5 z-30 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
        
        {/* Actions Bar (Discount / Tax) */}
        <div className="flex border-b border-black/5">
          <div className="flex-1 py-1 px-3 flex flex-col justify-center border-r border-black/5">
            <div className="flex items-center justify-between">
              <label className="text-[11px] font-semibold text-[#86868b] uppercase tracking-wider flex items-center gap-1">
                <Tag className="w-3 h-3" /> Discount
              </label>
              {cartDiscountValue() > 0 && (
                <button 
                  onClick={() => setCartDiscount(null, 0)}
                  className="text-[10px] text-[#ff3b30] hover:text-[#ff0000] font-medium"
                >
                  Clear
                </button>
              )}
            </div>
            <div className="flex items-center gap-1 mt-1">
              <span className="text-[14px] font-medium text-[#86868b]">{initData?.business?.currency_symbol || '$'}</span>
              <input 
                type="number"
                value={cartDiscountAmount || ''}
                onChange={(e) => {
                  const val = parseFloat(e.target.value);
                  if (!isNaN(val) && val >= 0) {
                    setCartDiscount('fixed', val);
                  } else {
                    setCartDiscount(null, 0);
                  }
                }}
                placeholder="0.00"
                className="w-full bg-transparent border-none p-0 text-[15px] font-semibold text-[#1d1d1f] focus:ring-0 placeholder-[#86868b]/30"
              />
            </div>
          </div>
          
          <div className="flex-1 py-1 px-3 flex flex-col justify-center relative group">
            <label className="text-[11px] font-semibold text-[#86868b] uppercase tracking-wider flex items-center gap-1 mb-1">
              <Receipt className="w-3 h-3" /> Tax
            </label>
            <select
              value={cartTaxId || ''}
              onChange={(e) => setCartTaxId(e.target.value ? parseInt(e.target.value) : null)}
              className="w-full h-full appearance-none bg-transparent cursor-pointer text-[14px] font-medium text-[#1d1d1f] hover:bg-black/5 transition-colors outline-none focus:ring-0 p-0"
            >
              <option value="">No Tax</option>
              {initData?.tax_rates?.map(tax => (
                <option key={tax.id} value={tax.id}>{tax.name} ({tax.amount}%)</option>
              ))}
            </select>
          </div>
        </div>

        {/* Totals & Buttons */}
        <div className="p-4 md:p-6 pb-6 md:pb-8">
          <div className="flex justify-between items-baseline mb-4 px-1">
            <span className="text-[15px] md:text-[17px] text-[#1d1d1f] font-medium">Total</span>
            <span className="text-[24px] md:text-[28px] font-bold text-[#1d1d1f] tracking-tight">
              {formatCurrency(cartTotal())}
            </span>
          </div>
          
          <div className="space-y-2 md:space-y-3">
            {/* Main Checkout Button */}
            <button 
              onClick={() => setCheckoutOpen(true)}
              disabled={cart.length === 0}
              className="w-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold py-3 md:py-3.5 rounded-xl md:rounded-2xl text-[15px] md:text-[17px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
              Review Order
            </button>
            
            <div className="flex gap-2 md:gap-3">
              {/* Apple Pay / Card Secondary */}
              <button 
                onClick={() => setCheckoutOpen(true)}
                disabled={cart.length === 0}
                className="flex-1 bg-[#1d1d1f] hover:bg-black text-white font-semibold py-3 md:py-3.5 rounded-xl md:rounded-2xl text-[14px] md:text-[16px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
                <CreditCard className="w-4 h-4 md:w-5 md:h-5 mr-1.5" /> Pay
              </button>
              
              {/* Express Cash Button */}
              <button 
                onClick={() => triggerExpressCash()}
                disabled={cart.length === 0}
                className="flex-1 bg-[#34c759] hover:bg-[#28a745] text-white font-semibold py-3 md:py-3.5 rounded-xl md:rounded-2xl text-[14px] md:text-[16px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
                <FastForward className="w-4 h-4 md:w-5 md:h-5 mr-1.5" /> Cash
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  );
}
