export interface Employee {
    id: string;
    name: string;
    position: string | null;
    phone: string | null;
    email: string | null;
    commission_rate: string;
    is_active: boolean;
    notes: string | null;
}

export interface EmployeeForm {
    name: string;
    position: string;
    phone: string;
    email: string;
    commission_rate: string;
    is_active: boolean;
}
