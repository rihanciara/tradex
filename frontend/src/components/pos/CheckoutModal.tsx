"use client"

import { useState } from 'react';
import { usePosStore } from '@/store/posStore';
import { submitCheckout, CheckoutPayload } from '@/lib/api';
import { X, CreditCard, Banknote, CheckCircle, Loader2 } from 'lucide-react';

export function CheckoutModal() {
  const { 
    cart, 
    cartTotal, 
    cartSubtotal,
    cartDiscountValue,
    cartTaxValue,
    cartTaxId,
    cartDiscountType,
    cartDiscountAmount,
    customerId, 
    isCheckoutOpen, 
    setCheckoutOpen, 
    clearCart, 
    initData 
  } = usePosStore();

  const [paymentMethod, setPaymentMethod] = useState<'cash' | 'card'>('cash');
  const [amountTendered, setAmountTendered] = useState<string>('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!isCheckoutOpen) return null;

  const total = cartTotal();
  const subtotal = cartSubtotal();
  const discountVal = cartDiscountValue();
  const taxVal = cartTaxValue();

  const tendered = parseFloat(amountTendered) || 0;
  const change = paymentMethod === 'cash' ? Math.max(0, tendered - total) : 0;
  const isReadyToPay = paymentMethod === 'card' || (paymentMethod === 'cash' && tendered >= total);

  const currencyCode = initData?.business?.currency_code || 'USD';
  const currencySymbol = initData?.business?.currency_symbol || '$';

  const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(val);

  const formattedTotal = formatCurrency(total);
  const formattedSubtotal = formatCurrency(subtotal);
  const formattedDiscount = formatCurrency(discountVal);
  const formattedTax = formatCurrency(taxVal);
  const formattedChange = formatCurrency(change);

  const handleCheckout = async () => {
    if (!isReadyToPay || cart.length === 0) return;

    setIsSubmitting(true);
    setError(null);

    const payload: CheckoutPayload = {
      customer_id: customerId || 1, // 1 is Walk-in typical ID
      location_id: initData?.location_id || undefined,
      items: cart.map(item => ({
        product_id: item.product_id,
        variation_id: item.variation_id,
        quantity: item.quantity,
        unit_price: item.sell_price_exc_tax,
        unit_price_inc_tax: item.sell_price_inc_tax,
        item_tax: item.sell_price_inc_tax - item.sell_price_exc_tax,
        tax_id: item.tax_id,
        enable_stock: item.enable_stock,
      })),
      payment: [
        {
          method: paymentMethod,
          amount: total,
        }
      ],
      total_before_tax: subtotal,
      tax_amount: taxVal,
      tax_rate_id: cartTaxId,
      discount_type: cartDiscountType,
      discount_amount: cartDiscountAmount,
      final_total: total,
    };

    try {
      const response = await submitCheckout(payload);
      if (response.success) {
        setIsSuccess(true);
        setTimeout(() => {
          clearCart();
          setCheckoutOpen(false);
          setIsSuccess(false);
          setAmountTendered('');
          setPaymentMethod('cash');
        }, 3000);
      } else {
        setError(response.message || 'Checkout failed');
      }
    } catch (err: unknown) {
      if (err && typeof err === 'object') {
        const axErr = err as { response?: { data?: { message?: string } }; message?: string };
        setError(axErr.response?.data?.message || axErr.message || 'An error occurred during checkout');
      } else if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('An error occurred during checkout');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isSuccess) {
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition-all">
        <div className="bg-white/90 backdrop-blur-xl rounded-[28px] shadow-2xl p-10 flex flex-col items-center w-full max-w-sm apple-glass animate-in zoom-in-95 duration-200">
          <div className="w-20 h-20 bg-[#34c759]/10 rounded-full flex items-center justify-center mb-6">
            <CheckCircle className="w-10 h-10 text-[#34c759]" />
          </div>
          <h2 className="text-[28px] font-bold text-[#1d1d1f] tracking-tight mb-2">Payment Successful</h2>
          <p className="text-[17px] text-[#86868b] font-medium text-center">Receipt is being printed...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition-all p-4">
      <div className="bg-white/95 backdrop-blur-3xl rounded-[28px] shadow-2xl overflow-hidden w-full max-w-4xl flex border border-white/40 apple-glass animate-in zoom-in-95 duration-200">
        
        {/* Left Side: Order Summary */}
        <div className="w-1/2 border-r border-black/5 flex flex-col bg-[#f5f5f7]/50">
          <div className="p-8 pb-4">
            <h2 className="text-[24px] font-bold text-[#1d1d1f] tracking-tight">Order Summary</h2>
          </div>
          
          <div className="flex-1 overflow-y-auto px-8 pb-4">
            <div className="space-y-4">
              {cart.map(item => (
                <div key={item.cart_id} className="flex justify-between items-start py-2 border-b border-black/5 last:border-0">
                  <div className="pr-4">
                    <span className="font-semibold text-[#1d1d1f] text-[15px] block">{item.product_name}</span>
                    <span className="text-[13px] text-[#86868b] font-medium">{item.quantity} x {new Intl.NumberFormat('en-US', { style: 'currency', currency: currencyCode }).format(item.final_price)}</span>
                  </div>
                  <span className="font-semibold text-[#1d1d1f] text-[15px]">{new Intl.NumberFormat('en-US', { style: 'currency', currency: currencyCode }).format(item.final_price * item.quantity)}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="p-8 pt-4 border-t border-black/5 bg-white/40">
            <div className="flex justify-between items-center mb-2">
              <span className="text-[15px] text-[#86868b] font-medium">Subtotal</span>
              <span className="text-[15px] font-semibold text-[#1d1d1f]">{formattedSubtotal}</span>
            </div>
            {discountVal > 0 && (
              <div className="flex justify-between items-center mb-2">
                <span className="text-[15px] text-[#0071e3] font-medium">Discount</span>
                <span className="text-[15px] font-semibold text-[#0071e3]">-{formattedDiscount}</span>
              </div>
            )}
            {taxVal > 0 && (
              <div className="flex justify-between items-center mb-4">
                <span className="text-[15px] text-[#86868b] font-medium">Cart Tax</span>
                <span className="text-[15px] font-semibold text-[#1d1d1f]">{formattedTax}</span>
              </div>
            )}
            <div className="flex justify-between items-center bg-[#f5f5f7] p-4 rounded-xl mt-4">
              <span className="text-[17px] text-[#1d1d1f] font-semibold">Total</span>
              <span className="text-[24px] font-bold text-[#1d1d1f]">{formattedTotal}</span>
            </div>
          </div>
        </div>

        {/* Right Side: Payment Details */}
        <div className="w-1/2 flex flex-col relative bg-white">
          <button 
            onClick={() => setCheckoutOpen(false)}
            className="absolute top-6 right-6 w-8 h-8 flex items-center justify-center rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#1d1d1f] transition-colors"
          >
            <X className="w-4 h-4" />
          </button>

          <div className="p-8 flex-1 flex flex-col">
            <h2 className="text-[24px] font-bold text-[#1d1d1f] tracking-tight mb-6">Payment</h2>

            {/* Payment Method Selector */}
            <div className="flex gap-4 mb-8">
              <button
                onClick={() => setPaymentMethod('cash')}
                className={`flex-1 py-4 px-4 rounded-2xl flex flex-col items-center justify-center gap-2 border-2 transition-all ${
                  paymentMethod === 'cash' 
                    ? 'border-[#0071e3] bg-[#0071e3]/5 shadow-sm' 
                    : 'border-transparent bg-[#f5f5f7] hover:bg-[#e8e8ed]'
                }`}
              >
                <Banknote className={`w-6 h-6 ${paymentMethod === 'cash' ? 'text-[#0071e3]' : 'text-[#86868b]'}`} />
                <span className={`font-semibold text-[15px] leading-none h-4 inline-flex items-center ${paymentMethod === 'cash' ? 'text-[#0071e3]' : 'text-[#1d1d1f]'}`}>Cash</span>
              </button>
              <button
                onClick={() => setPaymentMethod('card')}
                className={`flex-1 py-4 px-4 rounded-2xl flex flex-col items-center justify-center gap-2 border-2 transition-all ${
                  paymentMethod === 'card' 
                    ? 'border-[#0071e3] bg-[#0071e3]/5 shadow-sm' 
                    : 'border-transparent bg-[#f5f5f7] hover:bg-[#e8e8ed]'
                }`}
              >
                <CreditCard className={`w-6 h-6 ${paymentMethod === 'card' ? 'text-[#0071e3]' : 'text-[#86868b]'}`} />
                <span className={`font-semibold text-[15px] leading-none h-4 inline-flex items-center ${paymentMethod === 'card' ? 'text-[#0071e3]' : 'text-[#1d1d1f]'}`}>Card</span>
              </button>
            </div>

            {/* Cash Tendered Input */}
            {paymentMethod === 'cash' && (
              <div className="mb-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                <label className="block text-[13px] font-medium text-[#86868b] mb-2 px-1">Amount Tendered</label>
                <div className="relative">
                  <span className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] font-bold text-[#1d1d1f]">{currencySymbol}</span>
                  <input 
                    type="number"
                    value={amountTendered}
                    onChange={(e) => setAmountTendered(e.target.value)}
                    placeholder="0.00"
                    className="w-full bg-[#f5f5f7] border-none rounded-xl py-4 pl-10 pr-4 text-[24px] font-bold text-[#1d1d1f] placeholder-[#86868b]/30 focus:ring-2 focus:ring-[#0071e3]/30 transition-all apple-input"
                    min={total}
                    step="0.01"
                    autoFocus
                  />
                </div>
                
                {amountTendered && tendered >= total && (
                  <div className="mt-6 flex justify-between items-center p-5 bg-[#34c759]/10 rounded-xl border border-[#34c759]/20">
                    <span className="text-[17px] font-semibold text-[#1d1d1f]">Change Due</span>
                    <span className="text-[24px] font-bold text-[#34c759]">{formattedChange}</span>
                  </div>
                )}
                
                {amountTendered && tendered < total && (
                  <div className="mt-4 p-3 bg-[#ff3b30]/10 rounded-xl">
                    <p className="text-[#ff3b30] text-[13px] font-medium text-center">Amount tendered must be at least {formattedTotal}</p>
                  </div>
                )}
              </div>
            )}

            {/* Card Instructions Dummy */}
            {paymentMethod === 'card' && (
              <div className="mb-8 flex-1 flex flex-col items-center justify-center p-8 bg-[#f5f5f7]/50 rounded-2xl animate-in fade-in slide-in-from-bottom-2 duration-300">
                <CreditCard className="w-12 h-12 text-[#86868b] mb-4 opacity-50" />
                <p className="text-[17px] font-semibold text-[#1d1d1f] text-center">Ready for Card Payment</p>
                <p className="text-[13px] text-[#86868b] font-medium mt-1 text-center">The total {formattedTotal} will be charged to the card.</p>
              </div>
            )}

            {error && (
              <div className="mb-4 p-3 bg-[#ff3b30]/10 border border-[#ff3b30]/20 rounded-xl">
                <p className="text-[#ff3b30] text-[13px] font-semibold text-center">{error}</p>
              </div>
            )}

            <div className="mt-auto">
              <button 
                onClick={handleCheckout}
                disabled={!isReadyToPay || isSubmitting}
                className={`w-full py-4 rounded-xl text-[17px] font-semibold shadow-sm transition-all apple-btn flex items-center justify-center ${
                  !isReadyToPay 
                    ? 'bg-[#f5f5f7] text-[#86868b] cursor-not-allowed' 
                    : 'bg-[#0071e3] hover:bg-[#0077ed] text-white'
                }`}
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin mr-2" />
                    Processing...
                  </>
                ) : (
                  `Complete Order (${formattedTotal})`
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
