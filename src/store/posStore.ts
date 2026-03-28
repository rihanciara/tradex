import { create } from 'zustand';
import { Product } from '@/lib/api';

interface CartItem extends Product {
  cart_id: string; // Unique ID for the cart (in case of combo splits)
  quantity: number;
  final_price: number;
}

interface PosState {
  cart: CartItem[];
  addToCart: (product: Product) => void;
  removeFromCart: (cartId: string) => void;
  updateQuantity: (cartId: string, qty: number) => void;
  clearCart: () => void;
  cartTotal: () => number;
  customerId: number | null;
  setCustomerId: (id: number | null) => void;
  isCheckoutOpen: boolean;
  setCheckoutOpen: (isOpen: boolean) => void;
}

export const usePosStore = create<PosState>((set, get) => ({
  cart: [],
  customerId: null,
  isCheckoutOpen: false,
  setCustomerId: (id) => set({ customerId: id }),
  setCheckoutOpen: (isOpen) => set({ isCheckoutOpen: isOpen }),
  
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

  clearCart: () => set({ cart: [] }),

  cartTotal: () => {
    const { cart } = get();
    return cart.reduce((total, item) => total + item.final_price * item.quantity, 0);
  },
}));