import { create } from 'zustand';
import { Product, PosInitData } from '@/lib/api';

interface CartItem extends Product {
  cart_id: string; // Unique ID for the cart (in case of combo splits)
  quantity: number;
  final_price: number; // Includes item-level taxes
}

interface PosState {
  initData: PosInitData | null;
  setInitData: (data: PosInitData) => void;
  cart: CartItem[];
  
  // Cart Actions
  addToCart: (product: Product) => void;
  removeFromCart: (cartId: string) => void;
  updateQuantity: (cartId: string, qty: number) => void;
  clearCart: () => void;
  
  // Cart Level Discount & Tax
  cartDiscountType: 'fixed' | 'percentage' | null;
  cartDiscountAmount: number;
  setCartDiscount: (type: 'fixed' | 'percentage' | null, amount: number) => void;
  
  cartTaxId: number | null;
  setCartTaxId: (id: number | null) => void;

  // Calculators
  cartSubtotal: () => number; // Sum of items inc. item-level taxes
  cartDiscountValue: () => number; // Calculated currency value of discount
  cartTaxValue: () => number; // Calculated currency value of cart-level tax
  cartTotal: () => number; // Final total after cart discounts and cart taxes
  
  // UI & Customer State
  customerId: number | null;
  setCustomerId: (id: number | null) => void;
  isCheckoutOpen: boolean;
  setCheckoutOpen: (isOpen: boolean) => void;
  isSettingsOpen: boolean;
  setSettingsOpen: (isOpen: boolean) => void;
  
  // Express Trigger
  expressCheckoutTrigger: boolean;
  triggerExpressCash: () => void;
  resetExpressCash: () => void;
}

export const usePosStore = create<PosState>((set, get) => ({
  initData: null,
  setInitData: (data) => set({ initData: data }),
  cart: [],
  
  cartDiscountType: null,
  cartDiscountAmount: 0,
  setCartDiscount: (type, amount) => set({ cartDiscountType: type, cartDiscountAmount: amount }),
  
  cartTaxId: null,
  setCartTaxId: (id) => set({ cartTaxId: id }),

  customerId: null,
  setCustomerId: (id) => set({ customerId: id }),
  isCheckoutOpen: false,
  setCheckoutOpen: (isOpen) => set({ isCheckoutOpen: isOpen }),
  isSettingsOpen: false,
  setSettingsOpen: (isOpen) => set({ isSettingsOpen: isOpen }),

  expressCheckoutTrigger: false,
  triggerExpressCash: () => set({ expressCheckoutTrigger: true, isCheckoutOpen: true }),
  resetExpressCash: () => set({ expressCheckoutTrigger: false }),
  
  addToCart: (product) => {
    set((state) => {
      // Check if product variation already in cart
      const existingItem = state.cart.find((item) => item.variation_id === product.variation_id);
      
      if (existingItem) {
        return {
          cart: state.cart.map((item) =>
            item.variation_id === product.variation_id
              ? { ...item, quantity: item.quantity + 1 }
              : item
          ),
        };
      }

      // Add new item
      return {
        cart: [
          ...state.cart,
          {
            ...product,
            cart_id: Math.random().toString(36).substr(2, 9),
            quantity: 1,
            final_price: product.sell_price_inc_tax,
          },
        ],
      };
    });
  },

  removeFromCart: (cartId) => {
    set((state) => ({
      cart: state.cart.filter((item) => item.cart_id !== cartId),
    }));
  },

  updateQuantity: (cartId, qty) => {
    set((state) => ({
      cart: state.cart.map((item) =>
        item.cart_id === cartId ? { ...item, quantity: qty } : item
      ),
    }));
  },

  clearCart: () => set({ 
    cart: [], 
    cartDiscountType: null, 
    cartDiscountAmount: 0, 
    cartTaxId: null,
    customerId: null 
  }),

  cartSubtotal: () => {
    const { cart } = get();
    return cart.reduce((total, item) => total + item.final_price * item.quantity, 0);
  },

  cartDiscountValue: () => {
    const { cartSubtotal, cartDiscountType, cartDiscountAmount } = get();
    const subtotal = cartSubtotal();
    
    if (!cartDiscountType || cartDiscountAmount <= 0) return 0;
    
    if (cartDiscountType === 'fixed') {
      return cartDiscountAmount;
    }
    
    if (cartDiscountType === 'percentage') {
      return subtotal * (cartDiscountAmount / 100);
    }
    
    return 0;
  },

  cartTaxValue: () => {
    const { cartSubtotal, cartDiscountValue, cartTaxId, initData } = get();
    if (!cartTaxId || !initData?.tax_rates) return 0;
    
    const tax = initData.tax_rates.find(t => t.id === cartTaxId);
    if (!tax) return 0;

    const subtotalAfterDiscount = cartSubtotal() - cartDiscountValue();
    return subtotalAfterDiscount * (tax.amount / 100);
  },

  cartTotal: () => {
    const { cartSubtotal, cartDiscountValue, cartTaxValue } = get();
    const subtotal = cartSubtotal();
    const discount = cartDiscountValue();
    const tax = cartTaxValue();
    
    return Math.max(0, subtotal - discount + tax);
  },
}));