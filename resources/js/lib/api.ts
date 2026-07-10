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

// Un 401 con token almacenado significa sesión revocada o expirada: limpiar el
// estado local y volver al login evita quedar "medio autenticado" con menú vacío.
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = axios.isAxiosError(error) ? error.response?.status : null;
        const hadToken = localStorage.getItem(storageKeys.token) !== null;

        if (status === 401 && hadToken) {
            localStorage.removeItem(storageKeys.token);
            localStorage.removeItem(storageKeys.companyId);
            localStorage.removeItem(storageKeys.branchId);
            localStorage.removeItem('omnipos.auth.user');
            window.location.assign('/ingresar');
        }

        return Promise.reject(error);
    },
);

export { storageKeys };
