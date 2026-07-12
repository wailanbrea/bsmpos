<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useSessionStore } from '../../auth/stores/session';
import { useModuleStore } from '../../module-manager/stores/modules';

interface NavItem {
    label: string;
    to: string;
    module?: string;
}

const allNavigation: NavItem[] = [
    { label: 'Resumen', to: '/' },
    { label: 'POS', to: '/pos', module: 'pos' },
    { label: 'Mesas 🍽️', to: '/restaurant/layout', module: 'restaurant' },
    { label: 'Cocina KDS 🍳', to: '/kitchen/kds', module: 'restaurant' },
    { label: 'Productos', to: '/productos', module: 'product' },
    { label: 'Inventario', to: '/inventario', module: 'inventory' },
    { label: 'Clientes', to: '/clientes', module: 'customer' },
    { label: 'Agenda 💈', to: '/agenda', module: 'appointment' },
    { label: 'Empleados', to: '/empleados', module: 'employee' },
    { label: 'Vehículos 🚗', to: '/vehiculos', module: 'vehicle' },
    { label: 'Órdenes 🔧', to: '/ordenes-trabajo', module: 'work_order' },
    { label: 'Módulos', to: '/configuracion/modulos', module: 'module_manager' },
    { label: 'Reportes', to: '/reportes', module: 'report' },
    { label: 'Configuración', to: '/configuracion/fiscal', module: 'setting' },
    { label: 'Facturación electrónica', to: '/facturacion-electronica', module: 'electronic_invoice' },
    { label: 'Auditoría', to: '/auditoria', module: 'audit' },
    { label: 'Roles', to: '/roles', module: 'user_access' },
    { label: 'Usuarios', to: '/usuarios', module: 'user_access' },
    { label: 'Sucursales', to: '/sucursales', module: 'company' },
    { label: 'Seguridad', to: '/seguridad' },
];

const session = useSessionStore();
const modules = useModuleStore();
const operatorName = computed(() => session.user?.name ?? 'Operador');
const navigation = computed(() => allNavigation.filter((item) => !item.module || modules.canUse(item.module)));

onMounted(() => {
    void modules.loadModules();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-7xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold tracking-wide text-[#3525cd]">OmniPOS · turno activo</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Panel de control</h1>
                    <p class="mt-1 text-sm text-[#464555]">{{ operatorName }}</p>
                </div>
                <RouterLink
                    v-if="modules.canUse('pos')"
                    to="/pos"
                    class="min-h-12 rounded-lg bg-[#3525cd] px-5 font-semibold text-white shadow-sm transition active:scale-[.98] flex items-center justify-center"
                >
                    Abrir POS
                </RouterLink>
            </header>

            <section
                class="mb-6 grid gap-3 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm md:grid-cols-3"
                aria-label="Estado operativo"
            >
                <div class="flex items-center gap-3 border-b border-[#e4e1ee] pb-3 md:border-r md:border-b-0 md:pb-0">
                    <span class="h-3 w-3 rounded-full bg-[#006c49]" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-[#464555]">Conexión</p>
                        <p class="font-semibold">En línea</p>
                    </div>
                </div>
                <div class="border-b border-[#e4e1ee] pb-3 md:border-r md:border-b-0 md:pb-0">
                    <p class="text-xs text-[#464555]">Sucursal activa</p>
                    <p class="font-semibold">{{ session.branch?.name }}</p>
                    <RouterLink
                        to="/seleccionar-contexto"
                        class="mt-1 inline-block text-xs font-semibold text-[#3525cd]"
                        >Cambiar</RouterLink
                    >
                </div>
                <div>
                    <p class="text-xs text-[#464555]">Caja</p>
                    <p class="font-semibold">Requiere apertura</p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
                <nav class="rounded-2xl bg-[#302f39] p-3 text-[#f3effc]" aria-label="Navegación principal">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-[#c3c0ff]">Módulos</p>
                    <RouterLink
                        v-for="item in navigation"
                        :key="item.label"
                        :to="item.to"
                        class="flex min-h-12 items-center rounded-lg px-3 text-base font-medium hover:bg-white/10 focus:bg-white/10"
                    >
                        {{ item.label }}
                    </RouterLink>
                </nav>
                <section
                    class="rounded-2xl border border-dashed border-[#c7c4d8] bg-[#f5f2ff] p-8"
                    aria-labelledby="setup-title"
                >
                    <p class="text-sm font-semibold text-[#006c49]">Base técnica lista</p>
                    <h2 id="setup-title" class="mt-2 text-2xl font-semibold">
                        Configura tu empresa para empezar a vender.
                    </h2>
                    <p class="mt-3 max-w-2xl text-[#464555]">
                        El onboarding, los módulos y las métricas aparecerán aquí cuando se complete el núcleo SaaS.
                    </p>
                </section>
            </div>
        </div>
    </main>
</template>
