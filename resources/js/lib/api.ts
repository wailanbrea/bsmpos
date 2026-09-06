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
// Se valida que el 401 provenga del token activo actual para que peticiones obsoletas
// en vuelo no destruyan una sesión recién iniciada.
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = axios.isAxiosError(error) ? error.response?.status : null;
        const currentToken = localStorage.getItem(storageKeys.token);

        if (status === 401 && currentToken) {
            const headers = error.config?.headers;
            const requestAuth =
                headers?.Authorization ||
                headers?.authorization ||
                (typeof headers?.get === 'function' ? (headers.get('Authorization') || headers.get('authorization')) : null);
            const requestToken = typeof requestAuth === 'string' ? requestAuth.replace(/^Bearer\s+/i, '') : null;

            // Si la petición se envió con un token distinto al actual, o no llevaba token de auth, no destruir la sesión activa
            if (requestToken && requestToken !== currentToken) {
                return Promise.reject(error);
            }

            // Solo desloguear si la petición fallida realmente usó el token activo
            if (requestToken && requestToken === currentToken) {
                localStorage.removeItem(storageKeys.token);
                localStorage.removeItem(storageKeys.companyId);
                localStorage.removeItem(storageKeys.branchId);
                localStorage.removeItem('omnipos.auth.user');

                if (window.location.pathname !== '/ingresar' && window.location.pathname !== '/crear-cuenta') {
                    window.location.assign('/ingresar');
                }
            }
        }

        return Promise.reject(error);
    },
);

export { storageKeys };
