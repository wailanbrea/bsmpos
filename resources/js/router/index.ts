import { createRouter, createWebHistory } from 'vue-router';
import DashboardPage from '../modules/dashboard/pages/DashboardPage.vue';
import ContextPage from '../modules/auth/pages/ContextPage.vue';
import LoginPage from '../modules/auth/pages/LoginPage.vue';
import RegisterPage from '../modules/auth/pages/RegisterPage.vue';
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
