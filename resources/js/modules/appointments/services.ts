import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { Appointment, AppointmentForm, AppointmentStatus, CustomerOption, ServiceOption } from './types';

interface AgendaFilters {
    date?: string;
    employeeId?: string;
    status?: string;
}

export async function fetchAppointments(filters: AgendaFilters = {}): Promise<Appointment[]> {
    const params: Record<string, string> = {};
    if (filters.date) params.date = filters.date;
    if (filters.employeeId) params.employee_id = filters.employeeId;
    if (filters.status) params.status = filters.status;

    const response = await api.get<ApiEnvelope<Appointment[]>>('/appointments', {
        params: Object.keys(params).length ? params : undefined,
    });
    return response.data.data;
}

export async function createAppointment(form: AppointmentForm): Promise<Appointment> {
    const response = await api.post<ApiEnvelope<Appointment>>('/appointments', {
        customer_id: form.customer_id || null,
        employee_id: form.employee_id || null,
        scheduled_at: form.scheduled_at,
        notes: form.notes || null,
        services: form.service_ids.map((service_id) => ({ service_id })),
    });
    return response.data.data;
}

export async function updateAppointmentStatus(id: string, status: AppointmentStatus): Promise<Appointment> {
    const response = await api.patch<ApiEnvelope<Appointment>>(`/appointments/${id}/status`, { status });
    return response.data.data;
}

/** Servicios disponibles para agendar (para el selector de la cita). */
export async function fetchServiceOptions(): Promise<ServiceOption[]> {
    const response = await api.get<ApiEnvelope<ServiceOption[]>>('/services');
    return response.data.data.filter((service) => service.available_appointments);
}

export async function fetchCustomerOptions(): Promise<CustomerOption[]> {
    const response = await api.get<ApiEnvelope<CustomerOption[]>>('/customers');
    return response.data.data;
}
