"use client";

import { usePosStore } from '@/store/posStore';
import { X, Receipt, Edit3, ArrowRight, Loader2 } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { fetchRecentSales } from '@/lib/api';
import { useState } from 'react';

export function RecentSalesModal() {
  const { isRecentSalesOpen, setRecentSalesOpen, initData } = usePosStore();
  const [isSsoLoading, setIsSsoLoading] = useState<number | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ['recentSales'],
    queryFn: () => fetchRecentSales(),
    enabled: isRecentSalesOpen,
    refetchInterval: 60000, // refresh every minute if kept open
  });

  if (!isRecentSalesOpen) return null;

  const currencyCode = initData?.business?.currency_code || 'USD';
  const formatCurrency = (val: number | string) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(Number(val));

  const handleEditInDashboard = async (transactionId: number) => {
    setIsSsoLoading(transactionId);
    try {
      const { apiClient } = await import('@/lib/apiClient');
      // Pass the redirect path to the SSO generator if supported, otherwise just go to /sells
      const response = await apiClient.get(`/auth/sso-url?redirect=/sells/${transactionId}/edit`);
      
      if (response.data?.success && response.data?.data?.sso_url) {
        window.location.href = response.data.data.sso_url;
      } else {
        // Fallback if backend doesn't support the redirect param yet
        window.location.href = `/sso/magic-login/${initData?.user?.id}?redirect=/sells/${transactionId}/edit`;
      }
    } catch (err: any) {
      alert("Failed to bridge to dashboard. Ensure backend is updated.");
    } finally {
      setIsSsoLoading(null);
    }
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4 print:hidden">
      <div className="bg-white/95 backdrop-blur-3xl rounded-[28px] shadow-2xl overflow-hidden w-full max-w-3xl flex flex-col border border-white/40 apple-glass animate-in zoom-in-95 duration-200 max-h-[85vh]">
        
        {/* Header */}
        <div className="p-6 border-b border-black/5 flex justify-between items-center bg-white/60 sticky top-0 z-20">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-[#0071e3]/10 rounded-full flex items-center justify-center">
              <Receipt className="w-5 h-5 text-[#0071e3]" />
            </div>
            <div>
              <h2 className="text-[20px] font-bold text-[#1d1d1f] tracking-tight leading-tight">
                Recent Sales
              </h2>
              <p className="text-[13px] text-[#86868b] font-medium">View and edit your latest transactions</p>
            </div>
          </div>
          <button 
            onClick={() => setRecentSalesOpen(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#1d1d1f] transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6 bg-[#fbfbfd]">
          {isLoading ? (
            <div className="flex flex-col items-center justify-center h-48 space-y-4">
              <Loader2 className="w-8 h-8 animate-spin text-[#0071e3]" />
              <p className="text-[15px] font-medium text-[#86868b]">Loading recent sales...</p>
            </div>
          ) : error ? (
            <div className="bg-[#ff3b30]/10 border border-[#ff3b30]/20 rounded-2xl p-6 text-center">
              <p className="text-[#ff3b30] font-semibold text-[15px]">Could not load sales data.</p>
              <p className="text-[#ff3b30]/80 text-[13px] mt-1">Please make sure you have pulled the latest backend updates.</p>
            </div>
          ) : (
            <div className="space-y-6">
              {/* Summary Card */}
              <div className="bg-gradient-to-br from-[#0071e3] to-[#005bb5] rounded-2xl p-6 text-white shadow-lg">
                <p className="text-white/80 font-medium text-[13px] uppercase tracking-wider mb-1">Today's Revenue (Your Register)</p>
                <h3 className="text-[36px] font-bold tracking-tight">
                  {formatCurrency(data?.data?.today_total || 0)}
                </h3>
              </div>

              {/* Transactions List */}
              <div className="bg-white rounded-2xl border border-black/5 overflow-hidden shadow-sm">
                <div className="px-5 py-3 border-b border-black/5 bg-[#f5f5f7]/50">
                  <h4 className="text-[13px] font-bold text-[#86868b] uppercase tracking-wider">Latest Transactions</h4>
                </div>
                
                {data?.data?.sales?.length === 0 ? (
                  <div className="p-8 text-center text-[#86868b] font-medium">
                    No sales recorded today.
                  </div>
                ) : (
                  <div className="divide-y divide-black/5">
                    {data?.data?.sales?.map((sale) => (
                      <div key={sale.id} className="p-4 flex items-center justify-between hover:bg-[#f5f5f7]/50 transition-colors">
                        <div className="flex flex-col">
                          <span className="font-bold text-[#1d1d1f] text-[15px]">{sale.invoice_no}</span>
                          <span className="text-[#86868b] text-[13px] font-medium">{sale.customer_name}</span>
                          <span className="text-[#86868b] text-[11px] mt-0.5">{new Date(sale.transaction_date).toLocaleString()}</span>
                        </div>
                        
                        <div className="flex items-center gap-6">
                          <div className="text-right">
                            <span className="font-bold text-[#1d1d1f] text-[17px] block">
                              {formatCurrency(sale.final_total)}
                            </span>
                            <span className={`text-[11px] font-bold uppercase px-2 py-0.5 rounded-full mt-1 inline-block ${
                              sale.payment_status === 'paid' ? 'bg-[#34c759]/10 text-[#34c759]' :
                              sale.payment_status === 'partial' ? 'bg-[#ff9500]/10 text-[#ff9500]' :
                              'bg-[#ff3b30]/10 text-[#ff3b30]'
                            }`}>
                              {sale.payment_status}
                            </span>
                          </div>
                          
                          <button
                            onClick={() => handleEditInDashboard(sale.id)}
                            disabled={isSsoLoading === sale.id}
                            className="w-10 h-10 rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#0071e3] flex items-center justify-center transition-all disabled:opacity-50"
                            title="Edit in Dashboard"
                          >
                            {isSsoLoading === sale.id ? (
                              <Loader2 className="w-4 h-4 animate-spin" />
                            ) : (
                              <Edit3 className="w-4 h-4" />
                            )}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
