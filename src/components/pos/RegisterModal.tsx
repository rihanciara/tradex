"use client";

import { useState, useEffect } from 'react';
import { usePosStore } from '@/store/posStore';
import { X, Lock, Wallet, Calculator, AlertCircle, CheckCircle2, Loader2, ArrowRight } from 'lucide-react';
import { openRegister, closeRegister, fetchRegisterDetails, RegisterDetailsResponse } from '@/lib/api';

export function RegisterModal() {
  const { initData, setRegisterStatus, isRegisterModalOpen, setRegisterModalOpen } = usePosStore();
  const [mode, setMode] = useState<'open' | 'close'>('open');
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [details, setDetails] = useState<RegisterDetailsResponse['data'] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const isRegisterOpen = initData?.register?.is_open;
  const currencySymbol = initData?.business?.currency_symbol || '$';

  useEffect(() => {
    if (isRegisterModalOpen && isRegisterOpen) {
      setMode('close');
      loadDetails();
    } else {
      setMode('open');
    }
  }, [isRegisterModalOpen, isRegisterOpen]);

  const loadDetails = async () => {
    setIsLoading(true);
    try {
      const res = await fetchRegisterDetails();
      if (res.success) setDetails(res.data);
    } catch (err) {
      setError("Failed to load register details.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleOpen = async () => {
    if (!amount || isNaN(Number(amount))) {
      setError("Please enter a valid amount.");
      return;
    }

    setIsLoading(true);
    setError(null);
    try {
      const res = await openRegister({ initial_amount: Number(amount) });
      if (res.success) {
        setRegisterStatus(true, (res as any).data?.register_id);
        setRegisterModalOpen(false);
        setAmount('');
      } else {
        setError(res.msg);
      }
    } catch (err: any) {
      setError(err.response?.data?.msg || "Failed to open register.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleClose = async () => {
    if (!amount || isNaN(Number(amount))) {
      setError("Please enter the final closing amount.");
      return;
    }

    setIsLoading(true);
    setError(null);
    try {
      const res = await closeRegister({ 
        closing_amount: Number(amount),
        closing_note: note 
      });
      if (res.success) {
        setRegisterStatus(false, null);
        setRegisterModalOpen(false);
        setAmount('');
        setNote('');
      } else {
        setError(res.msg);
      }
    } catch (err: any) {
      setError(err.response?.data?.msg || "Failed to close register.");
    } finally {
      setIsLoading(false);
    }
  };

  if (!isRegisterModalOpen && isRegisterOpen) return null;

  // Force open if register is closed
  const isForced = !isRegisterOpen;
  if (!isForced && !isRegisterModalOpen) return null;

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-md p-4 animate-in fade-in duration-300">
      <div className="bg-white rounded-[32px] shadow-2xl overflow-hidden w-full max-w-md border border-white/20 apple-glass animate-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="p-8 text-center bg-gradient-to-b from-[#fbfbfd] to-white border-b border-black/5">
          <div className="w-16 h-16 bg-[#0071e3]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
            {mode === 'open' ? (
              <Wallet className="w-8 h-8 text-[#0071e3]" />
            ) : (
              <Calculator className="w-8 h-8 text-[#0071e3]" />
            )}
          </div>
          <h2 className="text-[24px] font-bold text-[#1d1d1f] tracking-tight">
            {mode === 'open' ? 'Open Cash Register' : 'Close Cash Register'}
          </h2>
          <p className="text-[15px] text-[#86868b] font-medium mt-1">
            {mode === 'open' 
              ? 'Enter the opening balance in your drawer' 
              : 'Review daily totals and enter closing balance'}
          </p>
        </div>

        <div className="p-8 space-y-6">
          {error && (
            <div className="bg-[#ff3b30]/10 border border-[#ff3b30]/20 rounded-2xl p-4 flex items-center gap-3 animate-in slide-in-from-top-2">
              <AlertCircle className="w-5 h-5 text-[#ff3b30] shrink-0" />
              <p className="text-[#ff3b30] text-[13px] font-bold leading-tight">{error}</p>
            </div>
          )}

          {mode === 'close' && details && (
            <div className="space-y-3 bg-[#f5f5f7] rounded-2xl p-4 border border-black/5">
              <div className="flex justify-between items-center text-[13px] font-medium">
                <span className="text-[#86868b]">Opening Balance</span>
                <span className="text-[#1d1d1f]">{currencySymbol}{details.cash_in_hand}</span>
              </div>
              <div className="flex justify-between items-center text-[13px] font-medium">
                <span className="text-[#86868b]">Total Cash Sales</span>
                <span className="text-[#1d1d1f] text-[#34c759]">+{currencySymbol}{details.total_cash}</span>
              </div>
              <div className="flex justify-between items-center text-[13px] font-medium">
                <span className="text-[#86868b]">Total Card Sales</span>
                <span className="text-[#1d1d1f]">+{currencySymbol}{details.total_card}</span>
              </div>
              <div className="w-full h-px bg-black/5"></div>
              <div className="flex justify-between items-center">
                <span className="text-[14px] font-bold text-[#1d1d1f]">Total Sales</span>
                <span className="text-[18px] font-bold text-[#0071e3]">{currencySymbol}{details.total_sale}</span>
              </div>
            </div>
          )}

          <div className="space-y-4">
            <div>
              <label className="block text-[13px] font-bold text-[#86868b] uppercase tracking-wider mb-2 ml-1">
                {mode === 'open' ? 'Amount in Hand' : 'Closing Amount in Drawer'}
              </label>
              <div className="relative">
                <span className="absolute left-5 top-1/2 -translate-y-1/2 text-[20px] font-bold text-[#1d1d1f]">
                  {currencySymbol}
                </span>
                <input 
                  type="number"
                  placeholder="0.00"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="w-full bg-[#f5f5f7] border-2 border-transparent focus:bg-white focus:border-[#0071e3] focus:ring-4 focus:ring-[#0071e3]/10 rounded-2xl py-4 pl-10 pr-4 text-[24px] font-bold text-[#1d1d1f] tracking-tight transition-all outline-none"
                />
              </div>
            </div>

            {mode === 'close' && (
              <div>
                <label className="block text-[13px] font-bold text-[#86868b] uppercase tracking-wider mb-2 ml-1">
                  Closing Note
                </label>
                <textarea 
                  placeholder="Optional notes for today's session..."
                  value={note}
                  onChange={(e) => setNote(e.target.value)}
                  className="w-full bg-[#f5f5f7] border-2 border-transparent focus:bg-white focus:border-[#0071e3] rounded-2xl py-3 px-4 text-[15px] font-medium text-[#1d1d1f] transition-all outline-none min-h-[80px]"
                />
              </div>
            )}
          </div>

          <button 
            onClick={mode === 'open' ? handleOpen : handleClose}
            disabled={isLoading || !amount}
            className="w-full bg-[#1d1d1f] hover:bg-black text-white rounded-2xl py-4 font-bold text-[17px] shadow-xl shadow-black/10 hover:shadow-2xl hover:shadow-black/20 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:hover:translate-y-0"
          >
            {isLoading ? (
              <Loader2 className="w-5 h-5 animate-spin" />
            ) : mode === 'open' ? (
              <>Open Session <ArrowRight className="w-5 h-5" /></>
            ) : (
              <>Complete End of Day <CheckCircle2 className="w-5 h-5" /></>
            )}
          </button>

          {!isForced && (
            <button 
              onClick={() => setRegisterModalOpen(false)}
              className="w-full py-2 text-[14px] font-bold text-[#86868b] hover:text-[#1d1d1f] transition-colors"
            >
              Cancel
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
