export type AppointmentStatus = 'pendiente' | 'confirmada' | 'en_proceso' | 'completada' | 'cancelada' | 'no_asistio';

export interface AppointmentServiceLine {
    service_id: number;
    name: string;
    price: string;
    tax_rate: string;
    duration_minutes: number;
}

export interface Appointment {
    id: string;
    customer_id: string | null;
    customer_name: string | null;
    employee_id: string | null;
    employee_name: string | null;
    scheduled_at: string;
    duration_minutes: number;
    status: AppointmentStatus;
    total: string;
    reminder_at: string | null;
    notes: string | null;
    services: AppointmentServiceLine[];
}

export interface AppointmentForm {
    customer_id: string;
    employee_id: string;
    scheduled_at: string;
    notes: string;
    service_ids: string[];
}

/** Opciones ligeras para los selectores de la agenda. */
export interface ServiceOption {
    id: string;
    name: string;
    price: string;
    available_appointments: boolean;
}

export interface CustomerOption {
    id: string;
    name: string;
}
