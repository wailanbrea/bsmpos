<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useSessionStore } from '../../auth/stores/session';

const session = useSessionStore();
const branchName = computed(() => session.branch?.name ?? 'Sucursal Principal');

const tableFilter = ref('');

interface RecentSale {
    ncf: string;
    customer: string;
    paymentMethod: string;
    icon: string;
    amount: string;
    status: string;
}

const recentSales: RecentSale[] = [
    {
        ncf: 'E310000000142',
        customer: 'Juan Perez (Consumidor Final)',
        paymentMethod: 'Tarjeta de Crédito',
        icon: 'credit_card',
        amount: 'RD$ 3,450.00',
        status: 'Cobrado',
    },
    {
        ncf: 'E320000000088',
        customer: 'Distribuidora Corripio SRL',
        paymentMethod: 'Transferencia Bancaria',
        icon: 'account_balance',
        amount: 'RD$ 24,100.00',
        status: 'Cobrado',
    },
    {
        ncf: 'E310000000141',
        customer: 'Maria Almonte',
        paymentMethod: 'Efectivo (RD$)',
        icon: 'payments',
        amount: 'RD$ 1,250.00',
        status: 'Cobrado',
    },
    {
        ncf: 'E310000000140',
        customer: 'Ferretería La Central',
        paymentMethod: 'Tarjeta de Débito',
        icon: 'credit_card',
        amount: 'RD$ 8,900.00',
        status: 'Cobrado',
    },
];

const filteredSales = computed(() => {
    const q = tableFilter.value.trim().toLowerCase();
    if (!q) return recentSales;
    return recentSales.filter(
        (s) => s.ncf.toLowerCase().includes(q) || s.customer.toLowerCase().includes(q),
    );
});
</script>

<template>
    <div class="space-y-6">
        <!-- OPERATIONAL KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- DAILY SALES -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#4648d4]" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Ventas del Día</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e1e0ff] flex items-center justify-center text-[#4648d4]">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 48,250.00</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-600 font-medium">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        <span>+8.2% vs ayer</span>
                    </div>
                </div>
            </div>

            <!-- CASH DRAWER -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#595c5e]" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Caja de Turno</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e5eeff] flex items-center justify-center text-[#595c5e]">
                        <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 5,000.00</div>
                    <div class="flex items-center space-x-1.5 text-xs text-[#5f5e61]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500" />
                        <span>Caja 01 Activa</span>
                    </div>
                </div>
            </div>

            <!-- INVENTORY ALERTS -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Alertas de Inventario</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">3 Productos</div>
                    <div class="flex items-center space-x-1 text-xs text-amber-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        <span>Bajo stock mínimo</span>
                    </div>
                </div>
            </div>

            <!-- DGII E-CF -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-600" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">e-CF Emitidos (DGII)</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">142 Comprobantes</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        <span>100% aceptados</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENTO GRID QUICK OPERATIONS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- LARGE POS SHORTCUT CARD (2 COLS) -->
            <div class="lg:col-span-2 bg-gradient-to-br from-[#6063ee] to-[#4648d4] text-white p-6 md:p-8 rounded-xl shadow-md flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-x-6 translate-y-6 pointer-events-none">
                    <span class="material-symbols-outlined text-[180px]">point_of_sale</span>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 text-white px-2.5 py-0.5 rounded-full text-[11px] font-semibold">
                            Motor de Venta Rápida
                        </span>
                        <span class="text-xs text-[#c0c1ff]">{{ branchName }}</span>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold font-geist mb-2 tracking-tight">
                        Terminal POS Comercial
                    </h3>
                    <p class="text-xs md:text-sm text-[#e1e0ff] max-w-lg mb-6 leading-relaxed">
                        Lector de código de barras, búsqueda predictiva de productos, control de stock en tiempo real y facturación electrónica fiscal DGII.
                    </p>
                </div>
                <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                    <RouterLink
                        to="/pos"
                        class="bg-white text-[#4648d4] px-5 py-2.5 rounded-lg text-xs md:text-sm font-bold hover:bg-[#f8f9ff] transition-all flex items-center shadow-xs active:scale-95"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">bolt</span>
                        <span>Lanzar Terminal POS</span>
                    </RouterLink>
                    <RouterLink
                        to="/productos"
                        class="bg-[#6063ee] text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-black/15 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">inventory_2</span>
                        <span>Catálogo de Productos</span>
                    </RouterLink>
                    <RouterLink
                        to="/inventario"
                        class="bg-white/10 text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-white/20 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">warehouse</span>
                        <span>Stock de Almacén</span>
                    </RouterLink>
                </div>
            </div>

            <!-- INVENTORY CAPACITY CARD (1 COL) -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Capacidad de Almacén</span>
                        <span class="material-symbols-outlined text-[#5f5e61] text-[18px]">inventory</span>
                    </div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30] mb-0.5">Stock General</h3>
                    <p class="text-xs text-[#5f5e61] mb-4">{{ branchName }}</p>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-xs">
                            <span class="text-[#5f5e61]">Espacio Utilizado</span>
                            <span class="font-semibold text-[#0b1c30]">73.6% (148 SKUs)</span>
                        </div>
                        <div class="w-full h-2 bg-[#e5eeff] rounded-full overflow-hidden">
                            <div class="bg-[#4648d4] h-full rounded-full" style="width: 73.6%;" />
                        </div>
                    </div>
                </div>
                <div class="pt-3 border-t border-[#e2e8f0] flex items-center justify-between">
                    <span class="text-xs text-amber-700 font-medium">3 productos por reordenar</span>
                    <RouterLink to="/inventario" class="text-xs text-[#4648d4] font-semibold hover:underline">
                        Gestionar stock →
                    </RouterLink>
                </div>
            </div>

            <!-- DGII E-CF SEQUENCES STATUS (1 COL) -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Secuencias e-CF</span>
                        <span class="material-symbols-outlined text-emerald-600 text-[18px]">verified</span>
                    </div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30] mb-0.5">Comprobantes Activos</h3>
                    <p class="text-xs text-[#5f5e61] mb-3">Series fiscales autorizadas por DGII</p>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2.5 bg-[#eff4ff] rounded-lg">
                            <span class="text-xs font-mono font-medium text-[#0b1c30]">E310000000142 (Consumo)</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded">
                                Activa
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-2.5 bg-[#eff4ff] rounded-lg">
                            <span class="text-xs font-mono font-medium text-[#0b1c30]">E320000000088 (Crédito Fiscal)</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded">
                                Activa
                            </span>
                        </div>
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-[#e2e8f0] flex items-center justify-between">
                    <span class="text-xs text-[#5f5e61]">Sincronización online</span>
                    <RouterLink to="/configuracion/fiscal" class="text-xs text-[#4648d4] font-semibold hover:underline">
                        Ajustes fiscales →
                    </RouterLink>
                </div>
            </div>

            <!-- QUICK ACCESS SHORTCUTS (2 COLS) -->
            <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Operaciones Rápidas</span>
                        <span class="text-xs text-[#5f5e61]">Acceso directo a módulos clave</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <RouterLink
                            to="/pos"
                            class="p-3 bg-[#eff4ff] border border-[#dce9ff] rounded-lg hover:bg-[#e5eeff] transition-colors block text-left"
                        >
                            <span class="material-symbols-outlined text-[#4648d4] text-[22px] mb-1 block">point_of_sale</span>
                            <div class="font-semibold text-xs text-[#0b1c30]">Nueva Venta</div>
                            <p class="text-[11px] text-[#5f5e61]">Facturar en POS</p>
                        </RouterLink>

                        <RouterLink
                            to="/productos"
                            class="p-3 bg-[#eff4ff] border border-[#dce9ff] rounded-lg hover:bg-[#e5eeff] transition-colors block text-left"
                        >
                            <span class="material-symbols-outlined text-[#4648d4] text-[22px] mb-1 block">add_box</span>
                            <div class="font-semibold text-xs text-[#0b1c30]">Crear Producto</div>
                            <p class="text-[11px] text-[#5f5e61]">Agregar al catálogo</p>
                        </RouterLink>

                        <RouterLink
                            to="/clientes"
                            class="p-3 bg-[#eff4ff] border border-[#dce9ff] rounded-lg hover:bg-[#e5eeff] transition-colors block text-left"
                        >
                            <span class="material-symbols-outlined text-[#4648d4] text-[22px] mb-1 block">person_add</span>
                            <div class="font-semibold text-xs text-[#0b1c30]">Nuevo Cliente</div>
                            <p class="text-[11px] text-[#5f5e61]">Registro con RNC</p>
                        </RouterLink>

                        <RouterLink
                            to="/reportes"
                            class="p-3 bg-[#eff4ff] border border-[#dce9ff] rounded-lg hover:bg-[#e5eeff] transition-colors block text-left"
                        >
                            <span class="material-symbols-outlined text-[#4648d4] text-[22px] mb-1 block">summarize</span>
                            <div class="font-semibold text-xs text-[#0b1c30]">Cierre de Caja</div>
                            <p class="text-[11px] text-[#5f5e61]">Reporte X / Z</p>
                        </RouterLink>
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-[#e2e8f0] flex justify-between items-center text-xs">
                    <span class="text-[#5f5e61]">Atajos de teclado habilitados en POS (F1-F12)</span>
                    <RouterLink to="/reportes" class="text-[#4648d4] font-semibold hover:underline">
                        Reporte de ventas →
                    </RouterLink>
                </div>
            </div>
        </div>

        <!-- RECENT SALES ACTIVITY TABLE -->
        <div class="bg-white rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e2e8f0] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30]">Ventas y Comprobantes Recientes</h3>
                    <p class="text-xs text-[#5f5e61] mt-0.5">Operaciones cobradas con secuencia NCF</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center bg-[#eff4ff] px-3 py-1.5 rounded-lg border border-[#c7c4d7]/40">
                        <span class="material-symbols-outlined text-[#767586] mr-2 text-[16px]">filter_list</span>
                        <input
                            v-model="tableFilter"
                            type="text"
                            placeholder="Buscar NCF o cliente…"
                            class="bg-transparent border-none outline-none text-xs w-44 text-[#0b1c30] placeholder:text-[#767586]"
                        >
                    </div>
                    <RouterLink
                        to="/reportes"
                        class="bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                    >
                        Ver todas
                    </RouterLink>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#eff4ff] text-[11px] font-semibold text-[#5f5e61] uppercase tracking-wider">
                            <th class="py-3 px-5">NCF</th>
                            <th class="py-3 px-5">Cliente</th>
                            <th class="py-3 px-5">Método de Pago</th>
                            <th class="py-3 px-5 text-right">Monto</th>
                            <th class="py-3 px-5 text-center">Estado</th>
                            <th class="py-3 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e2e8f0] text-xs">
                        <tr
                            v-for="sale in filteredSales"
                            :key="sale.ncf"
                            class="hover:bg-[#f8f9ff] transition-colors"
                        >
                            <td class="py-3 px-5 font-mono text-[#4648d4] font-semibold">{{ sale.ncf }}</td>
                            <td class="py-3 px-5 font-medium text-[#0b1c30]">{{ sale.customer }}</td>
                            <td class="py-3 px-5 text-[#5f5e61]">
                                <div class="flex items-center space-x-1.5">
                                    <span class="material-symbols-outlined text-[16px]">{{ sale.icon }}</span>
                                    <span>{{ sale.paymentMethod }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-5 text-right font-bold text-[#0b1c30]">{{ sale.amount }}</td>
                            <td class="py-3 px-5 text-center">
                                <span class="bg-emerald-100 text-emerald-800 text-[11px] px-2.5 py-0.5 rounded-full font-semibold">
                                    {{ sale.status }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <RouterLink
                                    to="/reportes"
                                    class="p-1.5 rounded-md hover:bg-[#e5eeff] text-[#5f5e61] hover:text-[#4648d4] transition inline-block"
                                    title="Ver comprobante"
                                >
                                    <span class="material-symbols-outlined text-[18px]">receipt</span>
                                </RouterLink>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
