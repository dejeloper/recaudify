import { Product } from '@core/interfaces/product.interface';

export interface Rate {
  id: number;
  name: string;
  product_id: number;
  product: Product | null;
  value: number;
  installments: number;
  installment_value: number;
  discount: number;
  active: boolean;
}

export interface RateInput {
  name: string;
  product_id: number;
  value: number;
  installments: number;
  installment_value: number;
  discount: number;
  active: boolean;
}
