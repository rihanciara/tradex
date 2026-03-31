"use client";

import { useQuery } from '@tanstack/react-query';
import { queryClient } from '@/lib/queryClient';
import { QueryClientProvider } from '@tanstack/react-query';
import { fetchListPos, ListPosFilters } from '@/lib/api';
import { useState, useEffect } from 'react';
import Link from 'next/link';
import { ArrowLeft, Search, Printer, Loader2, ChevronLeft, ChevronRight, FileText, CheckCircle2, AlertCircle } from 'lucide-react';
import { usePosStore } from '@/store/posStore';

function ListPosPage() {
  const initData = usePosStore((state) => state.initData);
  const currencyCode = initData?.business?.currency_code || 'USD';

  const [filters, setFilters] = useState<ListPosFilters>({
    limit: 50,
    offset: 0,
    search: '',
    payment_status: 'all',
  });

  const [debouncedSearch, setDebouncedSearch] = useState(filters.search);

  // Debounce search
  useEffect(() => {
    const handler = setTimeout(() => {
      setFilters(prev => ({ ...prev, search: debouncedSearch, offset: 0 }));
    }, 500);
    return () => clearTimeout(handler);
  }, [debouncedSearch]);

  const { data, isLoading, error, isPlaceholderData } = useQuery({
    queryKey: ['listPos', filters],
    queryFn: () => fetchListPos(filters),
    placeholderData: (previousData, previousQuery) => previousData,
    staleTime: 30000,
  });

  const formatCurrency = (val: number | string) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(Number(val));

  const totalPages = data?.total ? Math.ceil(data.total / (filters.limit || 50)) : 1;
  const currentPage = Math.floor((filters.offset || 0) / (filters.limit || 50)) + 1;

  const handleNextPage = () => {
    if (currentPage < totalPages) {
      setFilters(prev => ({ ...prev, offset: (prev.offset || 0) + (prev.limit || 50) }));
    }
  };

  const handlePrevPage = () => {
    if (currentPage > 1) {
      setFilters(prev => ({ ...prev, offset: Math.max(0, (prev.offset || 0) - (prev.limit || 50)) }));
    }
  };

  const handleStatusChange = (status: string) => {
    setFilters(prev => ({ ...prev, payment_status: status, offset: 0 }));
  };

  return (
    <div className="min-h-screen bg-[#f5f5f7] font-sans flex flex-col items-center">
      {/* Top Header */}
      <div className="w-full bg-[#fbfbfd]/90 backdrop-blur-xl border-b border-black/5 sticky top-0 z-40 px-6 py-4 flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link href="/pos" className="w-10 h-10 rounded-full bg-[#0071e3]/10 hover:bg-[#0071e3]/20 flex items-center justify-center transition-colors text-[#0071e3]">
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <div>
            <h1 className="text-[24px] font-bold text-[#1d1d1f] tracking-tight leading-none">Sales History</h1>
            <p className="text-[#86868b] text-[13px] font-medium mt-1">Review all point-of-sale transactions</p>
          </div>
        </div>

        {/* Global Stats */}
        {data && (
          <div className="hidden md:flex items-center gap-6 bg-white px-5 py-2.5 rounded-2xl shadow-sm border border-black/5">
            <div className="text-right">
              <span className="text-[11px] font-bold text-[#86868b] uppercase tracking-wider block">Total Found</span>
              <span className="text-[15px] font-bold text-[#1d1d1f] leading-none">{data.total} Sales</span>
            </div>
            <div className="w-px h-8 bg-black/10"></div>
            <div className="text-right">
              <span className="text-[11px] font-bold text-[#86868b] uppercase tracking-wider block">Current Page Limit</span>
              <span className="text-[15px] font-bold text-[#0071e3] leading-none">{filters.limit}</span>
            </div>
          </div>
        )}
      </div>

      <div className="w-full max-w-7xl px-4 py-8 flex-1 flex flex-col gap-6">
        {/* Filters Bar */}
        <div className="bg-white p-4 rounded-3xl shadow-sm border border-black/5 flex flex-col md:flex-row gap-4 justify-between items-center">
          
          <div className="relative w-full md:w-96">
            <Search className="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-[#86868b]" />
            <input 
              type="text" 
              placeholder="Search Invoice No or Customer..." 
              value={debouncedSearch || ''}
              onChange={(e) => setDebouncedSearch(e.target.value)}
              className="w-full bg-[#f5f5f7] border-transparent focus:bg-white focus:border-[#0071e3] focus:ring-4 focus:ring-[#0071e3]/10 rounded-2xl py-3 pl-11 pr-4 text-[15px] text-[#1d1d1f] font-medium transition-all outline-none placeholder:text-[#86868b]/70"
            />
          </div>

          <div className="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 hide-scrollbar">
            {['all', 'paid', 'due', 'partial'].map(status => (
              <button
                key={status}
                onClick={() => handleStatusChange(status)}
                className={`whitespace-nowrap px-5 py-2.5 rounded-full text-[13px] font-bold capitalize transition-all ${
                  filters.payment_status === status 
                    ? 'bg-[#1d1d1f] text-white shadow-md' 
                    : 'bg-[#f5f5f7] text-[#86868b] hover:bg-[#e8e8ed]'
                }`}
              >
                {status}
              </button>
            ))}
          </div>
        </div>

        {/* Data Grid */}
        <div className="bg-white rounded-[28px] shadow-sm border border-black/5 overflow-hidden flex-1 flex flex-col relative">
          
          {/* Loading Overlay */}
          {isLoading && !data && (
            <div className="absolute inset-0 bg-white/80 backdrop-blur-sm z-20 flex flex-col items-center justify-center">
              <Loader2 className="w-10 h-10 animate-spin text-[#0071e3]" />
              <p className="mt-4 font-semibold text-[#86868b]">Loading Sales Data...</p>
            </div>
          )}

          {error && (
            <div className="absolute inset-0 z-20 flex flex-col items-center justify-center p-8 text-center bg-white/90 backdrop-blur-sm">
              <AlertCircle className="w-12 h-12 text-[#ff3b30] mb-4" />
              <p className="text-[#1d1d1f] font-bold text-[18px]">Failed to load POS sales</p>
              <p className="text-[#86868b] mt-2">Check your connection or backend configuration.</p>
            </div>
          )}

          {/* Table */}
          <div className="flex-1 overflow-x-auto">
            <table className="w-full text-left border-collapse min-w-[800px]">
              <thead>
                <tr className="bg-[#fbfbfd]">
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider border-b border-black/5">Date</th>
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider border-b border-black/5">Invoice No</th>
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider border-b border-black/5">Customer</th>
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider border-b border-black/5">Payment Status</th>
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider text-right border-b border-black/5">Total Amount</th>
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider text-right border-b border-black/5">Total Paid</th>
                  <th className="px-6 py-4 text-[11px] font-bold text-[#86868b] uppercase tracking-wider border-b border-black/5 w-[100px] text-center">Action</th>
                </tr>
              </thead>
              <tbody className={`divide-y divide-black/5 transition-opacity duration-200 ${isPlaceholderData ? 'opacity-50' : 'opacity-100'}`}>
                {data?.data.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-6 py-16 text-center text-[#86868b]">
                      <FileText className="w-12 h-12 mx-auto text-black/10 mb-4" />
                      <p className="font-semibold text-[15px]">No sales found matching your criteria</p>
                    </td>
                  </tr>
                ) : (
                  data?.data.map((sale) => {
                    const statusColor = 
                      sale.payment_status === 'paid' ? 'bg-[#34c759]/10 text-[#34c759]' :
                      sale.payment_status === 'partial' ? 'bg-[#ff9500]/10 text-[#ff9500]' :
                      'bg-[#ff3b30]/10 text-[#ff3b30]';
                    
                    const StatusIcon = sale.payment_status === 'paid' ? CheckCircle2 : AlertCircle;

                    return (
                      <tr key={sale.id} className="hover:bg-[#f5f5f7]/50 transition-colors group">
                        <td className="px-6 py-4">
                          <span className="text-[13px] font-medium text-[#1d1d1f] block">{new Date(sale.transaction_date).toLocaleDateString()}</span>
                          <span className="text-[11px] text-[#86868b]">{new Date(sale.transaction_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        </td>
                        <td className="px-6 py-4">
                          <span className="font-bold text-[#0071e3] text-[15px]">{sale.invoice_no}</span>
                        </td>
                        <td className="px-6 py-4">
                          <span className="font-semibold text-[#1d1d1f] text-[15px]">{sale.customer_name}</span>
                        </td>
                        <td className="px-6 py-4">
                          <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide ${statusColor}`}>
                            <StatusIcon className="w-3 h-3" />
                            {sale.payment_status}
                          </div>
                        </td>
                        <td className="px-6 py-4 text-right">
                          <span className="font-bold text-[#1d1d1f] text-[15px]">
                            {formatCurrency(sale.final_total)}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-right">
                          <span className="font-bold text-[#86868b] text-[15px]">
                            {formatCurrency(sale.total_paid || 0)}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-center">
                          <button 
                            className="w-10 h-10 rounded-full mx-auto bg-transparent group-hover:bg-[#0071e3]/10 text-[#86868b] group-hover:text-[#0071e3] flex items-center justify-center transition-all opacity-50 group-hover:opacity-100"
                            title="Print Local Receipt (Coming Soon)"
                          >
                            <Printer className="w-4 h-4" />
                          </button>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination Footer */}
          <div className="bg-[#fbfbfd] border-t border-black/5 px-6 py-4 flex items-center justify-between">
            <span className="text-[13px] font-medium text-[#86868b]">
              Showing <span className="text-[#1d1d1f] font-bold">{data?.data.length || 0}</span> items per page
            </span>
            
            <div className="flex items-center gap-4">
              <span className="text-[13px] font-bold text-[#1d1d1f]">
                Page {currentPage} of {totalPages}
              </span>
              <div className="flex items-center gap-2">
                <button 
                  onClick={handlePrevPage}
                  disabled={currentPage === 1}
                  className="w-10 h-10 rounded-full bg-white border border-black/5 shadow-sm text-[#1d1d1f] flex items-center justify-center disabled:opacity-30 disabled:hover:bg-white hover:bg-[#f5f5f7] transition-colors"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button 
                  onClick={handleNextPage}
                  disabled={currentPage === totalPages || totalPages === 0}
                  className="w-10 h-10 rounded-full bg-white border border-black/5 shadow-sm text-[#1d1d1f] flex items-center justify-center disabled:opacity-30 disabled:hover:bg-white hover:bg-[#f5f5f7] transition-colors"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Ensure the QueryClient is provided for this isolated page
export default function PosSalesPageWrapper() {
  return (
    <QueryClientProvider client={queryClient}>
      <ListPosPage />
    </QueryClientProvider>
  );
}
