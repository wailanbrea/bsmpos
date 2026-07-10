export interface Permission {
    code: string;
    name: string;
    module_code: string | null;
    description: string | null;
}
export interface Role {
    id: string;
    code: string;
    name: string;
    description: string | null;
    is_system: boolean;
    permissions: Pick<Permission, 'code' | 'name'>[];
}

export interface AccessBranch {
    id: string;
    name: string;
    code: string;
    is_active: boolean;
}

export interface CompanyUser {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    is_owner: boolean;
    default_branch_id: string | null;
    branches: AccessBranch[];
    roles: Pick<Role, 'id' | 'code' | 'name'>[];
}
