import { createRouter, createWebHistory } from 'vue-router';
import DashboardPage from '../modules/dashboard/pages/DashboardPage.vue';
import ContextPage from '../modules/auth/pages/ContextPage.vue';
import LoginPage from '../modules/auth/pages/LoginPage.vue';
import RegisterPage from '../modules/auth/pages/RegisterPage.vue';
import AuditLogPage from '../modules/audit/pages/AuditLogPage.vue';
import RoleManagementPage from '../modules/access/pages/RoleManagementPage.vue';
import UserManagementPage from '../modules/access/pages/UserManagementPage.vue';
import BranchManagementPage from '../modules/company/pages/BranchManagementPage.vue';
import { useSessionStore } from '../modules/auth/stores/session';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: DashboardPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/ingresar',
            name: 'login',
            component: LoginPage,
        },
        {
            path: '/crear-cuenta',
            name: 'register',
            component: RegisterPage,
        },
        {
            path: '/seleccionar-contexto',
            name: 'context',
            component: ContextPage,
            meta: { requiresAuth: true },
        },
        {
            path: '/auditoria',
            name: 'audit-log',
            component: AuditLogPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/roles',
            name: 'roles',
            component: RoleManagementPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/usuarios',
            name: 'users',
            component: UserManagementPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/sucursales',
            name: 'branches',
            component: BranchManagementPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
    ],
});

router.beforeEach((to) => {
    const session = useSessionStore();

    if (to.meta.requiresAuth && !session.isAuthenticated) {
        return { name: 'login' };
    }

    if ((to.name === 'login' || to.name === 'register') && session.isAuthenticated) {
        return { name: session.hasContext ? 'dashboard' : 'context' };
    }

    if (to.meta.requiresContext && !session.hasContext) {
        return { name: 'context' };
    }

    return true;
});

export default router;
