import { Product } from '@/lib/api';
import { usePosStore } from '@/store/posStore';
import { Plus, Check } from 'lucide-react';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const addToCart = usePosStore((state) => state.addToCart);
  const cartItems = usePosStore((state) => state.cart);
  const initData = usePosStore((state) => state.initData);
  
  // Check if product is already in cart for visual feedback
  const inCart = cartItems.some(item => item.variation_id === product.variation_id);

  // Format price
  const formattedPrice = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: initData?.business?.currency_code || 'USD',
  }).format(product.sell_price_inc_tax);

  return (
    <div 
      onClick={() => addToCart(product)}
      className={`
        relative overflow-hidden p-5 cursor-pointer flex flex-col h-full bg-white group
        rounded-[18px] border apple-btn
        ${inCart 
          ? 'border-[#0071e3] ring-1 ring-[#0071e3] bg-[#f5f5f7]' 
          : 'border-black/5 hover:border-black/10 hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] shadow-sm'
        }
      `}
    >
      {/* Sleek Minimal Stock Badge */}
      <div className="absolute top-4 right-4">
        <span className={`
          inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold tracking-wide uppercase
          ${product.current_stock > 10 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 
            product.current_stock > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200/50' : 'bg-rose-50 text-rose-700 border border-rose-200/50'}
        `}>
          {product.current_stock > 0 ? `${product.current_stock} Left` : 'Out'}
        </span>
      </div>

      <div className="mt-2 flex-grow">
        <h3 className="text-[17px] font-semibold text-[#1d1d1f] line-clamp-2 leading-tight tracking-tight pr-12">
          {product.product_name}
        </h3>
        
        {/* Variation Name (if not 'DUMMY') */}
        {product.variation_name !== 'DUMMY' && product.variation_name !== product.product_name && (
          <p className="text-[13px] text-[#86868b] mt-1 line-clamp-1 font-medium">
            {product.variation_name}
          </p>
        )}
        
        {/* SKU */}
        <p className="text-[11px] text-[#86868b] mt-1.5 font-mono opacity-60">
          {product.variation_sku || product.product_sku}
        </p>
      </div>

      <div className="mt-5 flex items-end justify-between border-t border-black/5 pt-4">
        <div className="flex items-baseline">
          <span className="text-[20px] font-bold text-[#1d1d1f] tracking-tight">
            {formattedPrice}
          </span>
        </div>
        
        <button 
          className={`
            w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-300
            ${inCart 
              ? 'bg-[#0071e3] text-white shadow-sm' 
              : 'bg-[#f5f5f7] text-[#1d1d1f] group-hover:bg-[#0071e3] group-hover:text-white group-hover:shadow-md'}
          `}
        >
          {inCart ? <Check className="w-4 h-4 stroke-[2.5]" /> : <Plus className="w-4 h-4 stroke-[2.5]" />}
        </button>
      </div>
    </div>
  );
}