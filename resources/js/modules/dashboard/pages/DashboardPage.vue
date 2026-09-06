<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from '../../auth/stores/session';
import { useModuleStore } from '../../module-manager/stores/modules';

const { t } = useI18n();
const session = useSessionStore();
const modules = useModuleStore();

const operatorName = computed(() => session.user?.name ?? 'Carlos Mendez');
const branchName = computed(() => session.branch?.name ?? 'Santo Domingo #04');

const currentPeriod = ref<'today' | 'week' | 'month'>('today');
const activeDiningTab = ref<'dining' | 'pickup'>('dining');
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
        customer: 'Restaurante El Malecon',
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

onMounted(() => {
    void modules.loadModules();
});
</script>

<template>
    <div class="flex flex-col w-full pb-16">
        <!-- TÍTULO ACCESIBLE PARA E2E Y LECTORES DE PANTALLA -->
        <h1 class="sr-only">{{ t('dashboard.title') }}</h1>

        <!-- SMART GREETING & PERIOD HEADER -->
        <div class="px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="text-[11px] uppercase tracking-wider font-semibold text-[#5f5e61] px-2 py-0.5 rounded bg-[#e5eeff]">
                        {{ t('dashboard.shiftBadge') }}
                    </span>
                    <span class="text-xs text-[#767586]">•</span>
                    <span class="text-xs text-[#5f5e61]">{{ t('dashboard.terminalBadge') }}</span>
                </div>
                <h2 class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] tracking-tight">
                    {{ t('dashboard.greeting', { name: operatorName }) }}
                </h2>
            </div>

            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                <div class="flex bg-[#e5eeff] p-1 rounded-lg">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                        :class="currentPeriod === 'today' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61] hover:text-[#0b1c30]'"
                        @click="currentPeriod = 'today'"
                    >
                        {{ t('common.today') }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                        :class="currentPeriod === 'week' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61] hover:text-[#0b1c30]'"
                        @click="currentPeriod = 'week'"
                    >
                        {{ t('common.thisWeek') }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                        :class="currentPeriod === 'month' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61] hover:text-[#0b1c30]'"
                        @click="currentPeriod = 'month'"
                    >
                        {{ t('common.thisMonth') }}
                    </button>
                </div>

                <RouterLink
                    to="/reportes"
                    class="bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center transition-all shadow-2xs"
                >
                    <span class="material-symbols-outlined mr-1.5 text-[18px]">download</span>
                    <span>{{ t('common.exportReport') }}</span>
                </RouterLink>
            </div>
        </div>

        <!-- OPERATIONAL KPI CARDS -->
        <div class="px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- CARD 1: DAILY SALES -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#4648d4]" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.dailySales') }}</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e1e0ff] flex items-center justify-center text-[#4648d4]">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 48,250.00</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-600 font-medium">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        <span>{{ t('dashboard.vsYesterday') }}</span>
                    </div>
                </div>
            </div>

            <!-- CARD 2: CASH DRAWER -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#595c5e]" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.shiftDrawer') }}</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e5eeff] flex items-center justify-center text-[#595c5e]">
                        <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 5,000.00</div>
                    <div class="flex items-center space-x-1.5 text-xs text-[#5f5e61]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500" />
                        <span>{{ t('dashboard.drawerActive') }}</span>
                    </div>
                </div>
            </div>

            <!-- CARD 3: INVENTORY ALERTS -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.inventoryAlerts') }}</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">{{ t('dashboard.lowStockCount', { count: 3 }) }}</div>
                    <div class="flex items-center space-x-1 text-xs text-amber-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        <span>{{ t('dashboard.lowStockReached') }}</span>
                    </div>
                </div>
            </div>

            <!-- CARD 4: DGII E-CF ISSUED -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-600" />
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.dgiiEcfIssued') }}</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">{{ t('dashboard.dgiiTotal', { count: 142 }) }}</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        <span>{{ t('dashboard.dgiiAccepted') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENTO GRID QUICK OPERATIONS -->
        <div class="px-6 grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- LARGE POS SHORTCUT CARD (2 COLS) -->
            <div class="lg:col-span-2 bg-gradient-to-br from-[#6063ee] to-[#4648d4] text-white p-6 md:p-8 rounded-xl shadow-md flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-x-6 translate-y-6 pointer-events-none">
                    <span class="material-symbols-outlined text-[180px]">point_of_sale</span>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-white/20 text-white px-2.5 py-0.5 rounded-full text-[11px] font-semibold">
                            {{ t('dashboard.highSpeedEngine') }}
                        </span>
                        <span class="text-xs text-[#c0c1ff]">{{ t('dashboard.latency') }}</span>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold font-geist mb-2 tracking-tight">
                        {{ t('dashboard.posTerminalReady') }}
                    </h3>
                    <p class="text-xs md:text-sm text-[#e1e0ff] max-w-lg mb-6 leading-relaxed">
                        {{ t('dashboard.posTerminalDesc') }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <RouterLink
                        to="/pos"
                        class="bg-white text-[#4648d4] px-5 py-2.5 rounded-lg text-xs md:text-sm font-bold hover:bg-[#f8f9ff] transition-all flex items-center shadow-xs active:scale-95"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">bolt</span>
                        <span>{{ t('dashboard.launchPos') }}</span>
                    </RouterLink>
                    <RouterLink
                        to="/pos"
                        class="bg-[#6063ee] text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-black/15 transition-all"
                    >
                        {{ t('dashboard.viewShortcuts') }}
                    </RouterLink>
                </div>
            </div>

            <!-- INVENTORY CAPACITY CARD (1 COL) -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.inventoryCapacity') }}</span>
                        <span class="material-symbols-outlined text-[#5f5e61] text-[18px]">inventory</span>
                    </div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30] mb-0.5">{{ t('dashboard.warehouseStock') }}</h3>
                    <p class="text-xs text-[#5f5e61] mb-4">{{ branchName }}</p>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-xs">
                            <span class="text-[#5f5e61]">{{ t('dashboard.usedSpace') }}</span>
                            <span class="font-semibold text-[#0b1c30]">{{ t('dashboard.skusRatio') }}</span>
                        </div>
                        <div class="w-full h-2 bg-[#e5eeff] rounded-full overflow-hidden">
                            <div class="bg-[#4648d4] h-full rounded-full" style="width: 73.6%;" />
                        </div>
                    </div>
                </div>
                <div class="pt-3 border-t border-[#e2e8f0] flex items-center justify-between">
                    <span class="text-xs text-amber-700 font-medium">{{ t('dashboard.lowStockItems') }}</span>
                    <RouterLink to="/inventario" class="text-xs text-[#4648d4] font-semibold hover:underline">
                        {{ t('dashboard.manageStock') }}
                    </RouterLink>
                </div>
            </div>

            <!-- DGII E-CF SEQUENCES STATUS (1 COL) -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.dgiiStatus') }}</span>
                        <span class="material-symbols-outlined text-emerald-600 text-[18px]">verified</span>
                    </div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30] mb-0.5">{{ t('dashboard.activeSequences') }}</h3>
                    <p class="text-xs text-[#5f5e61] mb-3">{{ t('dashboard.sequencesSub') }}</p>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2.5 bg-[#eff4ff] rounded-lg">
                            <span class="text-xs font-mono font-medium text-[#0b1c30]">E310000000142</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded">
                                {{ t('common.active') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-2.5 bg-[#eff4ff] rounded-lg">
                            <span class="text-xs font-mono font-medium text-[#0b1c30]">E320000000088</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded">
                                {{ t('common.active') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-[#e2e8f0] flex items-center justify-between">
                    <span class="text-xs text-[#5f5e61]">{{ t('dashboard.tokenExpires') }}</span>
                    <RouterLink to="/configuracion/fiscal" class="text-xs text-[#4648d4] font-semibold hover:underline">
                        {{ t('dashboard.syncDgii') }}
                    </RouterLink>
                </div>
            </div>

            <!-- RESTAURANT / QUICK OPERATIONS CARD (2 COLS) -->
            <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">{{ t('dashboard.quickOperations') }}</span>
                        <div class="flex space-x-2">
                            <button
                                type="button"
                                class="text-[11px] px-2.5 py-0.5 rounded font-semibold transition cursor-pointer"
                                :class="activeDiningTab === 'dining' ? 'bg-[#4648d4]/10 text-[#4648d4]' : 'bg-[#e5eeff] text-[#5f5e61]'"
                                @click="activeDiningTab = 'dining'"
                            >
                                {{ t('dashboard.diningRoom') }}
                            </button>
                            <button
                                type="button"
                                class="text-[11px] px-2.5 py-0.5 rounded font-semibold transition cursor-pointer"
                                :class="activeDiningTab === 'pickup' ? 'bg-[#4648d4]/10 text-[#4648d4]' : 'bg-[#e5eeff] text-[#5f5e61]'"
                                @click="activeDiningTab = 'pickup'"
                            >
                                {{ t('dashboard.expressPickup') }}
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <RouterLink
                            to="/restaurant/layout"
                            class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors block"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-xs text-emerald-900">{{ t('dashboard.table01') }}</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-600" />
                            </div>
                            <p class="text-[11px] text-emerald-700">RD$ 2,400 · {{ t('dashboard.guests') }}</p>
                        </RouterLink>

                        <RouterLink
                            to="/restaurant/layout"
                            class="p-3 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors block"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-xs text-amber-900">{{ t('dashboard.table02') }}</span>
                                <span class="w-2 h-2 rounded-full bg-amber-600" />
                            </div>
                            <p class="text-[11px] text-amber-700">RD$ 5,800 · {{ t('dashboard.ordered') }}</p>
                        </RouterLink>

                        <RouterLink
                            to="/restaurant/layout"
                            class="p-3 bg-[#eff4ff] border border-[#dce9ff] rounded-lg hover:bg-[#e5eeff] transition-colors block"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-xs text-[#0b1c30]">{{ t('dashboard.table03') }}</span>
                                <span class="w-2 h-2 rounded-full bg-[#767586]" />
                            </div>
                            <p class="text-[11px] text-[#5f5e61]">{{ t('dashboard.available') }}</p>
                        </RouterLink>

                        <RouterLink
                            to="/restaurant/layout"
                            class="p-3 bg-[#eff4ff] border border-[#dce9ff] rounded-lg hover:bg-[#e5eeff] transition-colors block"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-xs text-[#0b1c30]">{{ t('dashboard.table04') }}</span>
                                <span class="w-2 h-2 rounded-full bg-[#767586]" />
                            </div>
                            <p class="text-[11px] text-[#5f5e61]">{{ t('dashboard.available') }}</p>
                        </RouterLink>
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-[#e2e8f0] flex justify-between items-center text-xs">
                    <span class="text-[#5f5e61]">{{ t('dashboard.activeFloorPlan') }}</span>
                    <RouterLink to="/restaurant/layout" class="text-[#4648d4] font-semibold hover:underline">
                        {{ t('dashboard.viewFloorMaps') }}
                    </RouterLink>
                </div>
            </div>
        </div>

        <!-- RECENT SALES ACTIVITY TABLE -->
        <div class="px-6">
            <div class="bg-white rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] overflow-hidden">
                <div class="px-5 py-4 border-b border-[#e2e8f0] flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold font-geist text-[#0b1c30]">{{ t('dashboard.recentSales') }}</h3>
                        <p class="text-xs text-[#5f5e61] mt-0.5">{{ t('dashboard.recentSalesSub') }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center bg-[#eff4ff] px-3 py-1.5 rounded-lg border border-[#c7c4d7]/40">
                            <span class="material-symbols-outlined text-[#767586] mr-2 text-[16px]">filter_list</span>
                            <input
                                v-model="tableFilter"
                                type="text"
                                :placeholder="t('dashboard.filterPlaceholder')"
                                class="bg-transparent border-none outline-none text-xs w-44 text-[#0b1c30] placeholder:text-[#767586]"
                            >
                        </div>
                        <RouterLink
                            to="/reportes"
                            class="bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        >
                            {{ t('common.viewAll') }}
                        </RouterLink>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#eff4ff] text-[11px] font-semibold text-[#5f5e61] uppercase tracking-wider">
                                <th class="py-3 px-5">{{ t('dashboard.tableHeaders.ncf') }}</th>
                                <th class="py-3 px-5">{{ t('dashboard.tableHeaders.customer') }}</th>
                                <th class="py-3 px-5">{{ t('dashboard.tableHeaders.paymentMethod') }}</th>
                                <th class="py-3 px-5 text-right">{{ t('dashboard.tableHeaders.amount') }}</th>
                                <th class="py-3 px-5 text-center">{{ t('dashboard.tableHeaders.status') }}</th>
                                <th class="py-3 px-5 text-right">{{ t('dashboard.tableHeaders.actions') }}</th>
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
                                        {{ t('common.collected') }}
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

                <div class="px-5 py-3.5 border-t border-[#e2e8f0] flex items-center justify-between text-xs text-[#5f5e61]">
                    <span>{{ t('common.showing', { current: filteredSales.length, total: 142 }) }}</span>
                    <div class="flex items-center space-x-2">
                        <button type="button" class="px-3 py-1 bg-[#e5eeff] rounded text-xs opacity-50 cursor-not-allowed" disabled>
                            {{ t('common.previous') }}
                        </button>
                        <button type="button" class="px-3 py-1 bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] rounded text-xs font-medium transition cursor-pointer">
                            {{ t('common.next') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
