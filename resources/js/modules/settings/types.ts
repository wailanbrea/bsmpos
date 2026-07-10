export interface Tax {
    id: string;
    name: string;
    code: string;
    rate: string;
    type: string;
    scope: string;
    is_inclusive: boolean;
    is_retention: boolean;
    is_active: boolean;
}

export interface PaymentMethod {
    id: string;
    name: string;
    code: string;
    requires_reference: boolean;
    is_active: boolean;
}

export interface DocumentType {
    code: string;
    name: string;
    is_electronic: boolean;
    requires_customer_tax_id: boolean;
}

export interface FiscalSettings {
    taxes: Tax[];
    payment_methods: PaymentMethod[];
    document_types: DocumentType[];
    currencies: string;
}

export interface NcfSequence {
    id: number;
    document_type_code: string;
    series: string;
    start_number: number;
    end_number: number;
    current_number: number;
    remaining: number;
    expires_at: string | null;
    alert_threshold: number;
    is_active: boolean;
}
