import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { api, storageKeys } from '../../../lib/api';
import type { ApiEnvelope, AuthSession, AuthUser, Branch, Company } from '../types';

const userStorageKey = 'omnipos.auth.user';

function storedUser(): AuthUser | null {
    const serializedUser = localStorage.getItem(userStorageKey);

    if (!serializedUser) {
        return null;
    }

    try {
        return JSON.parse(serializedUser) as AuthUser;
    } catch {
        localStorage.removeItem(userStorageKey);
        return null;
    }
}

export const useSessionStore = defineStore('session', () => {
    const token = ref(localStorage.getItem(storageKeys.token));
    const user = ref<AuthUser | null>(storedUser());
    const companyId = ref(localStorage.getItem(storageKeys.companyId));
    const branchId = ref(localStorage.getItem(storageKeys.branchId));

    const isAuthenticated = computed(() => token.value !== null);
    const company = computed<Company | null>(
        () => user.value?.companies.find((item) => item.id === companyId.value) ?? null,
    );
    const branch = computed<Branch | null>(
        () => company.value?.branches.find((item) => item.id === branchId.value) ?? null,
    );
    const hasContext = computed(() => company.value !== null && branch.value !== null);

    const isOwner = computed(() => {
        if (user.value?.is_super_admin) return true;
        return company.value?.is_owner ?? false;
    });

    const permissions = computed<string[]>(() => {
        return company.value?.permissions ?? [];
    });

    function hasPermission(permissionCode: string): boolean {
        if (isOwner.value) return true;
        if (permissions.value.includes('*')) return true;
        return permissions.value.includes(permissionCode);
    }

    function persistUser(value: AuthUser | null): void {
        user.value = value;

        if (value) {
            localStorage.setItem(userStorageKey, JSON.stringify(value));
            return;
        }

        localStorage.removeItem(userStorageKey);
    }

    function applySession(session: AuthSession): void {
        token.value = session.token;
        localStorage.setItem(storageKeys.token, session.token);
        persistUser(session.user);
        clearContext();
    }

    function selectContext(nextCompanyId: string, nextBranchId: string): void {
        const nextCompany = user.value?.companies.find((item) => item.id === nextCompanyId);
        const nextBranch = nextCompany?.branches.find((item) => item.id === nextBranchId && item.is_active);

        if (!nextCompany || !nextCompany.is_active || !nextBranch) {
            throw new Error('La compañía o sucursal seleccionada ya no está disponible.');
        }

        companyId.value = nextCompanyId;
        branchId.value = nextBranchId;
        localStorage.setItem(storageKeys.companyId, nextCompanyId);
        localStorage.setItem(storageKeys.branchId, nextBranchId);
    }

    function clearContext(): void {
        companyId.value = null;
        branchId.value = null;
        localStorage.removeItem(storageKeys.companyId);
        localStorage.removeItem(storageKeys.branchId);
    }

    async function login(payload: {
        email: string;
        password: string;
        device_name: string;
        code?: string;
    }): Promise<void> {
        const response = await api.post<ApiEnvelope<AuthSession>>('/auth/login', payload);
        applySession(response.data.data);
    }

    async function register(payload: {
        name: string;
        email: string;
        phone?: string;
        password: string;
        password_confirmation: string;
        device_name: string;
    }): Promise<void> {
        const response = await api.post<ApiEnvelope<AuthSession>>('/auth/register', payload);
        applySession(response.data.data);
    }

    async function refreshUser(): Promise<void> {
        const response = await api.get<ApiEnvelope<AuthUser>>('/auth/me');
        persistUser(response.data.data);

        if (!hasContext.value) {
            clearContext();
        }
    }

    async function logout(): Promise<void> {
        try {
            await api.post('/auth/logout');
        } finally {
            token.value = null;
            localStorage.removeItem(storageKeys.token);
            persistUser(null);
            clearContext();
        }
    }

    return {
        branch,
        company,
        hasContext,
        isAuthenticated,
        isOwner,
        permissions,
        user,
        hasPermission,
        login,
        logout,
        refreshUser,
        register,
        selectContext,
    };
});
