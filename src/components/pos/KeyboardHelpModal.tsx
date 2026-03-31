"use client";

import { usePosStore } from '@/store/posStore';
import { X, Keyboard } from 'lucide-react';

const shortcuts = [
  { keys: ['/', 'F3'], action: 'Focus product search' },
  { keys: ['F1'], action: 'Return to search from anywhere' },
  { keys: ['F2'], action: 'Jump to cart panel' },
  { keys: ['↓'], action: 'Move into product grid from search' },
  { keys: ['↑', '↓', '←', '→'], action: 'Navigate product grid' },
  { keys: ['Enter'], action: 'Add highlighted product to cart' },
  { keys: ['F10'], action: 'Open checkout' },
  { keys: ['F12'], action: 'Express cash checkout (exact amount)' },
  { keys: ['Esc'], action: 'Cancel / close modal / back to search' },
  { keys: ['+', '='], action: 'Increase cart item quantity' },
  { keys: ['-'], action: 'Decrease cart item quantity' },
  { keys: ['Del'], action: 'Remove focused cart item' },
  { keys: ['Tab'], action: 'Navigate checkout fields' },
  { keys: ['Enter'], action: 'Submit payment in checkout' },
  { keys: ['?'], action: 'Toggle this shortcut guide' },
];

export function KeyboardHelpModal() {
  const { isKeyboardHelpOpen, setKeyboardHelpOpen } = usePosStore();

  if (!isKeyboardHelpOpen) return null;

  return (
    <div
      className="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 backdrop-blur-md p-4 animate-in fade-in duration-200"
      onClick={() => setKeyboardHelpOpen(false)}
    >
      <div
        className="bg-white/90 backdrop-blur-2xl rounded-[28px] shadow-2xl w-full max-w-lg border border-white/30 animate-in zoom-in-95 duration-200 overflow-hidden"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-black/5">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-[#1d1d1f] rounded-xl flex items-center justify-center">
              <Keyboard className="w-5 h-5 text-white" />
            </div>
            <div>
              <h2 className="text-[17px] font-bold text-[#1d1d1f]">Keyboard Shortcuts</h2>
              <p className="text-[12px] text-[#86868b] font-medium">Press <kbd className="px-1.5 py-0.5 bg-[#f5f5f7] rounded-md font-mono text-[11px] text-[#1d1d1f]">?</kbd> anytime to toggle</p>
            </div>
          </div>
          <button
            onClick={() => setKeyboardHelpOpen(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#1d1d1f] transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Shortcut list */}
        <div className="p-4 max-h-[60vh] overflow-y-auto">
          <div className="space-y-1">
            {shortcuts.map((shortcut, i) => (
              <div
                key={i}
                className="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-[#f5f5f7] transition-colors group"
              >
                <span className="text-[14px] text-[#1d1d1f] font-medium">{shortcut.action}</span>
                <div className="flex items-center gap-1.5 ml-4 shrink-0">
                  {shortcut.keys.map((key, ki) => (
                    <span key={ki} className="flex items-center gap-1">
                      <kbd className="px-2 py-1 bg-[#f5f5f7] group-hover:bg-white border border-black/10 rounded-lg text-[12px] font-mono font-bold text-[#1d1d1f] shadow-sm transition-colors min-w-[28px] text-center">
                        {key}
                      </kbd>
                      {ki < shortcut.keys.length - 1 && (
                        <span className="text-[11px] text-[#86868b] font-medium">or</span>
                      )}
                    </span>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="px-6 py-4 border-t border-black/5 bg-[#f5f5f7]/50">
          <p className="text-[12px] text-[#86868b] text-center font-medium">
            Barcode scanner ready — scan any product to instantly add it to cart
          </p>
        </div>
      </div>
    </div>
  );
}
