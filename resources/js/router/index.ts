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
import AgentTerminalsPage from '../modules/settings/pages/AgentTerminalsPage.vue';
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
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'audit.view' },
        },
        {
            path: '/roles',
            name: 'roles',
            component: RoleManagementPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'access.roles.view' },
        },
        {
            path: '/usuarios',
            name: 'users',
            component: UserManagementPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'access.users.manage' },
        },
        {
            path: '/sucursales',
            name: 'branches',
            component: BranchManagementPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'company.manage' },
        },
        {
            path: '/configuracion/inicial',
            name: 'onboarding',
            component: OnboardingPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'modules.manage' },
        },
        {
            path: '/configuracion/modulos',
            name: 'modules',
            component: ModuleManagementPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'modules.view' },
        },
        {
            path: '/configuracion',
            name: 'settings',
            component: ConfigurationPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'settings.view' },
        },
        {
            path: '/configuracion/fiscal',
            name: 'settings-fiscal',
            component: ConfigurationPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'settings.view' },
        },
        {
            path: '/configuracion/terminales',
            name: 'settings-terminals',
            component: AgentTerminalsPage,
            meta: { requiresAuth: true, requiresContext: true, requiresPermission: 'settings.view' },
        },
        {
            path: '/clientes',
            name: 'customers',
            component: CustomerListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'customer', requiresPermission: 'customers.view' },
        },
        {
            path: '/productos',
            name: 'products',
            component: ProductListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'product', requiresPermission: 'products.view' },
        },
        {
            path: '/inventario',
            name: 'inventory',
            component: InventoryPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'inventory', requiresPermission: 'inventory.view' },
        },
        {
            path: '/pos',
            name: 'pos',
            component: PosPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'pos', requiresPermission: 'pos.view' },
        },
        {
            path: '/restaurant/layout',
            alias: '/mesas',
            name: 'restaurant-layout',
            component: RestaurantLayoutPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'restaurant' },
        },
        {
            path: '/kitchen/kds',
            alias: ['/cocina', '/kds'],
            name: 'kitchen-kds',
            component: KitchenKdsPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'restaurant' },
        },
        {
            path: '/facturacion-electronica',
            name: 'electronic-invoices',
            component: ElectronicInvoicePage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'electronic_invoice', requiresPermission: 'einvoice.view' },
        },
        {
            path: '/reportes',
            name: 'reports',
            component: ReportsPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'report', requiresPermission: 'reports.view' },
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
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'appointment', requiresPermission: 'appointments.view' },
        },
        {
            path: '/empleados',
            name: 'employees',
            component: EmployeeListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'employee', requiresPermission: 'employees.view' },
        },
        {
            path: '/vehiculos',
            name: 'vehicles',
            component: VehicleListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'vehicle', requiresPermission: 'vehicles.view' },
        },
        {
            path: '/ordenes-trabajo',
            name: 'work-orders',
            component: WorkOrderListPage,
            meta: { requiresAuth: true, requiresContext: true, requiresModule: 'work_order', requiresPermission: 'work_orders.view' },
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

        if (typeof to.meta.requiresPermission === 'string' && !session.hasPermission(to.meta.requiresPermission)) {
            return { name: 'dashboard' };
        }
    }

    return true;
});

export default router;
