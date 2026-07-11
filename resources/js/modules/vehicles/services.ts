import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { CustomerOption, Vehicle, VehicleForm } from './types';

export async function fetchVehicles(search = ''): Promise<Vehicle[]> {
    const response = await api.get<ApiEnvelope<Vehicle[]>>('/vehicles', {
        params: search ? { search } : undefined,
    });
    return response.data.data;
}

function toPayload(form: VehicleForm): Record<string, unknown> {
    return {
        customer_id: form.customer_id,
        brand: form.brand,
        model: form.model || null,
        year: form.year ? Number(form.year) : null,
        plate: form.plate || null,
        vin: form.vin || null,
        color: form.color || null,
        mileage: form.mileage ? Number(form.mileage) : null,
        notes: form.notes || null,
    };
}

export async function createVehicle(form: VehicleForm): Promise<Vehicle> {
    const response = await api.post<ApiEnvelope<Vehicle>>('/vehicles', toPayload(form));
    return response.data.data;
}

export async function updateVehicle(id: string, form: VehicleForm): Promise<Vehicle> {
    const payload = toPayload(form);
    delete payload.customer_id; // el cliente del vehículo no se reasigna en la edición
    const response = await api.patch<ApiEnvelope<Vehicle>>(`/vehicles/${id}`, payload);
    return response.data.data;
}

export async function fetchCustomerOptions(): Promise<CustomerOption[]> {
    const response = await api.get<ApiEnvelope<CustomerOption[]>>('/customers');
    return response.data.data;
}
