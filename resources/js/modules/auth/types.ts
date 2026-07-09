export interface Branch {
    id: string;
    name: string;
    code: string;
    is_main: boolean;
    is_active: boolean;
}

export interface Company {
    id: string;
    name: string;
    currency_code: string;
    is_active: boolean;
    branches: Branch[];
}

export interface AuthUser {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    is_super_admin: boolean;
    companies: Company[];
}

export interface ApiEnvelope<T> {
    success: boolean;
    data: T;
    message: string | null;
}

export interface AuthSession {
    user: AuthUser;
    token: string;
}
