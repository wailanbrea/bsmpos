import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { ServiceOption, VehicleOption, WorkOrder, WorkOrderForm, WorkOrderStatus } from './types';

interface WorkOrderFilters {
    status?: string;
    vehicleId?: string;
}

interface VehicleApi {
    id: string;
    brand: string;
    model: string | null;
    plate: string | null;
}

export async function fetchWorkOrders(filters: WorkOrderFilters = {}): Promise<WorkOrder[]> {
    const params: Record<string, string> = {};
    if (filters.status) params.status = filters.status;
    if (filters.vehicleId) params.vehicle_id = filters.vehicleId;

    const response = await api.get<ApiEnvelope<WorkOrder[]>>('/work-orders', {
        params: Object.keys(params).length ? params : undefined,
    });
    return response.data.data;
}

export async function createWorkOrder(form: WorkOrderForm): Promise<WorkOrder> {
    const response = await api.post<ApiEnvelope<WorkOrder>>('/work-orders', {
        vehicle_id: form.vehicle_id,
        diagnosis: form.diagnosis || null,
        labor_amount: Number(form.labor_amount) || 0,
        services: form.service_ids.map((service_id) => ({ service_id })),
        parts: form.parts
            .filter((part) => part.name.trim() !== '')
            .map((part) => ({
                name: part.name,
                quantity: Number(part.quantity) || 0,
                price: Number(part.price) || 0,
            })),
    });
    return response.data.data;
}

export async function updateWorkOrderStatus(id: string, status: WorkOrderStatus): Promise<WorkOrder> {
    const response = await api.patch<ApiEnvelope<WorkOrder>>(`/work-orders/${id}/status`, { status });
    return response.data.data;
}

export async function fetchVehicleOptions(): Promise<VehicleOption[]> {
    const response = await api.get<ApiEnvelope<VehicleApi[]>>('/vehicles');
    return response.data.data.map((vehicle) => ({
        id: vehicle.id,
        label: `${vehicle.brand} ${vehicle.model ?? ''} · ${vehicle.plate ?? 's/placa'}`.trim(),
    }));
}

export async function fetchServiceOptions(): Promise<ServiceOption[]> {
    const response = await api.get<ApiEnvelope<ServiceOption[]>>('/services');
    return response.data.data;
}
