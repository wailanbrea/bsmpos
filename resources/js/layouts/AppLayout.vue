<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from '../modules/auth/stores/session';
import { useModuleStore } from '../modules/module-manager/stores/modules';
import { getCurrentLanguage, setLanguage, type AppLocale } from '../i18n';
import NotificationDropdown from '../components/NotificationDropdown.vue';

interface NavItem {
    labelKey: string;
    to: string;
    icon: string;
    module?: string;
    badge?: string;
}

interface NavSection {
    titleKey: string;
    items: NavItem[];
}

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const modules = useModuleStore();

const isMobileMenuOpen = ref(false);
const searchQuery = ref('');
const currentLang = ref<AppLocale>(getCurrentLanguage());

function toggleLanguage(): void {
    const nextLang: AppLocale = currentLang.value === 'es' ? 'en' : 'es';
    setLanguage(nextLang);
    currentLang.value = nextLang;
}

const sections: NavSection[] = [
    {
        titleKey: 'nav.dailyOperations',
        items: [
            { labelKey: 'nav.pos', to: '/pos', icon: 'point_of_sale', module: 'pos' },
            { labelKey: 'nav.tables', to: '/restaurant/layout', icon: 'table_restaurant', module: 'restaurant' },
            { labelKey: 'nav.kds', to: '/kitchen/kds', icon: 'skillet', module: 'restaurant' },
            { labelKey: 'nav.agenda', to: '/agenda', icon: 'calendar_month', module: 'appointment' },
            { labelKey: 'nav.workOrders', to: '/ordenes-trabajo', icon: 'build', module: 'work_order' },
        ],
    },
    {
        titleKey: 'nav.inventory',
        items: [
            { labelKey: 'nav.products', to: '/productos', icon: 'inventory_2', module: 'product' },
            { labelKey: 'nav.inventoryStock', to: '/inventario', icon: 'local_shipping', module: 'inventory' },
            { labelKey: 'nav.customers', to: '/clientes', icon: 'group', module: 'customer' },
            { labelKey: 'nav.vehicles', to: '/vehiculos', icon: 'directions_car', module: 'vehicle' },
            { labelKey: 'nav.employees', to: '/empleados', icon: 'badge', module: 'employee' },
        ],
    },
    {
        titleKey: 'nav.fiscalReports',
        items: [
            { labelKey: 'nav.reports', to: '/reportes', icon: 'description', module: 'report' },
            { labelKey: 'nav.electronicInvoice', to: '/facturacion-electronica', icon: 'verified', module: 'electronic_invoice' },
            { labelKey: 'nav.fiscalSettings', to: '/configuracion/fiscal', icon: 'receipt_long', module: 'setting' },
        ],
    },
    {
        titleKey: 'nav.saasAdmin',
        items: [
            { labelKey: 'nav.modules', to: '/configuracion/modulos', icon: 'extension', module: 'module_manager' },
            { labelKey: 'nav.roles', to: '/roles', icon: 'shield_person', module: 'user_access' },
            { labelKey: 'nav.users', to: '/usuarios', icon: 'manage_accounts', module: 'user_access' },
            { labelKey: 'nav.branches', to: '/sucursales', icon: 'business', module: 'company' },
            { labelKey: 'nav.terminals', to: '/configuracion/terminales', icon: 'devices' },
            { labelKey: 'nav.audit', to: '/auditoria', icon: 'list_alt', module: 'audit' },
            { labelKey: 'nav.security', to: '/seguridad', icon: 'security' },
        ],
    },
];

const filteredSections = computed(() => {
    return sections
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => !item.module || modules.canUse(item.module)),
        }))
        .filter((section) => section.items.length > 0);
});

const companyName = computed(() => session.company?.name ?? 'BSM-POS Enterprise');
const branchName = computed(() => session.branch?.name ?? 'Santo Domingo #04');
const userName = computed(() => session.user?.name ?? 'Carlos Mendez');
const userRole = computed(() => (session.user?.is_super_admin ? 'Propietario' : 'Store Manager'));
const userInitial = computed(() => (userName.value.charAt(0) || 'U').toUpperCase());
const isPosRoute = computed(() => route.path === '/pos' || route.name === 'pos');

function toggleMobileMenu(): void {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function closeMobileMenu(): void {
    isMobileMenuOpen.value = false;
}

async function handleLogout(): Promise<void> {
    if (window.confirm(t('common.logoutConfirm'))) {
        await session.logout();
        await router.push('/ingresar');
    }
}

watch(
    () => route.path,
    () => {
        closeMobileMenu();
    },
);

onMounted(() => {
    if (session.isAuthenticated && session.hasContext) {
        void modules.loadModules();
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#f8f9ff] text-[#0b1c30] font-sans flex flex-col">
        <!-- BACKDROP MÓVIL -->
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-40 bg-black/50 backdrop-blur-xs lg:hidden transition-opacity"
            @click="closeMobileMenu"
        />

        <!-- SIDEBAR FIJO IZQUIERDO (260px) -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-[260px] bg-[#18181b] text-[#f4f4f5] flex flex-col transition-transform duration-200 ease-in-out border-r border-zinc-800 shadow-2xl py-4"
            :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <!-- CABECERA DEL SIDEBAR -->
            <div class="px-5 mb-4 flex items-center justify-between shrink-0">
                <RouterLink to="/" class="flex items-center space-x-2">
                    <span class="font-bold text-white text-lg tracking-wider font-geist">BSM-POS</span>
                    <span class="text-[11px] bg-[#4648d4]/25 text-[#c0c1ff] font-semibold px-2 py-0.5 rounded">e-CF</span>
                </RouterLink>

                <button
                    type="button"
                    class="lg:hidden p-1 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800"
                    aria-label="Cerrar menú"
                    @click="closeMobileMenu"
                >
                    ✕
                </button>
            </div>

            <!-- CONTEXTO SUCURSAL ACTIVA -->
            <div class="mx-4 mb-4 p-2.5 bg-zinc-800/90 rounded-lg border border-zinc-700/70 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <span class="material-symbols-outlined text-[#6063ee] text-[20px] shrink-0">store</span>
                    <div class="min-w-0">
                        <p class="text-[10px] uppercase font-medium tracking-wider text-zinc-400 truncate leading-none" :title="companyName">
                            {{ companyName }}
                        </p>
                        <p class="text-xs font-semibold text-white truncate mt-1 leading-tight" :title="branchName">
                            {{ branchName }}
                        </p>
                    </div>
                </div>
                <RouterLink
                    to="/seleccionar-contexto"
                    class="p-1 text-zinc-400 hover:text-white rounded transition"
                    :title="t('common.switchStore')"
                >
                    <span class="material-symbols-outlined text-[18px]">unfold_more</span>
                </RouterLink>
            </div>

            <!-- NAVEGACIÓN PRINCIPAL -->
            <nav class="flex-1 px-3 space-y-4 overflow-y-auto no-scrollbar" aria-label="Navegación principal">
                <!-- ACCESO RÁPIDO A RESUMEN / DASHBOARD -->
                <div>
                    <RouterLink
                        to="/"
                        class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-all"
                        :class="[
                            route.path === '/'
                                ? 'bg-[#4648d4] text-white shadow-sm'
                                : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'
                        ]"
                    >
                        <span class="material-symbols-outlined mr-2.5 text-[18px]">dashboard</span>
                        <span>{{ t('nav.dashboard') }}</span>
                    </RouterLink>
                </div>

                <!-- SECCIONES CATEGORIZADAS -->
                <div v-for="section in filteredSections" :key="section.titleKey">
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1.5 px-3 font-semibold">
                        {{ t(section.titleKey) }}
                    </p>
                    <div class="space-y-0.5">
                        <RouterLink
                            v-for="item in section.items"
                            :key="item.to"
                            :to="item.to"
                            class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs transition-all"
                            :class="[
                                route.path === item.to || (item.to !== '/' && route.path.startsWith(item.to))
                                    ? 'bg-[#4648d4] text-white font-medium shadow-xs'
                                    : 'text-zinc-300 hover:bg-zinc-800 hover:text-white font-normal'
                            ]"
                        >
                            <span class="flex items-center truncate">
                                <span class="material-symbols-outlined mr-2.5 text-[18px] shrink-0">{{ item.icon }}</span>
                                <span class="truncate">{{ t(item.labelKey) }}</span>
                            </span>
                            <span
                                v-if="item.badge"
                                class="text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.2 rounded bg-emerald-600 text-white"
                            >
                                {{ item.badge }}
                            </span>
                        </RouterLink>
                    </div>
                </div>
            </nav>

            <!-- FOOTER DEL SIDEBAR (USUARIO Y LOGOUT) -->
            <div class="px-4 pt-3 mt-2 border-t border-zinc-800 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-[#4648d4] text-white font-bold flex items-center justify-center shrink-0 text-xs shadow-xs">
                        {{ userInitial }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate leading-tight">{{ userName }}</p>
                        <p class="text-[10px] text-zinc-400 truncate mt-0.5">{{ userRole }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="p-1.5 text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800 transition shrink-0"
                    :title="t('common.logout')"
                    :aria-label="t('common.logout')"
                    @click="handleLogout"
                >
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                </button>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL CON OFFSET DE SIDEBAR -->
        <div class="lg:pl-[260px] flex flex-col flex-1 min-w-0">
            <!-- TOPBAR HEADER (FIXED TOP) -->
            <header class="fixed top-0 left-0 lg:left-[260px] right-0 h-16 bg-[#f8f9ff]/85 backdrop-blur-xl border-b border-[#e2e8f0] shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 flex items-center justify-between px-4 md:px-6">
                <!-- BUSCADOR GLOBAL Y TOGGLE MÓVIL -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="lg:hidden p-2 rounded-lg border border-zinc-300 text-zinc-800 hover:bg-zinc-100"
                        aria-label="Abrir menú de navegación"
                        @click="toggleMobileMenu"
                    >
                        ☰
                    </button>

                    <div class="flex items-center bg-[#eff4ff] px-3 py-1.5 rounded-lg w-56 sm:w-80 md:w-96 border border-[#c7c4d7]/40">
                        <span class="material-symbols-outlined text-[#767586] mr-2 text-[18px]">search</span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('common.globalSearch')"
                            class="bg-transparent border-none outline-none text-xs md:text-sm text-[#0b1c30] w-full placeholder:text-[#767586]"
                        >
                    </div>
                </div>

                <!-- CONTROLES DERECHA (ESTADO DGII, SELECTOR IDIOMA, NOTIFICACIONES, ABRIR POS) -->
                <div class="flex items-center space-x-2 md:space-x-3">
                    <div class="hidden sm:flex items-center space-x-2 bg-[#eff4ff] px-3 py-1 rounded-full border border-[#c7c4d7]/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        <span class="text-xs font-semibold text-[#0b1c30]">
                            {{ modules.canUse('electronic_invoice') ? t('common.dgiiOnline') : t('common.ncfReady') }}
                        </span>
                    </div>

                    <!-- SELECTOR DE IDIOMA (ES / EN) -->
                    <button
                        type="button"
                        class="flex items-center space-x-1.5 px-2.5 py-1.5 rounded-lg border border-[#c7c4d7]/50 bg-white hover:bg-[#eff4ff] text-xs font-semibold text-[#0b1c30] transition shadow-2xs cursor-pointer"
                        :title="t('common.changeLanguage')"
                        @click="toggleLanguage"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#4648d4]">translate</span>
                        <span class="uppercase tracking-wider font-mono text-[11px]">{{ currentLang }}</span>
                    </button>

                    <!-- CENTRO DE NOTIFICACIONES -->
                    <NotificationDropdown />

                    <RouterLink
                        v-if="modules.canUse('pos')"
                        to="/pos"
                        class="bg-[#4648d4] hover:bg-[#393bb3] text-white px-3.5 md:px-4 py-2 rounded-lg font-medium text-xs md:text-sm flex items-center shadow-xs transition-all active:scale-95"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">point_of_sale</span>
                        <span>{{ t('common.openTerminal') }}</span>
                    </RouterLink>
                </div>
            </header>

            <!-- VISTA DE PÁGINA (MAIN) -->
            <main
                class="relative bg-[#f8f9ff] min-w-0"
                :class="[
                    isPosRoute
                        ? 'h-screen max-h-screen overflow-hidden flex flex-col pt-16'
                        : 'min-h-screen pt-16 flex-1'
                ]"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
