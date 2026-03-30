import { apiClient } from '@/lib/apiClient';
import { getVal, setVal, STORES, addToQueue } from './db';

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
  try {
    const response = await apiClient.get<InitResponse>('/pos/init', {
      params: { location_id: locationId },
    });
    if (response.data?.success) {
      await setVal('taxonomies', 'init_data', response.data); // reuse taxonomies store for general config
    }
    return response.data;
  } catch (err) {
    const cached = await getVal('taxonomies', 'init_data');
    if (cached) return cached;
    throw err;
  }
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
  try {
    const response = await apiClient.get<TaxonomiesResponse>('/pos/taxonomies');
    if (response.data?.success) {
      await setVal(STORES.TAXONOMIES, 'all', response.data);
    }
    return response.data;
  } catch (err) {
    const cached = await getVal(STORES.TAXONOMIES, 'all');
    if (cached) return cached;
    throw err;
  }
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
  try {
    const response = await apiClient.get<CatalogResponse>('/pos/catalog', {
      params: { location_id: locationId },
    });
    if (response.data?.success) {
      await setVal(STORES.CATALOG, 'all', response.data);
    }
    return response.data;
  } catch (err) {
    const cached = await getVal(STORES.CATALOG, 'all');
    if (cached) return cached;
    throw err;
  }
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
  try {
    const response = await apiClient.get<CustomerResponse>('/pos/customers', {
      params: { search },
    });
    if (response.data?.success && !search) {
      // Only cache the default initial customer list
      await setVal(STORES.CUSTOMERS, 'default', response.data);
    }
    return response.data;
  } catch (err) {
    if (!search) {
      const cached = await getVal(STORES.CUSTOMERS, 'default');
      if (cached) return cached;
    }
    throw err;
  }
};

export interface CheckoutPayload {
  customer_id: number;
  location_id?: number;
  items: {
    product_id: number;
    variation_id: number;
    quantity: number;
    unit_price: number;
    unit_price_inc_tax?: number;
    item_tax?: number;
    tax_id?: number | null;
    line_discount_type?: string | null;
    line_discount_amount?: number;
    enable_stock?: number;
  }[];
  payment: {
    method: 'cash' | 'card' | 'custom';
    amount: number;
  }[];
  total_before_tax?: number;
  tax_rate_id?: number | null;
  tax_amount?: number;
  discount_type?: string | null;
  discount_amount?: number;
  final_total: number;
}

export interface CheckoutResponse {
  success: boolean;
  message: string;
  transaction_id?: number;
  invoice_no?: string;
  is_offline?: boolean;
}

export const submitCheckout = async (payload: CheckoutPayload): Promise<CheckoutResponse> => {
  try {
    // Attempt to send immediately if online
    if (typeof navigator !== 'undefined' && !navigator.onLine) {
      throw new Error('Network offline');
    }
    
    const response = await apiClient.post<CheckoutResponse>('/pos/checkout', payload);
    return response.data;
  } catch (err: any) {
    // Check if network error (not a 4xx/5xx from the server)
    if (err.message === 'Network offline' || !err.response) {
      // Offline: push to sync queue
      await addToQueue(STORES.SYNC_QUEUE, payload);
      
      return {
        success: true,
        message: 'Saved offline. Will sync when connection is restored.',
        is_offline: true,
        invoice_no: `OFFLINE-${Math.floor(Math.random() * 10000)}`
      };
    }
    throw err;
  }
};
