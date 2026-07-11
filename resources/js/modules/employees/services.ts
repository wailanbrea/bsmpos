import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { Employee, EmployeeForm } from './types';

export async function fetchEmployees(): Promise<Employee[]> {
    const response = await api.get<ApiEnvelope<Employee[]>>('/employees');
    return response.data.data;
}

function toPayload(form: EmployeeForm): Record<string, unknown> {
    return {
        name: form.name,
        position: form.position || null,
        phone: form.phone || null,
        email: form.email || null,
        commission_rate: Number(form.commission_rate) || 0,
        is_active: form.is_active,
    };
}

export async function createEmployee(form: EmployeeForm): Promise<Employee> {
    const response = await api.post<ApiEnvelope<Employee>>('/employees', toPayload(form));
    return response.data.data;
}

export async function updateEmployee(id: string, form: EmployeeForm): Promise<Employee> {
    const response = await api.patch<ApiEnvelope<Employee>>(`/employees/${id}`, toPayload(form));
    return response.data.data;
}
