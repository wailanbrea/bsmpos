<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useSessionStore } from '../../auth/stores/session';

const session = useSessionStore();
const branchName = computed(() => session.branch?.name ?? 'Sucursal Principal');

const appointmentFilter = ref('');
const activeFilter = ref<'all' | 'pending' | 'in_progress' | 'completed'>('all');

interface AppointmentSummary {
    id: string;
    time: string;
    customer: string;
    service: string;
    barber: string;
    chair: string;
    amount: string;
    status: 'pending' | 'in_progress' | 'completed';
    statusLabel: string;
}

const appointments: AppointmentSummary[] = [
    {
        id: '1',
        time: '10:00 AM',
        customer: 'Alejandro Peña',
        service: 'Corte Ejecutivo + Barba con Toalla Caliente',
        barber: 'Carlos Mendez',
        chair: 'Sillón 01',
        amount: 'RD$ 950.00',
        status: 'in_progress',
        statusLabel: 'En Servicio',
    },
    {
        id: '2',
        time: '10:30 AM',
        customer: 'Jose Ramon Valenzuela',
        service: 'Skin Fade + Pigmentación de Barba',
        barber: 'Alexander Style',
        chair: 'Sillón 02',
        amount: 'RD$ 1,200.00',
        status: 'in_progress',
        statusLabel: 'En Servicio',
    },
    {
        id: '3',
        time: '11:15 AM',
        customer: 'Manuel Tavarez',
        service: 'Corte Infantil Clásico',
        barber: 'Marcos Fade',
        chair: 'Sillón 03',
        amount: 'RD$ 600.00',
        status: 'pending',
        statusLabel: 'En Espera',
    },
    {
        id: '4',
        time: '09:15 AM',
        customer: 'Victor Hugo Rosario',
        service: 'Corte de Cabello + Mascarilla Negra Facial',
        barber: 'Carlos Mendez',
        chair: 'Sillón 01',
        amount: 'RD$ 1,450.00',
        status: 'completed',
        statusLabel: 'Completado',
    },
    {
        id: '5',
        time: '12:00 PM',
        customer: 'Guillermo Gomez',
        service: 'Afeitado Tradicional con Navaja',
        barber: 'Junior VIP',
        chair: 'Sillón 04',
        amount: 'RD$ 800.00',
        status: 'pending',
        statusLabel: 'Reservada',
    },
];

const filteredAppointments = computed(() => {
    let result = appointments;
    if (activeFilter.value === 'pending') {
        result = result.filter((a) => a.status === 'pending');
    } else if (activeFilter.value === 'in_progress') {
        result = result.filter((a) => a.status === 'in_progress');
    } else if (activeFilter.value === 'completed') {
        result = result.filter((a) => a.status === 'completed');
    }

    const q = appointmentFilter.value.trim().toLowerCase();
    if (!q) return result;
    return result.filter(
        (a) =>
            a.customer.toLowerCase().includes(q) ||
            a.barber.toLowerCase().includes(q) ||
            a.service.toLowerCase().includes(q),
    );
});
</script>

<template>
    <div class="space-y-6">
        <!-- OPERATIONAL KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- APPOINTMENTS TODAY -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#4648d4]" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Citas de Hoy</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e1e0ff] flex items-center justify-center text-[#4648d4]">
                        <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">14 Citas</div>
                    <div class="flex items-center space-x-1 text-xs text-blue-600 font-medium">
                        <span class="material-symbols-outlined text-[14px]">event_available</span>
                        <span>5 pendientes de turno</span>
                    </div>
                </div>
            </div>

            <!-- CLIENTS SERVED -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-600" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Clientes Atendidos</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">9 Atendidos</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1" />
                        <span>2 actualmente en sillón</span>
                    </div>
                </div>
            </div>

            <!-- STAFF ON DUTY -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-500" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Barberos Activos</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-[18px]">badge</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">4 en Turno</div>
                    <div class="flex items-center space-x-1 text-xs text-amber-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">timer</span>
                        <span>Promedio servicio: 35 min</span>
                    </div>
                </div>
            </div>

            <!-- SALES TODAY -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-[#595c5e]" />
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Ingresos Hoy</span>
                    <div class="w-8 h-8 rounded-lg bg-[#e5eeff] flex items-center justify-center text-[#595c5e]">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] mb-1">RD$ 18,200.00</div>
                    <div class="flex items-center space-x-1 text-xs text-emerald-700 font-medium">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        <span>Servicios + Productos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENTO ROW: BARBER HERO + CHAIRS STATUS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- BARBER HERO -->
            <div class="lg:col-span-2 bg-gradient-to-br from-[#27272a] to-[#09090b] text-white p-6 md:p-7 rounded-xl shadow-md flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4 pointer-events-none">
                    <span class="material-symbols-outlined text-[170px]">content_cut</span>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="bg-white/20 text-white px-2.5 py-0.5 rounded-full text-[11px] font-semibold">
                            Gestión de Barbería & Salón
                        </span>
                        <span class="text-xs text-zinc-400">{{ branchName }}</span>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold font-geist mb-2 tracking-tight">
                        Agenda, Citas & Comisiones
                    </h3>
                    <p class="text-xs md:text-sm text-zinc-300 max-w-lg mb-6 leading-relaxed">
                        Control de turnos en tiempo real, asignación por profesional, venta de productos para el cabello y cobro express de servicios.
                    </p>
                </div>
                <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                    <RouterLink
                        to="/agenda"
                        class="bg-[#4648d4] hover:bg-[#3b3dbb] text-white px-5 py-2.5 rounded-lg text-xs md:text-sm font-bold transition-all flex items-center shadow-xs active:scale-95"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">calendar_month</span>
                        <span>Ver Agenda Completa</span>
                    </RouterLink>
                    <RouterLink
                        to="/pos"
                        class="bg-white text-zinc-900 px-4 py-2.5 rounded-lg text-xs md:text-sm font-bold hover:bg-zinc-100 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">point_of_sale</span>
                        <span>Cobro Express POS</span>
                    </RouterLink>
                    <RouterLink
                        to="/empleados"
                        class="bg-white/10 text-white px-4 py-2.5 rounded-lg text-xs md:text-sm font-semibold hover:bg-white/20 transition-all flex items-center"
                    >
                        <span class="material-symbols-outlined mr-1.5 text-[18px]">badge</span>
                        <span>Barberos / Personal</span>
                    </RouterLink>
                </div>
            </div>

            <!-- CHAIRS & STATIONS LIVE STATUS -->
            <div class="bg-white p-5 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Estado de Sillones</span>
                        <span class="text-xs text-emerald-700 font-semibold">2 / 4 Ocupados</span>
                    </div>
                    <div class="space-y-2.5">
                        <div class="p-2.5 rounded-lg border bg-amber-50/70 border-amber-200 text-xs">
                            <div class="flex justify-between items-center font-bold text-[#0b1c30]">
                                <span>Sillón 1 · Carlos Mendez</span>
                                <span class="text-amber-800 font-semibold text-[11px]">En Servicio</span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] mt-0.5">Alejandro P. · Corte Ejecutivo (20 min)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border bg-amber-50/70 border-amber-200 text-xs">
                            <div class="flex justify-between items-center font-bold text-[#0b1c30]">
                                <span>Sillón 2 · Alexander Style</span>
                                <span class="text-amber-800 font-semibold text-[11px]">En Servicio</span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] mt-0.5">Jose Ramon · Skin Fade (10 min)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border bg-emerald-50/70 border-emerald-200 text-xs">
                            <div class="flex justify-between items-center font-bold text-[#0b1c30]">
                                <span>Sillón 3 · Marcos Fade</span>
                                <span class="text-emerald-800 font-semibold text-[11px]">Disponible</span>
                            </div>
                            <p class="text-[11px] text-[#5f5e61] mt-0.5">Próxima cita a las 11:15 AM</p>
                        </div>
                    </div>
                </div>
                <div class="pt-3 border-t border-[#e2e8f0] mt-3 flex items-center justify-between text-xs">
                    <span class="text-[#5f5e61]">Sillón 4 libre</span>
                    <RouterLink to="/agenda" class="text-[#4648d4] font-semibold hover:underline">
                        Asignar turno →
                    </RouterLink>
                </div>
            </div>
        </div>

        <!-- TODAY'S APPOINTMENTS TABLE -->
        <div class="bg-white rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] border border-[#e2e8f0] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e2e8f0] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30]">Citas y Turnos de Hoy</h3>
                    <p class="text-xs text-[#5f5e61] mt-0.5">Control cronológico de clientes y servicios agendados</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex bg-[#e5eeff] p-1 rounded-lg text-xs">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded font-semibold transition-colors cursor-pointer"
                            :class="activeFilter === 'all' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeFilter = 'all'"
                        >
                            Todas
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded font-semibold transition-colors cursor-pointer"
                            :class="activeFilter === 'in_progress' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeFilter = 'in_progress'"
                        >
                            En Servicio
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded font-semibold transition-colors cursor-pointer"
                            :class="activeFilter === 'pending' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61]'"
                            @click="activeFilter = 'pending'"
                        >
                            En Espera
                        </button>
                    </div>
                    <div class="flex items-center bg-[#eff4ff] px-3 py-1.5 rounded-lg border border-[#c7c4d7]/40">
                        <span class="material-symbols-outlined text-[#767586] mr-2 text-[16px]">filter_list</span>
                        <input
                            v-model="appointmentFilter"
                            type="text"
                            placeholder="Buscar cliente o barbero…"
                            class="bg-transparent border-none outline-none text-xs w-44 text-[#0b1c30] placeholder:text-[#767586]"
                        >
                    </div>
                    <RouterLink
                        to="/agenda"
                        class="bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                    >
                        Ver agenda
                    </RouterLink>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#eff4ff] text-[11px] font-semibold text-[#5f5e61] uppercase tracking-wider">
                            <th class="py-3 px-5">Hora</th>
                            <th class="py-3 px-5">Cliente</th>
                            <th class="py-3 px-5">Servicio</th>
                            <th class="py-3 px-5">Profesional / Sillón</th>
                            <th class="py-3 px-5 text-right">Precio</th>
                            <th class="py-3 px-5 text-center">Estado</th>
                            <th class="py-3 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e2e8f0] text-xs">
                        <tr v-for="app in filteredAppointments" :key="app.id" class="hover:bg-[#f8f9ff] transition-colors">
                            <td class="py-3 px-5 font-mono font-bold text-[#4648d4]">{{ app.time }}</td>
                            <td class="py-3 px-5 font-semibold text-[#0b1c30]">{{ app.customer }}</td>
                            <td class="py-3 px-5 text-[#5f5e61]">{{ app.service }}</td>
                            <td class="py-3 px-5">
                                <div class="font-medium text-[#0b1c30]">{{ app.barber }}</div>
                                <span class="text-[10px] text-[#767586]">{{ app.chair }}</span>
                            </td>
                            <td class="py-3 px-5 text-right font-bold text-[#0b1c30]">{{ app.amount }}</td>
                            <td class="py-3 px-5 text-center">
                                <span
                                    class="text-[11px] px-2.5 py-0.5 rounded-full font-semibold"
                                    :class="{
                                        'bg-amber-100 text-amber-800': app.status === 'in_progress',
                                        'bg-blue-100 text-blue-800': app.status === 'pending',
                                        'bg-emerald-100 text-emerald-800': app.status === 'completed',
                                    }"
                                >
                                    {{ app.statusLabel }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <RouterLink
                                    to="/agenda"
                                    class="p-1.5 rounded-md hover:bg-[#e5eeff] text-[#5f5e61] hover:text-[#4648d4] transition inline-block"
                                    title="Ver detalle"
                                >
                                    <span class="material-symbols-outlined text-[18px]">edit_calendar</span>
                                </RouterLink>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
