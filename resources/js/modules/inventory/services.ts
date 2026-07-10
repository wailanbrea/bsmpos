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
};
