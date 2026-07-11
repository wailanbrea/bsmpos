export type WorkOrderStatus =
    'recibida' | 'diagnosticando' | 'cotizada' | 'aprobada' | 'en_proceso' | 'lista' | 'entregada' | 'cancelada';

export interface WorkOrderServiceLine {
    name: string;
    price: string;
    tax_rate: string;
}

export interface WorkOrderPartLine {
    name: string;
    quantity: string;
    price: string;
}

export interface WorkOrder {
    id: string;
    vehicle_id: string | null;
    vehicle_label: string | null;
    customer_name: string | null;
    employee_name: string | null;
    diagnosis: string | null;
    status: WorkOrderStatus;
    labor_amount: string;
    total: string;
    notes: string | null;
    services: WorkOrderServiceLine[];
    parts: WorkOrderPartLine[];
}

export interface PartInput {
    name: string;
    quantity: string;
    price: string;
}

export interface WorkOrderForm {
    vehicle_id: string;
    diagnosis: string;
    labor_amount: string;
    service_ids: string[];
    parts: PartInput[];
}

export interface VehicleOption {
    id: string;
    label: string;
}

export interface ServiceOption {
    id: string;
    name: string;
    price: string;
}
