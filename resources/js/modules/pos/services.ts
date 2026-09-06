import { api } from '../../lib/api';

export interface PosOrderItem {
    product_id: string;
    product_name?: string;
    name?: string;
    quantity: number;
    price: number;
    discount: number;
    tax_id: string | null;
    tax_rate?: number;
    batch_number?: string;
}

export interface PosPayment {
    payment_method_code: string;
    currency_code?: string;
    exchange_rate?: number;
    amount: number;
    reference?: string;
}

export interface PosOrder {
    id?: string;
    customer_id: string;
    warehouse_id?: string;
    restaurant_table_id?: string;
    order_number: string;
    status: string; // pending | completed
    apply_tip: boolean;
    notes?: string;
    items: PosOrderItem[];
    payments?: PosPayment[];
}

export interface ExternalOrderBridge {
    source: 'appointment' | 'work_order';
    reference_id: string;
    customer_id?: string | null;
    customer_name?: string | null;
    notes?: string;
    items: PosOrderItem[];
}

const BRIDGE_STORAGE_KEY = 'bsmpos_external_order_bridge';

export function setExternalOrderBridge(data: ExternalOrderBridge): void {
    window.sessionStorage.setItem(BRIDGE_STORAGE_KEY, JSON.stringify(data));
}

export function consumeExternalOrderBridge(): ExternalOrderBridge | null {
    const raw = window.sessionStorage.getItem(BRIDGE_STORAGE_KEY);
    if (!raw) return null;
    window.sessionStorage.removeItem(BRIDGE_STORAGE_KEY);
    try {
        return JSON.parse(raw) as ExternalOrderBridge;
    } catch {
        return null;
    }
}

// El cliente compartido `api` inyecta Authorization y el contexto de
// compañía/sucursal (X-Company-Id / X-Branch-Id) en cada petición.
export const PosService = {
    async getOrders(): Promise<unknown[]> {
        const response = await api.get('/orders');
        return response.data.data;
    },

    async createOrder(data: PosOrder, idempotencyKey?: string): Promise<unknown> {
        const response = await api.post('/orders', data, {
            headers: idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined,
        });
        return response.data.data;
    },

    async getCashRegisters(): Promise<unknown[]> {
        const response = await api.get('/cash-registers');
        return response.data.data;
    },

    async getActiveCashSession(): Promise<unknown> {
        const response = await api.get('/cash-sessions/active');
        return response.data.data;
    },

    async openCashSession(cashRegisterId: string, openingAmount: number): Promise<unknown> {
        const response = await api.post('/cash-sessions/open', {
            cash_register_id: cashRegisterId,
            opening_amount: openingAmount,
        });
        return response.data.data;
    },

    async createCashMovement(type: 'in' | 'out', amount: number, concept: string): Promise<unknown> {
        const response = await api.post('/cash-sessions/movements', {
            type,
            amount,
            concept,
        });
        return response.data.data;
    },

    async closeCashSession(countedAmount: number): Promise<unknown> {
        const response = await api.post('/cash-sessions/close', {
            counted_amount: countedAmount,
        });
        return response.data.data;
    },
};
