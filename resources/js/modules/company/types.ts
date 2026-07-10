export interface ManagedBranch {
    id: string;
    name: string;
    code: string;
    phone: string | null;
    address: string | null;
    is_main: boolean;
    is_active: boolean;
}
