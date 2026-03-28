import { Product } from '@/lib/api';
import { usePosStore } from '@/store/posStore';
import { Plus, Check } from 'lucide-react';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const addToCart = usePosStore((state) => state.addToCart);
  const cartItems = usePosStore((state) => state.cart);
  
  // Check if product is already in cart for visual feedback
  const inCart = cartItems.some(item => item.variation_id === product.variation_id);

  // Format price
  const formattedPrice = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(product.sell_price_inc_tax);

  return (
    <div 
      onClick={() => addToCart(product)}
      className={`
        relative overflow-hidden rounded-xl border p-4 cursor-pointer 
        transition-all duration-200 ease-in-out hover:shadow-lg
        flex flex-col h-full bg-white group
        ${inCart ? 'border-blue-500 ring-1 ring-blue-500/50 bg-blue-50/30' : 'border-gray-200 hover:border-blue-300'}
      `}
    >
      {/* Stock Badge */}
      <div className="absolute top-3 right-3">
        <span className={`
          inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
          ${product.current_stock > 10 ? 'bg-green-100 text-green-700' : 
            product.current_stock > 0 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'}
        `}>
          {product.current_stock > 0 ? `${product.current_stock} in stock` : 'Out of stock'}
        </span>
      </div>

      <div className="mt-4 flex-grow">
        <h3 className="text-sm font-bold text-gray-900 line-clamp-2 leading-tight">
          {product.product_name}
        </h3>
        
        {/* Variation Name (if not 'DUMMY') */}
        {product.variation_name !== 'DUMMY' && product.variation_name !== product.product_name && (
          <p className="text-xs text-gray-500 mt-1 line-clamp-1">
            {product.variation_name}
          </p>
        )}
        
        {/* SKU */}
        <p className="text-xs text-gray-400 mt-1 font-mono">
          {product.variation_sku || product.product_sku}
        </p>
      </div>

      <div className="mt-4 flex items-end justify-between border-t pt-3">
        <div>
          <span className="text-lg font-black text-gray-900 tracking-tight">
            {formattedPrice}
          </span>
          <span className="text-[10px] text-gray-500 ml-1">inc. tax</span>
        </div>
        
        <button 
          className={`
            w-8 h-8 flex items-center justify-center rounded-lg transition-colors
            ${inCart ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-blue-100 group-hover:text-blue-600'}
          `}
        >
          {inCart ? <Check className="w-4 h-4" /> : <Plus className="w-4 h-4" />}
        </button>
      </div>
    </div>
  );
}