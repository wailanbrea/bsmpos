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
import CustomerListPage from '../modules/customers/pages/CustomerListPage.vue';
import ProductListPage from '../modules/products/pages/ProductListPage.vue';
import InventoryPage from '../modules/inventory/pages/InventoryPage.vue';
import PosPage from '../modules/pos/pages/PosPage.vue';
import RestaurantLayoutPage from '../modules/restaurant/pages/RestaurantLayoutPage.vue';
import KitchenKdsPage from '../modules/restaurant/pages/KitchenKdsPage.vue';
import ElectronicInvoicePage from '../modules/electronic-invoices/pages/ElectronicInvoicePage.vue';
import ReportsPage from '../modules/reports/pages/ReportsPage.vue';
import SecurityPage from '../modules/security/pages/SecurityPage.vue';
import AgendaPage from '../modules/appointments/pages/AgendaPage.vue';
import EmployeeListPage from '../modules/employees/pages/EmployeeListPage.vue';
import VehicleListPage from '../modules/vehicles/pages/VehicleListPage.vue';
import WorkOrderListPage from '../modules/work-orders/pages/WorkOrderListPage.vue';
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
        {
            path: '/clientes',
            name: 'customers',
            component: CustomerListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'customer' },
        },
        {
            path: '/productos',
            name: 'products',
            component: ProductListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'product' },
        },
        {
            path: '/inventario',
            name: 'inventory',
            component: InventoryPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'inventory' },
        },
        {
            path: '/pos',
            name: 'pos',
            component: PosPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'pos' },
        },
        {
            path: '/restaurant/layout',
            name: 'restaurant-layout',
            component: RestaurantLayoutPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'restaurant' },
        },
        {
            path: '/kitchen/kds',
            name: 'kitchen-kds',
            component: KitchenKdsPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'restaurant' },
        },
        {
            path: '/facturacion-electronica',
            name: 'electronic-invoices',
            component: ElectronicInvoicePage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'electronic_invoice' },
        },
        {
            path: '/reportes',
            name: 'reports',
            component: ReportsPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'report' },
        },
        {
            path: '/seguridad',
            name: 'security',
            component: SecurityPage,
            meta: { requiresAuth: true, requiresContext: true },
        },
        {
            path: '/agenda',
            name: 'agenda',
            component: AgendaPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'appointment' },
        },
        {
            path: '/empleados',
            name: 'employees',
            component: EmployeeListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'employee' },
        },
        {
            path: '/vehiculos',
            name: 'vehicles',
            component: VehicleListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'vehicle' },
        },
        {
            path: '/ordenes-trabajo',
            name: 'work-orders',
            component: WorkOrderListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'work_order' },
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
