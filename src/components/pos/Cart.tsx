"use client"

import { usePosStore } from '@/store/posStore';
import { Minus, Plus, ShoppingCart, CreditCard, Tag, Receipt, FastForward, Pencil, Check, X } from 'lucide-react';
import { CustomerSelect } from './CustomerSelect';
import { useState, useRef, useEffect } from 'react';

// Inline edit field — auto-focuses, commits on blur/Enter, cancels on Escape
function InlineEdit({
  value,
  prefix = '',
  onCommit,
  onCancel,
}: {
  value: string;
  prefix?: string;
  onCommit: (val: string) => void;
  onCancel: () => void;
}) {
  const ref = useRef<HTMLInputElement>(null);
  const [local, setLocal] = useState(value);

  useEffect(() => { ref.current?.focus(); ref.current?.select(); }, []);

  return (
    <div className="flex items-center gap-1">
      {prefix && <span className="text-[13px] text-[#86868b]">{prefix}</span>}
      <input
        ref={ref}
        type="number"
        min="0"
        value={local}
        onChange={(e) => setLocal(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === 'Enter') onCommit(local);
          if (e.key === 'Escape') onCancel();
        }}
        onBlur={() => onCommit(local)}
        className="w-20 text-right bg-white border border-[#0071e3]/30 focus:ring-2 focus:ring-[#0071e3]/10 rounded-lg px-2 py-1 text-[14px] font-semibold text-[#1d1d1f] outline-none"
      />
    </div>
  );
}

export function Cart() {
  const { 
    cart, 
    removeFromCart, 
    updateQuantity,
    updateItemPrice,
    updateItemDiscount,
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

  // Cart-level discount — inline (no modal)
  const [editingCartDiscount, setEditingCartDiscount] = useState(false);

  // Per-item editing: which cart_id is being edited and which field
  const [editingItem, setEditingItem] = useState<{ cartId: string; field: 'price' | 'discount' } | null>(null);

  const currencyCode = initData?.business?.currency_code || 'USD';
  const currencySymbol = initData?.business?.currency_symbol || '$';
  const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(val);

  const commitCartDiscount = (val: string) => {
    const num = parseFloat(val);
    if (!isNaN(num) && num > 0) {
      setCartDiscount('fixed', num);
    } else {
      setCartDiscount(null, 0);
    }
    setEditingCartDiscount(false);
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
      
      {/* Header */}
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
      <div className="flex-1 overflow-y-auto px-6 py-2">
        <div className="space-y-1 pt-2">
          {cart.map((item) => {
            const lineTotal = item.final_price * item.quantity;
            const hasDiscount = (item.item_discount ?? 0) > 0;
            const hasPriceOverride = item.final_price !== item.sell_price_inc_tax;

            return (
              <div 
                key={item.cart_id} 
                className="flex flex-col pt-4 pb-4 border-b border-black/5 last:border-0"
              >
                {/* Row 1: Name + Line Total */}
                <div className="flex justify-between items-start gap-2">
                  <div className="flex-1 min-w-0">
                    <h4 className="text-[16px] font-semibold text-[#1d1d1f] leading-tight truncate">{item.product_name}</h4>
                    {item.variation_name !== 'DUMMY' && item.variation_name !== item.product_name && (
                      <p className="text-[12px] text-[#86868b] font-medium">{item.variation_name}</p>
                    )}
                  </div>
                  <span className="text-[16px] font-bold text-[#1d1d1f] tracking-tight whitespace-nowrap">
                    {formatCurrency(lineTotal)}
                  </span>
                </div>

                {/* Row 2: Unit price + discount indicator */}
                <div className="flex items-center gap-3 mt-1.5">
                  {/* Unit price — tap to edit */}
                  {editingItem?.cartId === item.cart_id && editingItem.field === 'price' ? (
                    <InlineEdit
                      value={item.final_price.toFixed(2)}
                      prefix={currencySymbol}
                      onCommit={(v) => {
                        updateItemPrice(item.cart_id, parseFloat(v) || 0);
                        setEditingItem(null);
                      }}
                      onCancel={() => setEditingItem(null)}
                    />
                  ) : (
                    <button
                      onClick={() => setEditingItem({ cartId: item.cart_id, field: 'price' })}
                      className="flex items-center gap-1 text-[13px] text-[#86868b] hover:text-[#0071e3] transition-colors group"
                      title="Edit unit price"
                    >
                      <span className={hasPriceOverride ? 'text-[#ff9500] font-semibold' : ''}>
                        {formatCurrency(item.final_price)}
                      </span>
                      <Pencil className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                    </button>
                  )}

                  <span className="text-[#d1d1d6]">·</span>

                  {/* Line discount — tap to edit */}
                  {editingItem?.cartId === item.cart_id && editingItem.field === 'discount' ? (
                    <InlineEdit
                      value={(item.item_discount ?? 0).toString()}
                      prefix={`-${currencySymbol}`}
                      onCommit={(v) => {
                        updateItemDiscount(item.cart_id, parseFloat(v) || 0);
                        setEditingItem(null);
                      }}
                      onCancel={() => setEditingItem(null)}
                    />
                  ) : (
                    <button
                      onClick={() => setEditingItem({ cartId: item.cart_id, field: 'discount' })}
                      className="flex items-center gap-1 text-[13px] hover:text-[#0071e3] transition-colors group"
                      title="Add item discount"
                    >
                      <span className={hasDiscount ? 'text-[#34c759] font-semibold' : 'text-[#86868b]'}>
                        {hasDiscount ? `-${formatCurrency(item.item_discount ?? 0)}` : 'disc'}
                      </span>
                      <Tag className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                    </button>
                  )}
                </div>

                {/* Row 3: Qty controls + Remove */}
                <div className="flex justify-between items-center mt-3">
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
                    className="text-[13px] text-[#ff3b30] hover:text-[#ff2d20] font-medium apple-btn px-2"
                  >
                    Remove
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Footer */}
      <div className="flex-none bg-white/90 backdrop-blur-2xl border-t border-black/5 z-30 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
        
        {/* Cart-Level: Inline Discount + Tax */}
        <div className="flex items-center border-b border-black/5">
          {/* Inline Discount — no modal */}
          <div className="flex-1 flex items-center justify-center gap-2 py-3 px-4">
            <Tag className="w-4 h-4 text-[#86868b] flex-shrink-0" />
            {editingCartDiscount ? (
              <InlineEdit
                value={cartDiscountAmount > 0 ? cartDiscountAmount.toString() : ''}
                prefix={currencySymbol}
                onCommit={commitCartDiscount}
                onCancel={() => setEditingCartDiscount(false)}
              />
            ) : (
              <button
                onClick={() => setEditingCartDiscount(true)}
                className="text-[14px] font-medium text-[#0071e3] hover:text-[#0077ed] transition-colors"
              >
                {cartDiscountValue() > 0
                  ? `Discount (−${formatCurrency(cartDiscountValue())})`
                  : 'Cart Discount'}
              </button>
            )}
            {cartDiscountValue() > 0 && !editingCartDiscount && (
              <button
                onClick={() => setCartDiscount(null, 0)}
                className="text-[#86868b] hover:text-[#ff3b30] transition-colors"
                title="Remove discount"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          <div className="w-px bg-black/5 self-stretch" />

          {/* Tax selector */}
          <div className="flex-1 relative">
            <select
              value={cartTaxId || ''}
              onChange={(e) => setCartTaxId(e.target.value ? parseInt(e.target.value) : null)}
              className="w-full h-full appearance-none bg-transparent cursor-pointer text-center text-[14px] font-medium text-[#1d1d1f] hover:bg-black/5 transition-colors outline-none focus:ring-0 py-3 px-8"
              style={{ textAlignLast: 'center' }}
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
          
          <div className="space-y-3">
            <button 
              onClick={() => setCheckoutOpen(true)}
              disabled={cart.length === 0}
              className="w-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold py-3 md:py-3.5 rounded-xl md:rounded-2xl text-[15px] md:text-[17px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
              Review Order
            </button>
            
            <div className="flex gap-3">
              <button 
                onClick={() => setCheckoutOpen(true)}
                disabled={cart.length === 0}
                className="flex-1 bg-[#1d1d1f] hover:bg-black text-white font-semibold py-3 md:py-3.5 rounded-xl md:rounded-2xl text-[14px] md:text-[16px] shadow-sm apple-btn flex items-center justify-center disabled:opacity-50">
                <CreditCard className="w-4 h-4 md:w-5 md:h-5 mr-1.5" /> Pay
              </button>
              
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
