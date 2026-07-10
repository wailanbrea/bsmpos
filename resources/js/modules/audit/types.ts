export interface AuditActor {
    id: string;
    name: string;
}

export interface AuditLog {
    id: string;
    action: string;
    module: string;
    user: AuditActor | null;
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    ip: string | null;
    created_at: string;
}

export interface AuditPagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
