"use client"

import { useQuery } from '@tanstack/react-query';
import { fetchCatalog, fetchTaxonomies } from '@/lib/api';
import { ProductCard } from './ProductCard';
import { Search, Filter, X, Settings, Receipt } from 'lucide-react';
import { useState, useMemo } from 'react';

import { usePosStore } from '@/store/posStore';

export function ProductGrid() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [selectedBrand, setSelectedBrand] = useState<string | null>(null);
  const [showFilters, setShowFilters] = useState(false);
  const [activeLetter, setActiveLetter] = useState<string | null>(null);
  
  const alphabet = ['#', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
  
  const locationId = usePosStore(state => state.initData?.location_id);
  const setSettingsOpen = usePosStore(state => state.setSettingsOpen);
  const setRecentSalesOpen = usePosStore(state => state.setRecentSalesOpen);
  
  // React Query: Fetches catalog ONCE and caches it locally (IndexedDB prepped)
  const { data: catalogData, isLoading: catalogLoading, error: catalogError } = useQuery({
    queryKey: ['catalog', locationId],
    queryFn: () => fetchCatalog(locationId || undefined),
    enabled: !!locationId // Only fetch catalog once the location is known
  });

  // React Query: Fetch Taxonomies
  const { data: taxonomiesData } = useQuery({
    queryKey: ['taxonomies'],
    queryFn: () => fetchTaxonomies(),
  });

  // 0ms Latency Client-Side Search and Filter
  const filteredProducts = useMemo(() => {
    if (!catalogData?.data) return [];
    
    let result = catalogData.data;

    // Apply Taxonomy Filters
    if (selectedCategory) {
      result = result.filter(product => product.category === selectedCategory);
    }
    if (selectedBrand) {
      result = result.filter(product => product.brand === selectedBrand);
    }

    // Apply Alphabet Filter
    if (activeLetter) {
      if (activeLetter === '#') {
        result = result.filter(product => /^[^a-zA-Z]/.test(product.product_name));
      } else {
        result = result.filter(product => product.product_name.toUpperCase().startsWith(activeLetter));
      }
    }

    // Apply Search Term
    if (searchTerm) {
      const lowerSearch = searchTerm.toLowerCase();
      result = result.filter(product => {
        return (
          product.product_name.toLowerCase().includes(lowerSearch) ||
          (product.variation_sku && product.variation_sku.toLowerCase().includes(lowerSearch)) ||
          (product.product_sku && product.product_sku.toLowerCase().includes(lowerSearch))
        );
      });
    }

    return result;
  }, [catalogData, searchTerm, selectedCategory, selectedBrand, activeLetter]);

  if (catalogLoading) return (
    <div className="h-full flex items-center justify-center bg-[#f5f5f7]">
      <div className="flex flex-col items-center">
        <div className="animate-spin rounded-full h-8 w-8 border-2 border-[#1d1d1f] border-t-transparent mb-4"></div>
        <p className="text-[13px] font-medium text-[#86868b] tracking-wide">Downloading Inventory...</p>
      </div>
    </div>
  );

  if (catalogError) return (
    <div className="h-full flex flex-col items-center justify-center bg-[#f5f5f7] text-[#1d1d1f]">
      <div className="w-16 h-16 mb-4 text-[#ff3b30] opacity-80">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <p className="font-semibold text-[17px] mb-1">Cannot Connect to Database</p>
      <p className="text-[13px] text-[#86868b] mb-4 text-center px-6">
        {catalogError instanceof Error ? catalogError.message : 'Please check your network or Vercel API settings.'}
      </p>
      <button 
        onClick={() => window.location.reload()}
        className="px-6 py-2 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-900 transition-colors"
      >
        Retry Connection
      </button>
    </div>
  );

  const categories = taxonomiesData?.data?.categories || [];
  const brands = taxonomiesData?.data?.brands || [];

  return (
    <div className="flex h-full bg-[#f5f5f7]">
      
      {/* Optional Left Sidebar for Filters */}
      {showFilters && (
        <div className="w-64 bg-white/80 backdrop-blur-xl border-r border-black/5 flex flex-col h-full z-20 transition-all duration-300">
          <div className="p-6 flex justify-between items-center border-b border-black/5">
            <h2 className="text-[17px] font-bold text-[#1d1d1f]">Filters</h2>
            <button onClick={() => setShowFilters(false)} className="text-[#86868b] hover:text-[#1d1d1f]">
              <X className="w-5 h-5" />
            </button>
          </div>
          
          <div className="flex-1 overflow-y-auto p-6 space-y-8">
            {/* Categories */}
            <div>
              <h3 className="text-[13px] font-semibold text-[#86868b] uppercase tracking-wider mb-3">Categories</h3>
              <div className="space-y-1">
                <button 
                  onClick={() => setSelectedCategory(null)}
                  className={`w-full text-left px-3 py-2 rounded-lg text-[15px] font-medium transition-colors ${
                    selectedCategory === null ? 'bg-[#0071e3] text-white' : 'text-[#1d1d1f] hover:bg-[#f5f5f7]'
                  }`}
                >
                  All Categories
                </button>
                {categories.map(cat => (
                  <button 
                    key={`cat-${cat.id}`}
                    onClick={() => setSelectedCategory(cat.name)}
                    className={`w-full text-left px-3 py-2 rounded-lg text-[15px] font-medium transition-colors ${
                      selectedCategory === cat.name ? 'bg-[#0071e3] text-white' : 'text-[#1d1d1f] hover:bg-[#f5f5f7]'
                    }`}
                  >
                    {cat.name}
                  </button>
                ))}
              </div>
            </div>

            {/* Brands */}
            <div>
              <h3 className="text-[13px] font-semibold text-[#86868b] uppercase tracking-wider mb-3">Brands</h3>
              <div className="space-y-1">
                <button 
                  onClick={() => setSelectedBrand(null)}
                  className={`w-full text-left px-3 py-2 rounded-lg text-[15px] font-medium transition-colors ${
                    selectedBrand === null ? 'bg-[#0071e3] text-white' : 'text-[#1d1d1f] hover:bg-[#f5f5f7]'
                  }`}
                >
                  All Brands
                </button>
                {brands.map(brand => (
                  <button 
                    key={`brand-${brand.id}`}
                    onClick={() => setSelectedBrand(brand.name)}
                    className={`w-full text-left px-3 py-2 rounded-lg text-[15px] font-medium transition-colors ${
                      selectedBrand === brand.name ? 'bg-[#0071e3] text-white' : 'text-[#1d1d1f] hover:bg-[#f5f5f7]'
                    }`}
                  >
                    {brand.name}
                  </button>
                ))}
              </div>
            </div>
          </div>
          
          {(selectedCategory || selectedBrand) && (
            <div className="p-4 border-t border-black/5">
              <button 
                onClick={() => { setSelectedCategory(null); setSelectedBrand(null); }}
                className="w-full py-2.5 bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#1d1d1f] font-semibold rounded-xl transition-colors text-[13px]"
              >
                Clear Filters
              </button>
            </div>
          )}
        </div>
      )}

      {/* Main Content */}
      <div className="flex-1 flex flex-col h-full min-w-0">
        {/* Apple-style Mac Search Header */}
        <div className="pt-8 pb-4 px-8 bg-[#f5f5f7]/80 backdrop-blur-xl z-10 sticky top-0 border-b border-black/5">
          <div className="flex items-center gap-4">
            {!showFilters && (
              <button 
                onClick={() => setShowFilters(true)}
                className="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-black/5 hover:border-black/10 transition-all text-[#1d1d1f]"
              >
                <Filter className="w-5 h-5" />
              </button>
            )}
            <div className="relative flex-1 max-w-2xl">
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
            
            <div className="flex items-center gap-2 ml-4">
              <button 
                onClick={() => setRecentSalesOpen(true)}
                className="flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-black/5 hover:border-black/10 transition-all shadow-sm apple-btn text-[#1d1d1f]"
                title="Recent Sales Dashboard"
              >
                <Receipt className="w-5 h-5 text-[#0071e3]" />
              </button>
              <button 
                onClick={() => setSettingsOpen(true)}
                className="flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-black/5 hover:border-black/10 transition-all shadow-sm apple-btn text-[#1d1d1f]"
                title="Terminal Settings"
              >
                <Settings className="w-5 h-5 text-[#86868b]" />
              </button>
            </div>
          </div>
          
          <div className="mt-4 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <h1 className="text-[28px] font-bold tracking-tight text-[#1d1d1f]">
                Products
              </h1>
              {(selectedCategory || selectedBrand) && (
                <div className="flex gap-2">
                  {selectedCategory && (
                    <span className="px-2.5 py-1 bg-[#0071e3]/10 text-[#0071e3] rounded-full text-[13px] font-semibold flex items-center gap-1">
                      {selectedCategory}
                      <button onClick={() => setSelectedCategory(null)} className="hover:text-[#0077ed]"><X className="w-3 h-3" /></button>
                    </span>
                  )}
                  {selectedBrand && (
                    <span className="px-2.5 py-1 bg-[#0071e3]/10 text-[#0071e3] rounded-full text-[13px] font-semibold flex items-center gap-1">
                      {selectedBrand}
                      <button onClick={() => setSelectedBrand(null)} className="hover:text-[#0077ed]"><X className="w-3 h-3" /></button>
                    </span>
                  )}
                </div>
              )}
            </div>
            <p className="text-[13px] font-medium text-[#86868b]">
              {filteredProducts.length} items
            </p>
          </div>

          {/* A-Z Alphabetical Scrubber */}
          <div className="mt-4 pt-4 border-t border-black/5 flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none snap-x">
            <button
              onClick={() => setActiveLetter(null)}
              className={`flex-shrink-0 snap-start px-4 py-1.5 rounded-full text-[12px] font-bold transition-colors ${
                activeLetter === null ? 'bg-[#1d1d1f] text-white' : 'bg-transparent text-[#86868b] hover:bg-[#e8e8ed] hover:text-[#1d1d1f]'
              }`}
            >
              All
            </button>
            {alphabet.map((letter) => (
              <button
                key={letter}
                onClick={() => setActiveLetter(letter)}
                className={`flex-shrink-0 snap-start w-8 h-8 flex items-center justify-center rounded-full text-[13px] font-bold transition-all ${
                  activeLetter === letter ? 'bg-[#0071e3] text-white shadow-md scale-110' : 'bg-transparent text-[#86868b] hover:bg-[#e8e8ed] hover:text-[#1d1d1f]'
                }`}
              >
                {letter}
              </button>
            ))}
          </div>
        </div>

        {/* Product Grid */}
        <div className="flex-1 overflow-y-auto px-8 py-6 pb-24">
          {filteredProducts.length === 0 ? (
            <div className="text-center py-24">
              <Search className="w-12 h-12 text-[#86868b] mx-auto mb-4 opacity-30" />
              <p className="text-[#1d1d1f] text-[17px] font-semibold">No results found</p>
              <p className="text-[#86868b] text-[15px] mt-1">Try adjusting your filters or search term.</p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5">
                {filteredProducts.slice(0, 100).map((product) => (
                  <ProductCard key={product.variation_id} product={product} />
                ))}
              </div>
              
              {filteredProducts.length > 100 && (
                <div className="mt-10 mb-6 flex flex-col items-center justify-center p-6 bg-[#f5f5f7]/50 rounded-2xl border border-black/5">
                  <p className="text-[#1d1d1f] text-[15px] font-semibold">
                    Displaying 100 of {new Intl.NumberFormat('en-US').format(filteredProducts.length)} items.
                  </p>
                  <p className="text-[#86868b] text-[13px] font-medium mt-1">
                    Please use the search bar above or scan a barcode to find specific products.
                  </p>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}