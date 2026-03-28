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
  // We use useMemo so it only recalculates when searchTerm or data changes
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
    <div className="h-full flex items-center justify-center">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>
  );

  if (error) return (
    <div className="h-full flex flex-col items-center justify-center text-red-500">
      <p className="font-bold text-lg mb-2">Failed to load catalog</p>
      <p className="text-sm">Please check your network connection.</p>
    </div>
  );

  return (
    <div className="flex flex-col h-full bg-slate-50 border-l border-gray-200">
      
      {/* 0ms Search Bar Area */}
      <div className="p-4 bg-white border-b border-gray-200 shadow-sm z-10 sticky top-0">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Search className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-shadow"
            placeholder="Search by name, SKU, or barcode (0ms latency)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            autoFocus
          />
        </div>
      </div>

      {/* Lightning Fast Product Grid */}
      <div className="flex-1 overflow-y-auto p-4">
        {filteredProducts.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-gray-500 text-lg">No products found matching "{searchTerm}"</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
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