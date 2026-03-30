"use client"

import { usePosStore } from '@/store/posStore';
import { X, RefreshCw, LogOut, Terminal, User, Store, Database, ExternalLink } from 'lucide-react';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Cookies from 'js-cookie';

export function SettingsModal() {
  const { isSettingsOpen, setSettingsOpen, initData, clearCart } = usePosStore();
  const [isSyncing, setIsSyncing] = useState(false);
  const [isSsoLoading, setIsSsoLoading] = useState(false);
  const router = useRouter();

  if (!isSettingsOpen) return null;

  const handleSsoBridge = async () => {
    setIsSsoLoading(true);
    try {
      const { apiClient } = await import('@/lib/apiClient');
      const response = await apiClient.get('/auth/sso-url');
      if (response.data?.success && response.data?.data?.sso_url) {
        // Redirection completely leaves the Next.js realm and creates Laravel session
        window.location.href = response.data.data.sso_url;
      } else {
        const msg = response.data?.message || 'Unknown error generating SSO link.';
        alert("Dashboard link failed: " + msg);
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || err?.message || 'Network error.';
      alert("SSO Error: " + msg);
    } finally {
      setIsSsoLoading(false);
    }
  };

  const handleForceResync = async () => {
    setIsSyncing(true);
    try {
      // Deleting the universal IndexedDB database to wipe all cached POS data
      if (typeof window !== 'undefined' && window.indexedDB) {
        window.indexedDB.deleteDatabase('tradex_pos_db');
      }
      
      // Reload the page to force Next.js to run initialization and fresh fetch
      window.location.reload();
    } catch (err) {
      console.error("Failed to delete POS database cache", err);
      setIsSyncing(false);
    }
  };

  const handleLogout = () => {
    // 1. Wipe Authentication Cookie
    Cookies.remove('auth_token');
    
    // 2. Wipe Dev Bypass if it exists
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('dev_bypass');
    }
    
    // 3. Wipe Active Cart State
    clearCart();
    setSettingsOpen(false);

    // 4. Wipe offline catalog databases for security
    if (typeof window !== 'undefined' && window.indexedDB) {
        window.indexedDB.deleteDatabase('tradex_pos_db');
    }

    // 5. Redirect entirely back to Login Gateway
    router.push('/login');
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition-all p-4 print:hidden">
      <div className="bg-white/95 backdrop-blur-3xl rounded-[28px] shadow-2xl overflow-hidden w-full max-w-lg border border-white/40 apple-glass animate-in zoom-in-95 duration-200">
        
        <div className="p-6 border-b border-black/5 flex justify-between items-center bg-white/60 sticky top-0 z-20">
          <h2 className="text-[20px] font-bold text-[#1d1d1f] tracking-tight flex items-center gap-2">
            <Terminal className="w-5 h-5 text-[#86868b]" />
            Terminal Settings
          </h2>
          <button 
            onClick={() => setSettingsOpen(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#1d1d1f] transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-8 space-y-8">
          
          {/* Identity Section */}
          <div className="bg-[#f5f5f7]/50 rounded-2xl p-5 border border-black/5">
            <h3 className="text-[13px] font-bold text-[#86868b] uppercase tracking-wider mb-4">Account Information</h3>
            
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <User className="w-5 h-5 text-[#0071e3]" />
                  <span className="text-[15px] font-medium text-[#1d1d1f]">Cashier</span>
                </div>
                <span className="text-[15px] font-semibold text-[#1d1d1f]">{initData?.user?.name || 'Unknown'}</span>
              </div>
              
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Store className="w-5 h-5 text-[#0071e3]" />
                  <span className="text-[15px] font-medium text-[#1d1d1f]">Business</span>
                </div>
                <span className="text-[15px] font-semibold text-[#1d1d1f]">{initData?.business?.name || 'Loading...'}</span>
              </div>
            </div>
          </div>

          {/* Navigation Section */}
          <div className="pt-2">
            <button
              onClick={handleSsoBridge}
              disabled={isSsoLoading}
              className="w-full bg-[#1d1d1f] hover:bg-black text-white font-semibold py-4 rounded-2xl text-[15px] flex items-center justify-center gap-2 transition-colors apple-btn shadow-sm disabled:opacity-50"
            >
              {isSsoLoading ? <RefreshCw className="w-5 h-5 animate-spin" /> : <ExternalLink className="w-5 h-5" />}
              Back to Dashboard
            </button>
          </div>

          {/* Database Section */}
          <div>
            <h3 className="text-[13px] font-bold text-[#86868b] uppercase tracking-wider mb-4 px-1">Offline Database</h3>
            <button
              onClick={handleForceResync}
              disabled={isSyncing}
              className="w-full bg-[#f5f5f7] hover:bg-[#e8e8ed] border border-black/5 rounded-2xl p-4 flex items-center justify-between transition-colors apple-btn disabled:opacity-50"
            >
              <div className="flex items-center gap-3 text-left">
                <div className="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center">
                  <Database className="w-5 h-5 text-[#0071e3]" />
                </div>
                <div>
                  <h4 className="text-[15px] font-semibold text-[#1d1d1f]">Force Resync Catalog</h4>
                  <p className="text-[13px] text-[#86868b] font-medium">Clear local cache and download latest prices</p>
                </div>
              </div>
              <RefreshCw className={`w-5 h-5 text-[#86868b] ${isSyncing ? 'animate-spin' : ''}`} />
            </button>
          </div>

          {/* Danger Zone */}
          <div className="pt-4 border-t border-black/5">
            <button
              onClick={handleLogout}
              className="w-full bg-[#ff3b30]/10 hover:bg-[#ff3b30]/20 text-[#ff3b30] font-semibold py-4 rounded-2xl text-[15px] flex items-center justify-center gap-2 transition-colors apple-btn"
            >
              <LogOut className="w-5 h-5" />
              Secure Logout
            </button>
          </div>

        </div>
      </div>
    </div>
  );
}
