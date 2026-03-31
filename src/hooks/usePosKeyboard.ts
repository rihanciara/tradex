"use client";

import { useEffect, useRef, RefObject } from 'react';
import { usePosStore } from '@/store/posStore';

interface UsePosKeyboardOptions {
  searchInputRef: RefObject<HTMLInputElement | null>;
  onGridNavigate: (delta: number) => void;
  onGridEnter: () => void;
  onCartNavigate: (delta: number) => void;
  onCartAdjustQty: (delta: number) => void;
  onCartRemove: () => void;
  gridProductCount: number;
}

export function usePosKeyboard({
  searchInputRef,
  onGridNavigate,
  onGridEnter,
  onCartNavigate,
  onCartAdjustQty,
  onCartRemove,
  gridProductCount,
}: UsePosKeyboardOptions) {
  const {
    focusZone,
    setFocusZone,
    setKeyboardHelpOpen,
    isKeyboardHelpOpen,
    isCheckoutOpen,
    setCheckoutOpen,
    isSettingsOpen,
    isRecentSalesOpen,
    isRegisterModalOpen,
    cart,
    triggerExpressCash,
  } = usePosStore();

  // Track if any modal is open to suppress grid/cart shortcuts
  const anyModalOpen =
    isCheckoutOpen || isSettingsOpen || isRecentSalesOpen || isRegisterModalOpen || isKeyboardHelpOpen;

  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      const tag = (e.target as HTMLElement).tagName;
      const isTyping = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';

      // ── Global shortcuts (always active) ──────────────────────────────────

      // ? — toggle help overlay
      if (e.key === '?' && !isTyping) {
        e.preventDefault();
        setKeyboardHelpOpen(!isKeyboardHelpOpen);
        return;
      }

      // Escape — close help, close modals, return to search
      if (e.key === 'Escape') {
        if (isKeyboardHelpOpen) { setKeyboardHelpOpen(false); return; }
        if (isCheckoutOpen) { setCheckoutOpen(false); return; }
        if (!anyModalOpen && focusZone === 'grid') {
          setFocusZone('search');
          setTimeout(() => searchInputRef.current?.focus(), 0);
          return;
        }
        if (!anyModalOpen && focusZone === 'cart') {
          setFocusZone('search');
          setTimeout(() => searchInputRef.current?.focus(), 0);
          return;
        }
        return;
      }

      // Suppress all other shortcuts when a modal is active
      if (anyModalOpen) return;

      // / or F3 — focus search box
      if ((e.key === '/' || e.key === 'F3') && !isTyping) {
        e.preventDefault();
        setFocusZone('search');
        setTimeout(() => {
          searchInputRef.current?.focus();
          searchInputRef.current?.select();
        }, 0);
        return;
      }

      // F1 — go back to product search
      if (e.key === 'F1') {
        e.preventDefault();
        setFocusZone('search');
        setTimeout(() => searchInputRef.current?.focus(), 0);
        return;
      }

      // F2 — jump to cart
      if (e.key === 'F2') {
        e.preventDefault();
        if (cart.length > 0) setFocusZone('cart');
        return;
      }

      // F10 — open checkout modal
      if (e.key === 'F10') {
        e.preventDefault();
        if (cart.length > 0) setCheckoutOpen(true);
        return;
      }

      // F12 — express cash checkout
      if (e.key === 'F12') {
        e.preventDefault();
        if (cart.length > 0) triggerExpressCash();
        return;
      }

      // ── Search zone shortcuts ─────────────────────────────────────────────

      if (focusZone === 'search') {
        // Arrow Down from search moves into the product grid
        if (e.key === 'ArrowDown' && gridProductCount > 0) {
          e.preventDefault();
          searchInputRef.current?.blur();
          setFocusZone('grid');
          onGridNavigate(0); // Focus index 0
          return;
        }
        // Enter in search with results — focus grid item 0
        if (e.key === 'Enter' && gridProductCount === 1) {
          e.preventDefault();
          onGridEnter(); // Add the single result directly
          return;
        }
        return;
      }

      // ── Grid zone shortcuts ───────────────────────────────────────────────

      if (focusZone === 'grid' && !isTyping) {
        if (e.key === 'ArrowRight') { e.preventDefault(); onGridNavigate(1); return; }
        if (e.key === 'ArrowLeft')  { e.preventDefault(); onGridNavigate(-1); return; }
        if (e.key === 'ArrowDown')  { e.preventDefault(); onGridNavigate(4); return; } // approx cols
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          // If at top row, go back to search
          // The ProductGrid itself will handle going up to search when index goes negative
          onGridNavigate(-4);
          return;
        }
        if (e.key === 'Enter') { e.preventDefault(); onGridEnter(); return; }
        return;
      }

      // ── Cart zone shortcuts ───────────────────────────────────────────────

      if (focusZone === 'cart' && !isTyping) {
        if (e.key === 'ArrowUp')   { e.preventDefault(); onCartNavigate(-1); return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); onCartNavigate(1); return; }
        if (e.key === '+' || e.key === '=') { e.preventDefault(); onCartAdjustQty(1); return; }
        if (e.key === '-')         { e.preventDefault(); onCartAdjustQty(-1); return; }
        if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); onCartRemove(); return; }
        if (e.key === 'Enter' || e.key === 'F10') {
          e.preventDefault();
          if (cart.length > 0) setCheckoutOpen(true);
          return;
        }
        return;
      }
    };

    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [
    focusZone, anyModalOpen, isKeyboardHelpOpen, isCheckoutOpen, cart.length,
    gridProductCount, searchInputRef,
    setFocusZone, setKeyboardHelpOpen, setCheckoutOpen, triggerExpressCash,
    onGridNavigate, onGridEnter, onCartNavigate, onCartAdjustQty, onCartRemove,
  ]);
}
