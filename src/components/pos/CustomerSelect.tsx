"use client"

import { useState, useRef, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { fetchCustomers, Customer } from '@/lib/api';
import { usePosStore } from '@/store/posStore';
import { User, Search, UserPlus, Check, ChevronDown } from 'lucide-react';

export function CustomerSelect() {
  const { customerId, setCustomerId } = usePosStore();
  const [isOpen, setIsOpen] = useState(false);
  const [search, setSearch] = useState('');
  const wrapperRef = useRef<HTMLDivElement>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['customers', search],
    queryFn: () => fetchCustomers(search),
    enabled: isOpen,
  });

  const customers = data?.data || [];
  const walkIn: Customer = { id: 1, name: 'Walk-In Customer', mobile: null, contact_id: '1', email: null, balance: 0 };
  const allCustomers = [walkIn, ...customers.filter(c => c.id !== 1)];
  const selectedCustomer = allCustomers.find(c => c.id === (customerId || 1)) || walkIn;

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative w-full" ref={wrapperRef}>
      <button 
        onClick={() => setIsOpen(!isOpen)}
        className="w-full flex items-center justify-between bg-white border border-black/5 p-3.5 rounded-2xl shadow-sm hover:bg-[#fcfcfd] transition-colors apple-btn"
      >
        <div className="flex items-center">
          <div className="w-10 h-10 bg-[#f5f5f7] rounded-full flex items-center justify-center mr-3">
            <User className="w-5 h-5 text-[#86868b]" />
          </div>
          <div className="text-left flex flex-col items-start gap-1">
            <span className="text-[15px] font-semibold text-[#1d1d1f] tracking-tight leading-none h-4 inline-flex items-center">{selectedCustomer.name}</span>
            {selectedCustomer.mobile && (
              <span className="text-[13px] text-[#86868b] leading-none h-3 inline-flex items-center">{selectedCustomer.mobile}</span>
            )}
            {!selectedCustomer.mobile && (
              <span className="text-[13px] text-[#86868b] leading-none h-3 inline-flex items-center">Add phone / details</span>
            )}
          </div>
        </div>
        <ChevronDown className={`w-5 h-5 text-[#1d1d1f] transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {isOpen && (
        <div className="absolute top-[110%] left-0 right-0 bg-white/90 backdrop-blur-xl border border-black/5 rounded-2xl shadow-xl z-50 overflow-hidden transform-gpu animate-in fade-in slide-in-from-top-2 duration-200 apple-glass">
          <div className="p-3 border-b border-black/5 relative">
            <Search className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-[#86868b]" />
            <input 
              type="text" 
              placeholder="Search customers..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-[#f5f5f7] border-none rounded-xl py-2.5 pl-10 pr-4 text-[15px] font-medium text-[#1d1d1f] placeholder-[#86868b] focus:ring-2 focus:ring-[#0071e3]/20 transition-all apple-input"
              autoFocus
            />
          </div>
          
          <div className="max-h-[250px] overflow-y-auto p-2">
            {isLoading ? (
              <div className="flex justify-center items-center py-6">
                <div className="animate-spin rounded-full h-5 w-5 border-2 border-[#1d1d1f] border-t-transparent"></div>
              </div>
            ) : allCustomers.length > 0 ? (
              allCustomers.map(customer => (
                <button
                  key={customer.id}
                  onClick={() => {
                    setCustomerId(customer.id);
                    setIsOpen(false);
                    setSearch('');
                  }}
                  className={`w-full flex items-center justify-between p-3 rounded-xl transition-colors text-left ${
                    (customerId || 1) === customer.id ? 'bg-[#f5f5f7] text-[#1d1d1f]' : 'hover:bg-[#f5f5f7] text-[#1d1d1f]'
                  }`}
                >
                  <div>
                    <span className="block text-[15px] font-medium tracking-tight mb-1 leading-none">{customer.name}</span>
                    {customer.mobile && <span className="block text-[13px] text-[#86868b] font-medium leading-none">{customer.mobile}</span>}
                  </div>
                  {(customerId || 1) === customer.id && <Check className="w-5 h-5 text-[#0071e3]" />}
                </button>
              ))
            ) : (
              <div className="py-6 text-center">
                <p className="text-[15px] font-medium text-[#1d1d1f]">No customers found.</p>
                <div className="mt-3 text-center">
                  <button className="text-[15px] font-medium text-[#0071e3] hover:text-[#0077ed] flex items-center justify-center w-full focus:outline-none">
                    <UserPlus className="w-4 h-4 mr-1.5" /> Create New Customer
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
