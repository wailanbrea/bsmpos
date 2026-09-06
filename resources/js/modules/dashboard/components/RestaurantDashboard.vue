<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useSessionStore } from '../../auth/stores/session';

const session = useSessionStore();
const branchName = computed(() => session.branch?.name ?? 'Sucursal Principal');

const activeDiningTab = ref<'dining' | 'pickup'>('dining');
const tableFilter = ref('');

interface TableItem {
    id: string;
    number: string;
    status: 'occupied' | 'ordered' | 'available' | 'reserved';
    guests: number;
    amount: string;
    waiter: string;
}

const tables: TableItem[] = [
    { id: '1', number: 'Mesa 01', status: 'occupied', guests: 4, amount: 'RD$ 3,450.00', waiter: 'Juan P.' },
    { id: '2', number: 'Mesa 02', status: 'ordered', guests: 2, amount: 'RD$ 1,820.00', waiter: 'Carlos M.' },
    { id: '3', number: 'Mesa 03', status: 'available', guests: 0, amount: 'RD$ 0.00', waiter: '-' },
    { id: '4', number: 'Mesa 04', status: 'available', guests: 0, amount: 'RD$ 0.00', waiter: '-' },
    { id: '5', number: 'Mesa 05', status: 'occupied', guests: 6, amount: 'RD$ 5,900.00', waiter: 'Ana S.' },
    { id: '6', number: 'Mesa 06', status: 'reserved', guests: 3, amount: 'RD$ 0.00', waiter: 'Carlos M.' },
];

interface KitchenOrder {
    ticket: string;
    table: string;
    elapsed: string;
    items: string[];
    priority: 'high' | 'normal';
}

const activeOrders: KitchenOrder[] = [
    { ticket: '#104', table: 'Mesa 01', elapsed: '8 min', items: ['2x Mofongo de Chicharrón', '1x Pechuga a la Plancha'], priority: 'normal' },
    { ticket: '#105', table: 'Mesa 05', elapsed: '14 min', items: ['3x Chillo Frito', '1x Tostones', '2x Cerveza Presidente'], priority: 'high' },
    { ticket: '#106', table: 'Pickup #12', elapsed: '4 min', items: ['1x Hamburguesa Clásica', '1x Papas Fritas'], priority: 'normal' },
];

interface RecentSale {
    ncf: string;
    customer: string;
    table: string;
    paymentMethod: string;
    icon: string;
    amount: string;
    status: string;
}

const recentSales: RecentSale[] = [
    { ncf: 'E310000000142', customer: 'Consumidor Final', table: 'Mesa 04', paymentMethod: 'Tarjeta de Crédito', icon: 'credit_card', amount: 'RD$ 3,450.00', status: 'Cobrado' },
    { ncf: 'E310000000141', customer: 'Maria Almonte', table: 'Para Llevar', paymentMethod: 'Efectivo (RD$)', icon: 'payments', amount: 'RD$ 1,250.00', status: 'Cobrado' },
    { ncf: 'E310000000140', customer: 'Restaurante El Malecon', table: 'Mesa 02', paymentMethod: 'Tarjeta de Débito', icon: 'credit_card', amount: 'RD$ 8,900.00', status: 'Cobrado' },
    { ncf: 'E310000000139', customer: 'Pedro Morales', table: 'Mesa 01', paymentMethod: 'Transferencia Bancaria', icon: 'account_balance', amount: 'RD$ 4,120.00', status: 'Cobrado' },
];

const filteredSales = computed(() => {
    const q = tableFilter.value.trim().toLowerCase();
    if (!q) return recentSales;
    return recentSales.filter((s) => s.ncf.toLowerCase().includes(q) || s.customer.toLowerCase().includes(q) || s.table.toLowerCase().includes(q));
});
</script>

<template>
    <div class="space-y-6">
        <!-- OPERATIONAL KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- DAILY SALES -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#4648d4]" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Ventas de Turno</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e1e0ff] flex items-center justify-center text-[#4648d4]">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 48,250.00</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-600 font-medium">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        <span>+12.4% vs ayer</span>
                    </div>
                </div>
            </div>

            <!-- ACTIVE TABLES -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-600" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Mesas Activas</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">table_restaurant</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">4 / 6 Mesas</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1" />
                        <span>15 Comensales en salón</span>
                    </div>
                </div>
            </div>

            <!-- KITCHEN KDS -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Comandas en Cocina</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-[18px]">skillet</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">3 Comandas</div>
                    <div class="flex items-center space-x-1 text-xs text-amber-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">timer</span>
                        <span>Promedio: 11 min</span>
                    </div>
                </div>
            </div>

            <!-- DGII E-CF -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#595c5e]" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">e-CF Emitidos (DGII)</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e5eeff] flex items-center justify-center text-[#595c5e]">
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

        <!-- BENTO ROW: RESTAURANT POS HERO + KITCHEN OVERVIEW -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- POS RESTAURANT HERO -->
            <div class="lg:col-span-2 bg-gradient-to-br from-[#4648d4] to-[#313399] text-white p-6 md:p-7 rounded-xl shadow-md flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4 pointer-events-none">
                    <span class="material-symbols-outlined text-[170px]">restaurant</span>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="bg-white/20 text-white px-2.5 py-0.5 rounded-full text-[11px] font-semibold">
                            Comandas y Cuentas Rápidas
                        </span>
                        <span class="text-xs text-[#c0c1ff]">{{ branchName }}</span>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold font-geist mb-2 tracking-tight">
                        Punto de Venta Restaurante
                    </h3>
                    <p class="text-xs md:text-sm text-[#e1e0ff] max-w-lg mb-6 leading-relaxed">
                        Control de mesas por área, cuentas divididas, comandas de cocina y facturación e-CF fiscal en un solo toque.
                    </p>
                </div>
                <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                    <RouterLink
                        to="/pos"
                        class="bg-white text-[#4648d4] px-5 py-2.5 rounded-lg text-xs md:text-sm font-bold hover:bg-[#f8f9ff] transition-all flex items-center shadow-xs active:scale-95"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">bolt</span>
                        <span>Abrir POS Restaurante</span>
                    </RouterLink>
                    <RouterLink
                        to="/restaurant/layout"
                        class="bg-[#6063ee] text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-black/15 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">table_restaurant</span>
                        <span>Plano de Mesas</span>
                    </RouterLink>
                    <RouterLink
                        to="/kitchen/kds"
                        class="bg-white/10 text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-white/20 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">skillet</span>
                        <span>Cocina KDS</span>
                    </RouterLink>
                </div>
            </div>

            <!-- KITCHEN LIVE TICKETS CARD -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Cocina en Vivo</span>
                        <RouterLink to="/kitchen/kds" class="text-xs text-[#4648d4] font-semibold hover:underline flex items-center">
                            Ver KDS <span class="material-symbols-outlined text-[14px] ml-0.5">arrow_forward</span>
                        </RouterLink>
                    </div>
                    <div class="space-y-2.5">
                        <div
                            v-for="order in activeOrders"
                            :key="order.ticket"
                            class="p-2.5 rounded-lg border text-xs"
                            :class="order.priority === 'high' ? 'bg-amber-50/70 border-amber-200' : 'bg-[#eff4ff] border-[#dce9ff]'"
                        >
                            <div class="flex items-center justify-between font-semibold mb-1">
                                <span class="text-[#0b1c30]">{{ order.ticket }} · {{ order.table }}</span>
                                <span
                                    class="text-[10px] px-1.5 py-0.5 rounded font-bold"
                                    :class="order.priority === 'high' ? 'bg-amber-200 text-amber-900' : 'bg-blue-100 text-blue-800'"
                                >
                                    {{ order.elapsed }}
                                </span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] line-clamp-1">{{ order.items.join(', ') }}</p>
                        </div>
                    </div>
                </div>
                <div class="pt-3 border-t border-[#e2e8f0] mt-3 flex items-center justify-between text-xs text-[#5f5e61]">
                    <span>3 órdenes en cola</span>
                    <span class="text-emerald-700 font-medium">Cocina al día</span>
                </div>
            </div>
        </div>

        <!-- RESTAURANT FLOOR TABLES STATUS -->
        <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0]">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30]">Estado del Salón y Mesas</h3>
                    <p class="text-xs text-[#5f5e61]">Monitor en tiempo real de ocupación y cuentas por mesa</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex bg-[#e5eeff] p-1 rounded-lg">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                            :class="activeDiningTab === 'dining' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeDiningTab = 'dining'"
                        >
                            Salón Principal
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                            :class="activeDiningTab === 'pickup' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeDiningTab = 'pickup'"
                        >
                            Para Llevar / Delivery
                        </button>
                    </div>
                    <RouterLink
                        to="/restaurant/layout"
                        class="bg-[#4648d4] text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold hover:bg-[#3b3dbb] transition flex items-center"
                    >
                        <span class="material-symbols-outlined text-[16px] mr-1">grid_view</span>
                        Gestionar Plano
                    </RouterLink>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <RouterLink
                    v-for="tbl in tables"
                    :key="tbl.id"
                    to="/restaurant/layout"
                    class="p-3.5 rounded-xl border transition-all hover:scale-[1.02] block text-left"
                    :class="{
                        'bg-emerald-50 border-emerald-300': tbl.status === 'occupied',
                        'bg-amber-50 border-amber-300': tbl.status === 'ordered',
                        'bg-[#f8f9ff] border-[#e2e8f0] hover:bg-[#e5eeff]': tbl.status === 'available',
                        'bg-purple-50 border-purple-200': tbl.status === 'reserved',
                    }"
                >
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-bold text-xs text-[#0b1c30]">{{ tbl.number }}</span>
                        <span
                            class="w-2.5 h-2.5 rounded-full"
                            :class="{
                                'bg-emerald-600': tbl.status === 'occupied',
                                'bg-amber-500': tbl.status === 'ordered',
                                'bg-[#767586]': tbl.status === 'available',
                                'bg-purple-500': tbl.status === 'reserved',
                            }"
                        />
                    </div>
                    <p class="text-[11px] font-semibold" :class="tbl.amount !== 'RD$ 0.00' ? 'text-emerald-800' : 'text-[#767586]'">
                        {{ tbl.amount }}
                    </p>
                    <p class="text-[10px] text-[#5f5e61] mt-1">
                        <span v-if="tbl.guests > 0">{{ tbl.guests }} personas · {{ tbl.waiter }}</span>
                        <span v-else-if="tbl.status === 'reserved'">Reservada</span>
                        <span v-else>Disponible</span>
                    </p>
                </RouterLink>
            </div>
        </div>

        <!-- RECENT RESTAURANT SALES ACTIVITY -->
        <div class="bg-white rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e2e8f0] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30]">Cuentas y Cobros Recientes</h3>
                    <p class="text-xs text-[#5f5e61] mt-0.5">Tickets cerrados con comprobante fiscal</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center bg-[#eff4ff] px-3 py-1.5 rounded-lg border border-[#c7c4d7]/40">
                        <span class="material-symbols-outlined text-[#767586] mr-2 text-[16px]">filter_list</span>
                        <input
                            v-model="tableFilter"
                            type="text"
                            placeholder="Buscar NCF, mesa o cliente…"
                            class="bg-transparent border-none outline-none text-xs w-48 text-[#0b1c30] placeholder:text-[#767586]"
                        >
                    </div>
                    <RouterLink
                        to="/reportes"
                        class="bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                    >
                        Ver todos
                    </RouterLink>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#eff4ff] text-[11px] font-semibold text-[#5f5e61] uppercase tracking-wider">
                            <th class="py-3 px-5">NCF</th>
                            <th class="py-3 px-5">Mesa / Origen</th>
                            <th class="py-3 px-5">Cliente</th>
                            <th class="py-3 px-5">Método de Pago</th>
                            <th class="py-3 px-5 text-right">Monto</th>
                            <th class="py-3 px-5 text-center">Estado</th>
                            <th class="py-3 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e2e8f0] text-xs">
                        <tr v-for="sale in filteredSales" :key="sale.ncf" class="hover:bg-[#f8f9ff] transition-colors">
                            <td class="py-3 px-5 font-mono text-[#4648d4] font-semibold">{{ sale.ncf }}</td>
                            <td class="py-3 px-5 font-semibold text-[#0b1c30]">{{ sale.table }}</td>
                            <td class="py-3 px-5 font-medium text-[#5f5e61]">{{ sale.customer }}</td>
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
