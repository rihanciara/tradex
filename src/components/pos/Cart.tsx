"use client"

import { usePosStore } from '@/store/posStore';
import { Trash2, Minus, Plus, ShoppingCart } from 'lucide-react';

export function Cart() {
  const { cart, removeFromCart, updateQuantity, clearCart, cartTotal } = usePosStore();

  const formattedTotal = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cartTotal());

  if (cart.length === 0) {
    return (
      <div className="h-full flex flex-col items-center justify-center bg-white p-6">
        <div className="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
          <ShoppingCart className="w-10 h-10 text-gray-300" />
        </div>
        <p className="text-gray-500 font-medium">Cart is empty</p>
        <p className="text-xs text-gray-400 mt-1">Scan a barcode or click a product</p>
      </div>
    );
  }

  return (
    <div className="flex flex-col h-full bg-white">
      {/* Cart Header */}
      <div className="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <h2 className="font-bold text-gray-800 flex items-center">
          <ShoppingCart className="w-5 h-5 mr-2 text-indigo-600" />
          Current Sale
          <span className="ml-2 bg-indigo-100 text-indigo-700 py-0.5 px-2.5 rounded-full text-xs font-bold">
            {cart.length}
          </span>
        </h2>
        <button 
          onClick={clearCart}
          className="text-xs font-semibold text-red-500 hover:text-red-700 transition-colors flex items-center"
        >
          <Trash2 className="w-3 h-3 mr-1" /> Clear All
        </button>
      </div>

      {/* Cart Items List */}
      <div className="flex-1 overflow-y-auto p-2">
        <div className="space-y-2">
          {cart.map((item) => (
            <div 
              key={item.cart_id} 
              className="group flex flex-col p-3 border border-gray-100 rounded-xl hover:border-indigo-100 hover:bg-indigo-50/30 transition-all"
            >
              <div className="flex justify-between items-start mb-2">
                <div className="pr-2 flex-1">
                  <h4 className="text-sm font-bold text-gray-800 leading-tight line-clamp-2">
                    {item.product_name}
                  </h4>
                  {item.variation_name !== 'DUMMY' && item.variation_name !== item.product_name && (
                    <p className="text-xs text-gray-500 mt-0.5">{item.variation_name}</p>
                  )}
                  <p className="text-[10px] text-gray-400 font-mono mt-0.5">
                    {item.variation_sku || item.product_sku}
                  </p>
                </div>
                
                <div className="text-right whitespace-nowrap">
                  <span className="font-bold text-gray-900 block">
                    ${(item.final_price * item.quantity).toFixed(2)}
                  </span>
                  <span className="text-xs text-gray-400">
                    ${item.final_price.toFixed(2)} / {item.unit}
                  </span>
                </div>
              </div>

              {/* Quantity Controls & Remove */}
              <div className="flex justify-between items-center mt-1 pt-2 border-t border-dashed border-gray-200">
                <div className="flex items-center bg-gray-100 rounded-lg p-0.5">
                  <button 
                    onClick={() => updateQuantity(item.cart_id, Math.max(1, item.quantity - 1))}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-white shadow-sm text-gray-600 hover:text-indigo-600 transition-colors"
                  >
                    <Minus className="w-3 h-3" />
                  </button>
                  <input 
                    type="number" 
                    value={item.quantity}
                    onChange={(e) => updateQuantity(item.cart_id, Math.max(1, Number(e.target.value)))}
                    className="w-10 text-center text-sm font-bold bg-transparent border-none focus:ring-0 p-0"
                    min="1"
                  />
                  <button 
                    onClick={() => updateQuantity(item.cart_id, item.quantity + 1)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-white shadow-sm text-gray-600 hover:text-indigo-600 transition-colors"
                  >
                    <Plus className="w-3 h-3" />
                  </button>
                </div>

                <button 
                  onClick={() => removeFromCart(item.cart_id)}
                  className="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Cart Totals & Checkout Button */}
      <div className="p-4 border-t border-gray-200 bg-white shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)] z-10">
        <div className="flex justify-between items-center mb-4">
          <span className="text-gray-500 font-medium">Total Amount</span>
          <span className="text-3xl font-black text-gray-900 tracking-tight">
            {formattedTotal}
          </span>
        </div>
        
        <button className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl text-lg shadow-lg shadow-indigo-200 transition-all active:scale-[0.98] flex items-center justify-center">
          Checkout <span className="ml-2 opacity-80 font-normal text-sm">(Space)</span>
        </button>
      </div>
    </div>
  );
}