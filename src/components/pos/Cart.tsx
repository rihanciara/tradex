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

  const [showDiscountModal, setShowDiscountModal] = useState(false);
  const [tempDiscountType, setTempDiscountType] = useState<'fixed' | 'percentage'>('fixed');
  const [tempDiscountAmount, setTempDiscountAmount] = useState<string>('');

  const currencyCode = initData?.business?.currency_code || 'USD';
  const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(val);

  const applyDiscount = () => {
    const val = parseFloat(tempDiscountAmount);
    if (!isNaN(val) && val > 0) {
      setCartDiscount(tempDiscountType, val);
    } else {
      setCartDiscount(null, 0);
    }
    setShowDiscountModal(false);
  };

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

      {/* Floating Apple Pay / Checkout Footer fixed to Flex column */}
      <div className="flex-none bg-white/90 backdrop-blur-2xl border-t border-black/5 z-30 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
        
        {/* Actions Bar (Discount / Tax) */}
        <div className="flex border-b border-black/5">
          <button 
            onClick={() => {
              setTempDiscountType(cartDiscountType || 'percentage');
              setTempDiscountAmount(cartDiscountAmount ? cartDiscountAmount.toString() : '');
              setShowDiscountModal(true);
            }}
            className="flex-1 py-3 flex items-center justify-center gap-2 text-[14px] font-medium text-[#0071e3] hover:bg-[#0071e3]/5 transition-colors"
          >
            <Tag className="w-4 h-4" />
            {cartDiscountValue() > 0 ? `Discount (-${formatCurrency(cartDiscountValue())})` : 'Add Discount'}
          </button>
          
          <div className="w-px bg-black/5"></div>
          
          <div className="flex-1 relative group">
            <select
              value={cartTaxId || ''}
              onChange={(e) => setCartTaxId(e.target.value ? parseInt(e.target.value) : null)}
              className="w-full h-full appearance-none bg-transparent cursor-pointer text-center text-[14px] font-medium text-[#1d1d1f] hover:bg-black/5 transition-colors outline-none focus:ring-0"
              style={{ textAlignLast: 'center' }}
            >
              <option value="">No Cart Tax</option>
              {initData?.tax_rates?.map(tax => (
                <option key={tax.id} value={tax.id}>{tax.name} ({tax.amount}%)</option>
              ))}
            </select>
            <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none">
              <Receipt className="w-4 h-4 text-[#86868b]" />
            </div>
          </div>
        </div>

        {/* Totals & Buttons */}
        <div className="p-6 pb-8">
          <div className="flex justify-between items-baseline mb-5 px-1">
            <span className="text-[17px] text-[#1d1d1f] font-medium">Total</span>
            <span className="text-[28px] font-bold text-[#1d1d1f] tracking-tight">
              {formatCurrency(cartTotal())}
            </span>
          </div>
          
          <div className="space-y-3">
            {/* Main Checkout Button */}
            <button 
              onClick={() => setCheckoutOpen(true)}
              disabled={cart.length === 0}
              className="w-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold py-3.5 rounded-2xl text-[17px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
              Review Order
            </button>
            
            <div className="flex gap-3">
              {/* Apple Pay / Card Secondary */}
              <button 
                onClick={() => setCheckoutOpen(true)}
                disabled={cart.length === 0}
                className="flex-1 bg-[#1d1d1f] hover:bg-black text-white font-semibold py-3.5 rounded-2xl text-[16px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
                <CreditCard className="w-5 h-5 mr-1.5" /> Pay
              </button>
              
              {/* Express Cash Button */}
              <button 
                onClick={() => triggerExpressCash()}
                disabled={cart.length === 0}
                className="flex-1 bg-[#34c759] hover:bg-[#28a745] text-white font-semibold py-3.5 rounded-2xl text-[16px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
                <FastForward className="w-5 h-5 mr-1.5" /> Cash
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Discount Modal Overlay */}
      {showDiscountModal && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 duration-200">
            <div className="px-6 py-4 border-b border-black/5 flex justify-between items-center">
              <h3 className="font-semibold text-[#1d1d1f] text-[17px]">Apply Discount</h3>
            </div>
            
            <div className="p-6">
              <div className="flex bg-[#f5f5f7] rounded-xl p-1 mb-6">
                <button 
                  onClick={() => setTempDiscountType('percentage')}
                  className={`flex-1 py-2 text-[14px] font-medium rounded-lg transition-colors ${tempDiscountType === 'percentage' ? 'bg-white shadow-sm text-[#1d1d1f]' : 'text-[#86868b] hover:text-[#1d1d1f]'}`}
                >
                  Percentage (%)
                </button>
                <button 
                  onClick={() => setTempDiscountType('fixed')}
                  className={`flex-1 py-2 text-[14px] font-medium rounded-lg transition-colors ${tempDiscountType === 'fixed' ? 'bg-white shadow-sm text-[#1d1d1f]' : 'text-[#86868b] hover:text-[#1d1d1f]'}`}
                >
                  Fixed Amount
                </button>
              </div>

              <div className="relative">
                {tempDiscountType === 'fixed' && (
                  <span className="absolute left-4 top-1/2 -translate-y-1/2 text-[17px] font-medium text-[#86868b]">{initData?.business?.currency_symbol || '$'}</span>
                )}
                <input 
                  type="number"
                  value={tempDiscountAmount}
                  onChange={(e) => setTempDiscountAmount(e.target.value)}
                  placeholder="0"
                  className={`w-full bg-[#f5f5f7] border border-transparent focus:border-[#0071e3]/30 focus:bg-white focus:ring-4 focus:ring-[#0071e3]/10 rounded-xl py-3 text-[17px] font-medium text-[#1d1d1f] transition-all outline-none ${tempDiscountType === 'fixed' ? 'pl-8' : 'pl-4'}`}
                  autoFocus
                />
                {tempDiscountType === 'percentage' && (
                  <span className="absolute right-4 top-1/2 -translate-y-1/2 text-[17px] font-medium text-[#86868b]">%</span>
                )}
              </div>
            </div>

            <div className="p-4 bg-[#fbfbfd] border-t border-black/5 flex gap-3">
              <button 
                onClick={() => {
                  setCartDiscount(null, 0);
                  setShowDiscountModal(false);
                }}
                className="flex-1 py-3 bg-white border border-black/10 hover:bg-[#f5f5f7] rounded-xl text-[#ff3b30] font-medium text-[15px] transition-colors"
              >
                Remove
              </button>
              <button 
                onClick={applyDiscount}
                className="flex-1 py-3 bg-[#0071e3] hover:bg-[#0077ed] text-white rounded-xl font-medium text-[15px] transition-colors shadow-sm"
              >
                Apply
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}