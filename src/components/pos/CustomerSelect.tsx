"use client"

import { useState, useRef, useEffect } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchCustomers, createCustomer, Customer } from '@/lib/api';
import { usePosStore } from '@/store/posStore';
import { User, Search, UserPlus, Check, ChevronDown, X, Loader2 } from 'lucide-react';

export function CustomerSelect() {
  const { customerId, setCustomerId } = usePosStore();
  const queryClient = useQueryClient();
  const [isOpen, setIsOpen] = useState(false);
  const [search, setSearch] = useState('');
  const wrapperRef = useRef<HTMLDivElement>(null);

  // New Customer modal state
  const [showNewModal, setShowNewModal] = useState(false);
  const [newName, setNewName] = useState('');
  const [newMobile, setNewMobile] = useState('');
  const [isCreating, setIsCreating] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);

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

  const handleCreateCustomer = async () => {
    if (!newName.trim()) {
      setCreateError('Customer name is required.');
      return;
    }
    setIsCreating(true);
    setCreateError(null);
    try {
      const res = await createCustomer({ name: newName.trim(), mobile: newMobile.trim() });
      if (res.success && res.data) {
        // Invalidate the customer query cache so it refreshes
        queryClient.invalidateQueries({ queryKey: ['customers'] });
        // Auto-select the newly created customer
        setCustomerId(res.data.id);
        // Close both modals
        setShowNewModal(false);
        setIsOpen(false);
        setNewName('');
        setNewMobile('');
      } else {
        setCreateError(res.message || 'Failed to create customer.');
      }
    } catch (err: any) {
      setCreateError(err?.response?.data?.message || 'Network error. Please try again.');
    } finally {
      setIsCreating(false);
    }
  };

  return (
    <>
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
                <span className="text-[13px] text-[#86868b] leading-none h-3 inline-flex items-center">Tap to assign customer</span>
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
            
            <div className="max-h-[220px] overflow-y-auto p-2">
              {isLoading ? (
                <div className="flex justify-center items-center py-6">
                  <div className="animate-spin rounded-full h-5 w-5 border-2 border-[#1d1d1f] border-t-transparent"></div>
                </div>
              ) : (
                <>
                  {allCustomers.map(customer => (
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
                  ))}
                </>
              )}
            </div>

            {/* Always-visible Add New Customer button */}
            <div className="p-2 border-t border-black/5">
              <button
                onClick={() => {
                  setShowNewModal(true);
                  setIsOpen(false);
                }}
                className="w-full flex items-center justify-center gap-2 py-3 px-4 bg-[#0071e3]/10 hover:bg-[#0071e3]/15 text-[#0071e3] font-semibold text-[14px] rounded-xl transition-colors"
              >
                <UserPlus className="w-4 h-4" />
                + New Customer
              </button>
            </div>
          </div>
        )}
      </div>

      {/* New Customer Modal */}
      {showNewModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" onClick={() => setShowNewModal(false)}>
          <div 
            className="bg-white rounded-[24px] shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 duration-200"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="px-6 py-5 border-b border-black/5 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-[#1d1d1f] text-[18px]">New Customer</h3>
                <p className="text-[13px] text-[#86868b] mt-0.5">Add a customer to this sale</p>
              </div>
              <button
                onClick={() => setShowNewModal(false)}
                className="w-8 h-8 flex items-center justify-center rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] transition-colors"
              >
                <X className="w-4 h-4 text-[#1d1d1f]" />
              </button>
            </div>

            {/* Form */}
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-[13px] font-semibold text-[#86868b] mb-2 uppercase tracking-wide">Full Name *</label>
                <input
                  type="text"
                  value={newName}
                  onChange={(e) => setNewName(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleCreateCustomer()}
                  placeholder="e.g. John Smith"
                  autoFocus
                  className="w-full bg-[#f5f5f7] border border-transparent focus:border-[#0071e3]/30 focus:bg-white focus:ring-4 focus:ring-[#0071e3]/10 rounded-xl py-3 px-4 text-[16px] font-medium text-[#1d1d1f] transition-all outline-none placeholder-[#86868b]"
                />
              </div>

              <div>
                <label className="block text-[13px] font-semibold text-[#86868b] mb-2 uppercase tracking-wide">Mobile Number</label>
                <input
                  type="tel"
                  value={newMobile}
                  onChange={(e) => setNewMobile(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleCreateCustomer()}
                  placeholder="e.g. +91 98765 43210"
                  className="w-full bg-[#f5f5f7] border border-transparent focus:border-[#0071e3]/30 focus:bg-white focus:ring-4 focus:ring-[#0071e3]/10 rounded-xl py-3 px-4 text-[16px] font-medium text-[#1d1d1f] transition-all outline-none placeholder-[#86868b]"
                />
              </div>

              {createError && (
                <p className="text-[#ff3b30] text-[13px] font-medium bg-[#ff3b30]/5 px-4 py-2.5 rounded-xl">{createError}</p>
              )}
            </div>

            {/* Actions */}
            <div className="px-6 pb-6 flex gap-3">
              <button
                onClick={() => setShowNewModal(false)}
                className="flex-1 py-3 rounded-xl text-[15px] font-semibold text-[#1d1d1f] bg-[#f5f5f7] hover:bg-[#e8e8ed] transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleCreateCustomer}
                disabled={isCreating || !newName.trim()}
                className="flex-1 py-3 rounded-xl text-[15px] font-semibold text-white bg-[#0071e3] hover:bg-[#0077ed] transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {isCreating ? (
                  <><Loader2 className="w-4 h-4 animate-spin" /> Saving...</>
                ) : (
                  'Save & Select'
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
