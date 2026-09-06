export interface Category {
    id: number;
    public_id: string;
    kind: string;
    name: string;
    parent_id: number | null;
    is_active: boolean;
}

export interface Product {
    id: string;
    name: string;
    sku: string | null;
    barcode: string | null;
    brand: string | null;
    category_id: number | null;
    category: string | null;
    tax_id: string | null;
    price: string;
    cost: string;
    image_url: string | null;
    track_inventory: boolean;
    warranty_months?: number | null;
    warranty_terms?: string | null;
    requires_serial_number?: boolean;
    is_active: boolean;
    available_pos: boolean;
    variants?: ProductVariant[];
    modifiers?: ProductModifier[];
    combos?: ProductCombo[];
}

export interface ProductVariant {
    id?: string;
    name: string;
    sku: string | null;
    barcode: string | null;
    price: number | null;
    cost: number | null;
    is_active: boolean;
}

export interface ProductModifierOption {
    id?: string;
    name: string;
    price: number;
    cost: number | null;
    is_active: boolean;
}

export interface ProductModifier {
    id?: string;
    name: string;
    required: boolean;
    multiselect: boolean;
    min_options: number;
    max_options: number;
    options: ProductModifierOption[];
}

export interface ProductCombo {
    child_product_id: string;
    child_product_name?: string;
    quantity: number;
    extra_price: number;
}

export interface ProductForm {
    name: string;
    sku: string;
    barcode: string;
    category_id: number | null;
    price: number;
    cost: number;
    track_inventory: boolean;
    available_pos: boolean;
    variants?: ProductVariant[];
    modifiers?: ProductModifier[];
    combos?: ProductCombo[];
}
