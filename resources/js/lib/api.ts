import axios from 'axios';

const storageKeys = {
    token: 'omnipos.auth.token',
    companyId: 'omnipos.context.company-id',
    branchId: 'omnipos.context.branch-id',
} as const;

export const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem(storageKeys.token);
    const companyId = localStorage.getItem(storageKeys.companyId);
    const branchId = localStorage.getItem(storageKeys.branchId);

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (companyId) {
        config.headers['X-Company-Id'] = companyId;
    }

    if (branchId) {
        config.headers['X-Branch-Id'] = branchId;
    }

    return config;
});

export { storageKeys };
