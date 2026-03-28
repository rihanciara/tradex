import { apiClient } from '@/lib/apiClient';

export interface Product {
  product_id: number;
  variation_id: number;
  product_name: string;
  variation_name: string;
  product_type: string;
  product_sku: string;
  variation_sku: string;
  unit: string;
  allow_decimal: number;
  brand: string | null;
  category: string | null;
  enable_stock: number;
  enable_sr_no: number;
  product_image: string | null;
  sell_price_exc_tax: number;
  sell_price_inc_tax: number;
  tax_id: number | null;
  tax_type: string;
  current_stock: number;
}

interface CatalogResponse {
  success: boolean;
  data: Product[];
  count: number;
  offset: number;
  has_more: boolean;
}

export const fetchCatalog = async (locationId?: number): Promise<CatalogResponse> => {
  const response = await apiClient.get<CatalogResponse>('/pos/catalog', {
    params: {
      location_id: locationId,
    },
  });
  return response.data;
};