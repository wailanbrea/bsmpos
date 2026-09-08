import { api } from '../../lib/api';

export interface Supplier {
    id: string;
    name: string;
    tax_id: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
}

export interface Warehouse {
    id: string;
    name: string;
    code: string;
    is_default: boolean;
    is_active: boolean;
}

export interface StockItem {
    product_id: string;
    product_name: string;
    warehouse_id: string;
    warehouse_name: string;
    quantity: string;
    avg_cost: string;
    last_cost: string;
}

export interface KardexMovement {
    id: string;
    warehouse_name: string;
    batch_number: string | null;
    type: string;
    quantity: string;
    cost: string;
    reference_type: string | null;
    created_at: string;
}

export interface PurchaseItem {
    product_id: string;
    product_name?: string;
    quantity: number;
    cost: number;
    total?: number;
    batch_number?: string;
    expires_at?: string;
}

export interface Purchase {
    id: string;
    purchase_number: string;
    status: string;
    subtotal: string;
    tax_total: string;
    total: string;
    purchase_date: string;
    notes: string | null;
    supplier_id: string;
    supplier_name: string;
    warehouse_id: string;
    warehouse_name: string;
    items: PurchaseItem[];
}

// El cliente compartido `api` inyecta Authorization y contexto tenant.
export const InventoryService = {
    // Proveedores
    async getSuppliers(): Promise<Supplier[]> {
        const response = await api.get('/suppliers');
        return response.data.data;
    },

    async createSupplier(data: Partial<Supplier>): Promise<Supplier> {
        const response = await api.post('/suppliers', data);
        return response.data.data;
    },

    // Almacenes
    async getWarehouses(): Promise<Warehouse[]> {
        const response = await api.get('/warehouses');
        return response.data.data;
    },

    async createWarehouse(data: Partial<Warehouse>): Promise<Warehouse> {
        const response = await api.post('/warehouses', data);
        return response.data.data;
    },

    // Stock
    async getStock(warehouseId?: string): Promise<StockItem[]> {
        const response = await api.get('/stock', {
            params: warehouseId ? { warehouse_id: warehouseId } : undefined,
        });
        return response.data.data;
    },

    // Kardex
    async getKardex(productPublicId: string): Promise<KardexMovement[]> {
        const response = await api.get(`/kardex/${productPublicId}`);
        return response.data.data;
    },

    // Compras
    async getPurchases(): Promise<Purchase[]> {
        const response = await api.get('/purchases');
        return response.data.data;
    },

    async createPurchase(data: {
        supplier_id: string;
        warehouse_id: string;
        purchase_number: string;
        purchase_date: string;
        notes?: string;
        items: PurchaseItem[];
    }): Promise<Purchase> {
        const response = await api.post('/purchases', data);
        return response.data.data;
    },

    async confirmPurchase(publicId: string): Promise<Purchase> {
        const response = await api.post(`/purchases/${publicId}/confirm`, {});
        return response.data.data;
    },

    // Ajuste directo / Entrada rápida de existencias
    async adjustStock(data: {
        product_id: string;
        warehouse_id: string;
        quantity: number;
        type: 'adjustment_in' | 'adjustment_out' | 'initial_stock';
        cost?: number;
        notes?: string;
    }): Promise<{ message: string }> {
        const response = await api.post('/stock/adjust', data);
        return response.data.data;
    },

    // Números de Serie y Garantías
    async getSerials(params?: {
        warehouse_id?: string;
        product_id?: string;
        status?: string;
        search?: string;
        page?: number;
    }): Promise<{ data: ProductSerial[]; meta?: { current_page: number; last_page: number; total: number } }> {
        const response = await api.get('/serials', { params });
        return {
            data: response.data.data || [],
            meta: response.data.meta,
        };
    },

    async getAvailableSerials(productId: string, warehouseId?: string): Promise<AvailableSerial[]> {
        const response = await api.get('/serials/available', {
            params: {
                product_id: productId,
                warehouse_id: warehouseId,
            },
        });
        return response.data.data || [];
    },

    async registerSerialsBatch(data: {
        product_id: string;
        warehouse_id: string;
        serials: string[];
        cost?: number;
        notes?: string;
    }): Promise<{ registered_count: number }> {
        const response = await api.post('/serials/batch', data);
        return response.data.data;
    },

    async lookupSerial(query: string): Promise<ProductSerial[]> {
        const response = await api.get('/serials/lookup', {
            params: { query },
        });
        return response.data.data || [];
    },
};

export interface ProductSerial {
    id: string;
    serial_number: string;
    status: 'available' | 'reserved' | 'sold' | 'returned' | 'defective';
    status_label: string;
    product: {
        id: string;
        name: string;
        sku: string;
    } | null;
    warehouse: {
        id: string;
        name: string;
        code: string;
    } | null;
    customer: {
        id: string;
        name: string;
        tax_id: string | null;
    } | null;
    invoice: {
        id: string;
        invoice_number: string;
        ncf: string | null;
    } | null;
    sold_at: string | null;
    sold_at_formatted: string | null;
    warranty_months: number | null;
    warranty_terms: string | null;
    warranty_expires_at: string | null;
    warranty_expires_formatted: string | null;
    warranty_status: 'valid' | 'expired' | 'none';
    is_warranty_active: boolean;
    warranty_days_remaining: number | null;
    notes: string | null;
    created_at: string;
}

export interface AvailableSerial {
    id: string;
    serial_number: string;
    warranty_terms: string | null;
    warranty_months: number | null;
}
