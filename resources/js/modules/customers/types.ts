export interface Customer {
    id: string;
    kind: string;
    name: string;
    tax_id_type: string | null;
    tax_id: string | null;
    phone: string | null;
    whatsapp: string | null;
    email: string | null;
    address: string | null;
    is_generic: boolean;
    credit_limit: string;
    credit_days: number;
    balance: string;
    available_credit: string;
    is_active: boolean;
    notes: string | null;
}

export interface CustomerForm {
    kind: string;
    name: string;
    tax_id_type: string;
    tax_id: string;
    phone: string;
    whatsapp: string;
    email: string;
    address: string;
    credit_limit: number;
    credit_days: number;
}
