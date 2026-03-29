import { apiClient } from '@/lib/apiClient';

export interface PosInitData {
  business: {
    name: string;
    currency_code: string;
    currency_symbol: string;
    thousand_separator: string;
    decimal_separator: string;
  };
  pos_settings: any;
  location_id: number | null;
  tax_rates: { id: number; name: string; amount: number }[];
  payment_methods: { id: string; label: string }[];
  register: { is_open: boolean; register_id: number | null };
  user: { id: number; name: string };
}

export interface InitResponse {
  success: boolean;
  data: PosInitData;
}

export const fetchInit = async (locationId?: number): Promise<InitResponse> => {
  const response = await apiClient.get<InitResponse>('/pos/init', {
    params: { location_id: locationId },
  });
  return response.data;
};

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

export interface Taxonomy {
  id: number;
  name: string;
}

export interface Category extends Taxonomy {
  parent_id: number | null;
}

export interface TaxonomiesResponse {
  success: boolean;
  data: {
    categories: Category[];
    brands: Taxonomy[];
  };
}

export const fetchTaxonomies = async (): Promise<TaxonomiesResponse> => {
  const response = await apiClient.get<TaxonomiesResponse>('/pos/taxonomies');
  return response.data;
};

export interface Customer {
  id: number;
  name: string;
  mobile: string | null;
  contact_id: string;
  email: string | null;
  balance: number;
}

export interface CustomerResponse {
  success: boolean;
  data: Customer[];
}

export const fetchCustomers = async (search?: string): Promise<CustomerResponse> => {
  const response = await apiClient.get<CustomerResponse>('/pos/customers', {
    params: { search },
  });
  return response.data;
};

export interface CheckoutPayload {
  customer_id: number;
  location_id?: number;
  items: {
    product_id: number;
    variation_id: number;
    quantity: number;
    unit_price: number;
  }[];
  payment: {
    method: 'cash' | 'card' | 'custom';
    amount: number;
  }[];
  final_total: number;
}

export interface CheckoutResponse {
  success: boolean;
  message: string;
  transaction_id?: number;
  invoice_no?: string;
}

export const submitCheckout = async (payload: CheckoutPayload): Promise<CheckoutResponse> => {
  const response = await apiClient.post<CheckoutResponse>('/pos/checkout', payload);
  return response.data;
};