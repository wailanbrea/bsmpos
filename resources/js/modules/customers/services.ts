import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { Customer, CustomerForm } from './types';

export async function fetchCustomers(search: string): Promise<Customer[]> {
    const response = await api.get<ApiEnvelope<Customer[]>>('/customers', {
        params: search ? { search } : undefined,
    });
    return response.data.data;
}

function toPayload(form: CustomerForm): Record<string, unknown> {
    return {
        kind: form.kind,
        name: form.name,
        tax_id_type: form.tax_id_type || null,
        tax_id: form.tax_id || null,
        phone: form.phone || null,
        whatsapp: form.whatsapp || null,
        email: form.email || null,
        address: form.address || null,
        credit_limit: Number(form.credit_limit) || 0,
        credit_days: Number(form.credit_days) || 0,
    };
}

export async function createCustomer(form: CustomerForm): Promise<Customer> {
    const response = await api.post<ApiEnvelope<Customer>>('/customers', toPayload(form));
    return response.data.data;
}

export async function updateCustomer(id: string, form: CustomerForm): Promise<Customer> {
    const response = await api.patch<ApiEnvelope<Customer>>(`/customers/${id}`, toPayload(form));
    return response.data.data;
}

export async function recordCredit(id: string, type: 'charge' | 'payment', amount: number): Promise<Customer> {
    const response = await api.post<ApiEnvelope<Customer>>(`/customers/${id}/credit`, { type, amount });
    return response.data.data;
}
