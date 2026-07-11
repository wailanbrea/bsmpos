export interface Vehicle {
    id: string;
    customer_id: string | null;
    customer_name: string | null;
    brand: string;
    model: string | null;
    year: number | null;
    plate: string | null;
    vin: string | null;
    color: string | null;
    mileage: number | null;
    notes: string | null;
    is_active: boolean;
}

export interface VehicleForm {
    customer_id: string;
    brand: string;
    model: string;
    year: string;
    plate: string;
    vin: string;
    color: string;
    mileage: string;
    notes: string;
}

export interface CustomerOption {
    id: string;
    name: string;
}
