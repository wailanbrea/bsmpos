import { createRouter, createWebHistory } from 'vue-router';
import DashboardPage from '../modules/dashboard/pages/DashboardPage.vue';
import ContextPage from '../modules/auth/pages/ContextPage.vue';
import LoginPage from '../modules/auth/pages/LoginPage.vue';
import RegisterPage from '../modules/auth/pages/RegisterPage.vue';
import AuditLogPage from '../modules/audit/pages/AuditLogPage.vue';
import RoleManagementPage from '../modules/access/pages/RoleManagementPage.vue';
import UserManagementPage from '../modules/access/pages/UserManagementPage.vue';
import BranchManagementPage from '../modules/company/pages/BranchManagementPage.vue';
import ModuleManagementPage from '../modules/module-manager/pages/ModuleManagementPage.vue';
import OnboardingPage from '../modules/module-manager/pages/OnboardingPage.vue';
import ConfigurationPage from '../modules/settings/pages/ConfigurationPage.vue';
import { useSessionStore } from '../modules/auth/stores/session';
import { useModuleStore } from '../modules/module-manager/stores/modules';

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
        {
            path: '/configuracion/inicial',
            name: 'onboarding',
            component: OnboardingPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/configuracion/modulos',
            name: 'modules',
            component: ModuleManagementPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/configuracion/fiscal',
            name: 'settings-fiscal',
            component: ConfigurationPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
    ],
});

router.beforeEach(async (to) => {
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

    if (to.meta.requiresContext && session.hasContext) {
        const modules = useModuleStore();
        modules.ensureCompany(session.company?.id ?? null);

        try {
            await modules.loadModules();
        } catch {
            return true;
        }

        if (!modules.hasBusinessType && to.name !== 'onboarding') {
            return { name: 'onboarding' };
        }

        if (typeof to.meta.requiresModule === 'string' && !modules.canUse(to.meta.requiresModule)) {
            return { name: 'dashboard' };
        }
    }

    return true;
});

export default router;
