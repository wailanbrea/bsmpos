<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useSessionStore } from '../../auth/stores/session';

const session = useSessionStore();
const branchName = computed(() => session.branch?.name ?? 'Sucursal Principal');

const orderFilter = ref('');
const activeStage = ref<'all' | 'reception' | 'repair' | 'ready'>('all');

interface WorkOrderSummary {
    id: string;
    orderNumber: string;
    vehicle: string;
    plate: string;
    customer: string;
    service: string;
    technician: string;
    status: 'reception' | 'diagnosing' | 'in_progress' | 'ready';
    statusLabel: string;
    amount: string;
    estimatedDelivery: string;
}

const workOrders: WorkOrderSummary[] = [
    {
        id: '1',
        orderNumber: 'OT-2026-089',
        vehicle: 'Toyota Hilux 2021',
        plate: 'A923145',
        customer: 'Agropecuaria del Norte SRL',
        service: 'Reparación de Transmisión 4x4',
        technician: 'Manuel Rodriguez',
        status: 'in_progress',
        statusLabel: 'En Reparación',
        amount: 'RD$ 34,500.00',
        estimatedDelivery: 'Hoy, 4:00 PM',
    },
    {
        id: '2',
        orderNumber: 'OT-2026-090',
        vehicle: 'Honda Civic 2019',
        plate: 'A842102',
        customer: 'Laura Gomez',
        service: 'Mantenimiento 40k y Frenos',
        technician: 'Roberto Castillo',
        status: 'ready',
        statusLabel: 'Listo para Entrega',
        amount: 'RD$ 8,750.00',
        estimatedDelivery: 'Listo',
    },
    {
        id: '3',
        orderNumber: 'OT-2026-091',
        vehicle: 'Hyundai Tucson 2022',
        plate: 'A771920',
        customer: 'Carlos Mendez',
        service: 'Diagnóstico Electrónico Check Engine',
        technician: 'Marcos Peña',
        status: 'diagnosing',
        statusLabel: 'En Diagnóstico',
        amount: 'RD$ 2,500.00',
        estimatedDelivery: 'Mañana, 11:00 AM',
    },
    {
        id: '4',
        orderNumber: 'OT-2026-092',
        vehicle: 'Kia Picanto 2020',
        plate: 'A652199',
        customer: 'Consorcio Brea',
        service: 'Cambio de Aceite Sintético y Filtros',
        technician: 'Roberto Castillo',
        status: 'ready',
        statusLabel: 'Listo para Entrega',
        amount: 'RD$ 4,200.00',
        estimatedDelivery: 'Listo',
    },
    {
        id: '5',
        orderNumber: 'OT-2026-093',
        vehicle: 'Ford Explorer 2018',
        plate: 'A512849',
        customer: 'David Santana',
        service: 'Cambio de Suspensión Delantera',
        technician: 'Manuel Rodriguez',
        status: 'in_progress',
        statusLabel: 'En Reparación',
        amount: 'RD$ 19,800.00',
        estimatedDelivery: 'Mañana, 3:00 PM',
    },
];

const filteredOrders = computed(() => {
    let result = workOrders;
    if (activeStage.value === 'reception') {
        result = result.filter((o) => o.status === 'diagnosing');
    } else if (activeStage.value === 'repair') {
        result = result.filter((o) => o.status === 'in_progress');
    } else if (activeStage.value === 'ready') {
        result = result.filter((o) => o.status === 'ready');
    }

    const q = orderFilter.value.trim().toLowerCase();
    if (!q) return result;
    return result.filter(
        (o) =>
            o.orderNumber.toLowerCase().includes(q) ||
            o.vehicle.toLowerCase().includes(q) ||
            o.plate.toLowerCase().includes(q) ||
            o.customer.toLowerCase().includes(q),
    );
});
</script>

<template>
    <div class="space-y-6">
        <!-- OPERATIONAL KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- VEHICLES IN WORKSHOP -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#4648d4]" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Vehículos en Taller</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e1e0ff] flex items-center justify-center text-[#4648d4]">
                        <span class="material-symbols-outlined text-[18px]">directions_car</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">8 Activos</div>
                    <div class="flex items-center space-x-1 text-xs text-blue-600 font-medium">
                        <span class="material-symbols-outlined text-[14px]">engineering</span>
                        <span>5 bahías ocupadas</span>
                    </div>
                </div>
            </div>

            <!-- ACTIVE WORK ORDERS -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Órdenes en Proceso</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-[18px]">build</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">5 Órdenes</div>
                    <div class="flex items-center space-x-1 text-xs text-amber-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">timelapse</span>
                        <span>2 pendientes de repuestos</span>
                    </div>
                </div>
            </div>

            <!-- READY FOR DELIVERY -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-600" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Listos para Entrega</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">task_alt</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">2 Vehículos</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1" />
                        <span>Notificación de entrega enviada</span>
                    </div>
                </div>
            </div>

            <!-- TOTAL BILLING / SERVICES -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#595c5e]" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Facturado Mes</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e5eeff] flex items-center justify-center text-[#595c5e]">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 86,400.00</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">verified</span>
                        <span>Mano de obra + Repuestos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENTO ROW: WORKSHOP ACTION HERO + SERVICE BAYS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- WORKSHOP ACTION HERO -->
            <div class="lg:col-span-2 bg-gradient-to-br from-[#1e293b] to-[#0f172a] text-white p-6 md:p-7 rounded-xl shadow-md flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4 pointer-events-none">
                    <span class="material-symbols-outlined text-[170px]">car_repair</span>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="bg-white/20 text-white px-2.5 py-0.5 rounded-full text-[11px] font-semibold">
                            Control Operativo de Taller Mecánico
                        </span>
                        <span class="text-xs text-slate-400">{{ branchName }}</span>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold font-geist mb-2 tracking-tight">
                        Gestión de Taller & Reparaciones
                    </h3>
                    <p class="text-xs md:text-sm text-slate-300 max-w-lg mb-6 leading-relaxed">
                        Recepción de vehículos con kilometraje y fotos, asignación de técnicos a bahías, control de repuestos utilizados y facturación con NCF/e-CF.
                    </p>
                </div>
                <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                    <RouterLink
                        to="/ordenes-trabajo"
                        class="bg-[#4648d4] hover:bg-[#3b3dbb] text-white px-5 py-2.5 rounded-lg text-xs md:text-sm font-bold transition-all flex items-center shadow-xs active:scale-95"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">add_circle</span>
                        <span>Nueva Orden de Trabajo</span>
                    </RouterLink>
                    <RouterLink
                        to="/vehiculos"
                        class="bg-white/15 text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-white/25 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">directions_car</span>
                        <span>Directorio de Vehículos</span>
                    </RouterLink>
                    <RouterLink
                        to="/pos"
                        class="bg-white/10 text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-white/20 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">point_of_sale</span>
                        <span>Cobro Rápido POS</span>
                    </RouterLink>
                </div>
            </div>

            <!-- SERVICE BAYS QUICK STATUS -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Bahías de Servicio</span>
                        <span class="text-xs text-emerald-700 font-semibold">4 / 5 Ocupadas</span>
                    </div>
                    <div class="space-y-2.5">
                        <div class="p-2.5 rounded-lg border bg-[#f8f9ff] border-[#e2e8f0] text-xs">
                            <div class="flex justify-between items-center font-bold text-[#0b1c30]">
                                <span>Bahía 1 · Elevador Hidráulico</span>
                                <span class="text-amber-700 font-semibold text-[11px]">En Proceso</span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] mt-0.5">Toyota Hilux (A923145) · Manuel R.</p>
                        </div>
                        <div class="p-2.5 rounded-lg border bg-[#f8f9ff] border-[#e2e8f0] text-xs">
                            <div class="flex justify-between items-center font-bold text-[#0b1c30]">
                                <span>Bahía 2 · Diagnóstico y Frenos</span>
                                <span class="text-emerald-700 font-semibold text-[11px]">Listo</span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] mt-0.5">Honda Civic (A842102) · Roberto C.</p>
                        </div>
                        <div class="p-2.5 rounded-lg border bg-[#f8f9ff] border-[#e2e8f0] text-xs">
                            <div class="flex justify-between items-center font-bold text-[#0b1c30]">
                                <span>Bahía 3 · Scanner y Eléctrico</span>
                                <span class="text-blue-700 font-semibold text-[11px]">Diagnóstico</span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] mt-0.5">Hyundai Tucson (A771920) · Marcos P.</p>
                        </div>
                    </div>
                </div>
                <div class="pt-3 border-t border-[#e2e8f0] mt-3 flex items-center justify-between text-xs">
                    <span class="text-[#5f5e61]">Bahía 4 disponible</span>
                    <RouterLink to="/ordenes-trabajo" class="text-[#4648d4] font-semibold hover:underline">
                        Asignar vehículo →
                    </RouterLink>
                </div>
            </div>
        </div>

        <!-- ACTIVE WORK ORDERS PIPELINE & TABLE -->
        <div class="bg-white rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e2e8f0] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30]">Órdenes de Trabajo Activas</h3>
                    <p class="text-xs text-[#5f5e61] mt-0.5">Seguimiento de vehículos en taller y servicios en curso</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex bg-[#e5eeff] p-1 rounded-lg text-xs">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded font-semibold transition-colors cursor-pointer"
                            :class="activeStage === 'all' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeStage = 'all'"
                        >
                            Todas
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded font-semibold transition-colors cursor-pointer"
                            :class="activeStage === 'repair' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeStage = 'repair'"
                        >
                            En Reparación
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded font-semibold transition-colors cursor-pointer"
                            :class="activeStage === 'ready' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeStage = 'ready'"
                        >
                            Listos
                        </button>
                    </div>
                    <div class="flex items-center bg-[#eff4ff] px-3 py-1.5 rounded-lg border border-[#c7c4d7]/40">
                        <span class="material-symbols-outlined text-[#767586] mr-2 text-[16px]">filter_list</span>
                        <input
                            v-model="orderFilter"
                            type="text"
                            placeholder="Buscar orden, placa, cliente…"
                            class="bg-transparent border-none outline-none text-xs w-48 text-[#0b1c30] placeholder:text-[#767586]"
                        >
                    </div>
                    <RouterLink
                        to="/ordenes-trabajo"
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
                            <th class="py-3 px-5">No. Orden</th>
                            <th class="py-3 px-5">Vehículo / Placa</th>
                            <th class="py-3 px-5">Cliente</th>
                            <th class="py-3 px-5">Servicio Principal</th>
                            <th class="py-3 px-5">Técnico Asignado</th>
                            <th class="py-3 px-5 text-right">Monto Estimado</th>
                            <th class="py-3 px-5 text-center">Estado</th>
                            <th class="py-3 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e2e8f0] text-xs">
                        <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-[#f8f9ff] transition-colors">
                            <td class="py-3 px-5 font-mono text-[#4648d4] font-bold">{{ order.orderNumber }}</td>
                            <td class="py-3 px-5">
                                <div class="font-semibold text-[#0b1c30]">{{ order.vehicle }}</div>
                                <span class="inline-block px-1.5 py-0.5 bg-zinc-200 text-zinc-800 rounded text-[10px] font-mono font-semibold">
                                    {{ order.plate }}
                                </span>
                            </td>
                            <td class="py-3 px-5 font-medium text-[#5f5e61]">{{ order.customer }}</td>
                            <td class="py-3 px-5 text-[#0b1c30]">{{ order.service }}</td>
                            <td class="py-3 px-5 text-[#5f5e61]">
                                <div class="flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-[16px] text-zinc-400">badge</span>
                                    <span>{{ order.technician }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-5 text-right font-bold text-[#0b1c30]">{{ order.amount }}</td>
                            <td class="py-3 px-5 text-center">
                                <span
                                    class="text-[11px] px-2.5 py-0.5 rounded-full font-semibold"
                                    :class="{
                                        'bg-amber-100 text-amber-800': order.status === 'in_progress',
                                        'bg-blue-100 text-blue-800': order.status === 'diagnosing',
                                        'bg-emerald-100 text-emerald-800': order.status === 'ready',
                                    }"
                                >
                                    {{ order.statusLabel }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <RouterLink
                                    to="/ordenes-trabajo"
                                    class="p-1.5 rounded-md hover:bg-[#e5eeff] text-[#5f5e61] hover:text-[#4648d4] transition inline-block"
                                    title="Ver orden"
                                >
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </RouterLink>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
