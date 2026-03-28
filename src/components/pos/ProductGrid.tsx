"use client"

import { useQuery } from '@tanstack/react-query';
import { fetchCatalog } from '@/lib/api';
import { ProductCard } from './ProductCard';
import { Search } from 'lucide-react';
import { useState, useMemo } from 'react';

export function ProductGrid() {
  const [searchTerm, setSearchTerm] = useState('');
  
  // React Query: Fetches catalog ONCE and caches it locally (IndexedDB prepped)
  const { data, isLoading, error } = useQuery({
    queryKey: ['catalog'],
    queryFn: () => fetchCatalog(),
  });

  // 0ms Latency Client-Side Search
  const filteredProducts = useMemo(() => {
    if (!data?.data) return [];
    if (!searchTerm) return data.data;

    const lowerSearch = searchTerm.toLowerCase();
    
    return data.data.filter(product => {
      return (
        product.product_name.toLowerCase().includes(lowerSearch) ||
        (product.variation_sku && product.variation_sku.toLowerCase().includes(lowerSearch)) ||
        (product.product_sku && product.product_sku.toLowerCase().includes(lowerSearch))
      );
    });
  }, [data, searchTerm]);

  if (isLoading) return (
    <div className="h-full flex items-center justify-center bg-[#f5f5f7]">
      <div className="flex flex-col items-center">
        <div className="animate-spin rounded-full h-8 w-8 border-2 border-[#1d1d1f] border-t-transparent mb-4"></div>
        <p className="text-[13px] font-medium text-[#86868b] tracking-wide">Syncing Catalog...</p>
      </div>
    </div>
  );

  if (error) return (
    <div className="h-full flex flex-col items-center justify-center bg-[#f5f5f7] text-[#1d1d1f]">
      <div className="w-16 h-16 mb-4 text-[#ff3b30] opacity-80">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <p className="font-semibold text-[17px] mb-1">Cannot Connect to Database</p>
      <p className="text-[13px] text-[#86868b]">Please check your network or Vercel API settings.</p>
    </div>
  );

  return (
    <div className="flex flex-col h-full bg-[#f5f5f7]">
      
      {/* Apple-style Mac Search Header */}
      <div className="pt-8 pb-4 px-8 bg-[#f5f5f7]/80 backdrop-blur-xl z-10 sticky top-0 border-b border-black/5">
        <div className="relative max-w-2xl">
          <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <Search className="h-4 w-4 text-[#86868b]" />
          </div>
          <input
            type="text"
            className="block w-full pl-10 pr-4 py-2.5 rounded-xl border-none bg-white text-[15px] font-medium text-[#1d1d1f] placeholder-[#86868b] apple-input shadow-sm"
            placeholder="Search by name, SKU, or scan barcode..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            autoFocus
          />
        </div>
        
        <div className="mt-4 flex items-center justify-between">
          <h1 className="text-[28px] font-bold tracking-tight text-[#1d1d1f]">
            Products
          </h1>
          <p className="text-[13px] font-medium text-[#86868b]">
            {filteredProducts.length} items
          </p>
        </div>
      </div>

      {/* Product Grid */}
      <div className="flex-1 overflow-y-auto px-8 py-6 pb-24">
        {filteredProducts.length === 0 ? (
          <div className="text-center py-24">
            <Search className="w-12 h-12 text-[#86868b] mx-auto mb-4 opacity-30" />
            <p className="text-[#1d1d1f] text-[17px] font-semibold">No results for &quot;{searchTerm}&quot;</p>
            <p className="text-[#86868b] text-[15px] mt-1">Check the spelling or try a new search.</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-5">
            {filteredProducts.map((product) => (
              <ProductCard 
                key={`${product.product_id}-${product.variation_id}`} 
                product={product} 
              />
            ))}
          </div>
        )}
      </div>

    </div>
  );
}