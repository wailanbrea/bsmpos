<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { fetchEmployees } from '../../employees/services';
import type { Employee } from '../../employees/types';
import { setExternalOrderBridge } from '../../pos/services';
import {
    createAppointment,
    fetchAppointments,
    fetchCustomerOptions,
    fetchServiceOptions,
    updateAppointmentStatus,
} from '../services';
import type { Appointment, AppointmentForm, AppointmentStatus, CustomerOption, ServiceOption } from '../types';

const STATUS_LABELS: Record<AppointmentStatus, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_proceso: 'En proceso',
    completada: 'Completada',
    cancelada: 'Cancelada',
    no_asistio: 'No asistió',
};

// Espejo de las transiciones válidas del backend (Appointment::TRANSITIONS).
const TRANSITIONS: Record<AppointmentStatus, AppointmentStatus[]> = {
    pendiente: ['confirmada', 'en_proceso', 'cancelada', 'no_asistio'],
    confirmada: ['en_proceso', 'cancelada', 'no_asistio'],
    en_proceso: ['completada', 'cancelada'],
    completada: [],
    cancelada: [],
    no_asistio: [],
};

const appointments = ref<Appointment[]>([]);
const employees = ref<Employee[]>([]);
const services = ref<ServiceOption[]>([]);
const customers = ref<CustomerOption[]>([]);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

const filterDate = ref(today());
const filterEmployee = ref('');
const form = ref<AppointmentForm>(emptyForm());

function emptyForm(): AppointmentForm {
    return { customer_id: '', employee_id: '', scheduled_at: `${today()}T09:00`, notes: '', service_ids: [] };
}

const formTotal = computed(() =>
    services.value
        .filter((service) => form.value.service_ids.includes(service.id))
        .reduce((sum, service) => sum + Number(service.price), 0),
);

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function loadAgenda(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        appointments.value = await fetchAppointments({
            date: filterDate.value,
            employeeId: filterEmployee.value || undefined,
        });
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function save(): Promise<void> {
    saving.value = true;
    error.value = null;
    try {
        await createAppointment(form.value);
        form.value = emptyForm();
        await loadAgenda();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

async function changeStatus(appointment: Appointment, status: AppointmentStatus): Promise<void> {
    error.value = null;
    try {
        const updated = await updateAppointmentStatus(appointment.id, status);
        const index = appointments.value.findIndex((item) => item.id === appointment.id);
        if (index !== -1) appointments.value[index] = { ...appointments.value[index], status: updated.status };
    } catch (exception) {
        error.value = message(exception);
    }
}

function time(iso: string): string {
    return new Date(iso).toLocaleTimeString('es-DO', { hour: '2-digit', minute: '2-digit' });
}

const router = useRouter();

function billInPos(appointment: Appointment): void {
    const items = appointment.services.map((s) => ({
        product_id: String(s.service_id),
        product_name: s.name,
        quantity: 1,
        price: Number(s.price),
        discount: 0,
        tax_id: null,
        tax_rate: Number(s.tax_rate) || 18,
    }));

    setExternalOrderBridge({
        source: 'appointment',
        reference_id: appointment.id,
        customer_id: appointment.customer_id,
        customer_name: appointment.customer_name,
        notes: `Cita Barbería #${appointment.id.slice(-6)} · ${appointment.employee_name || 'Sin empleado'} (${time(appointment.scheduled_at)})`,
        items,
    });

    void router.push({ path: '/pos', query: { source: 'appointment', ref: appointment.id } });
}

onMounted(async () => {
    try {
        [employees.value, services.value, customers.value] = await Promise.all([
            fetchEmployees(),
            fetchServiceOptions(),
            fetchCustomerOptions(),
        ]);
    } catch (exception) {
        error.value = message(exception);
    }
    await loadAgenda();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-6xl">
            <header class="border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Barbería / Salón</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Agenda de citas</h1>
                <p class="mt-2 text-sm text-[#464555]">Citas por empleado, con estado y total del servicio.</p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
                <section>
                    <div class="mb-4 flex flex-wrap items-end gap-3">
                        <label class="grid gap-1 text-sm font-semibold"
                            >Fecha
                            <input
                                v-model="filterDate"
                                type="date"
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                @change="loadAgenda"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Empleado
                            <select
                                v-model="filterEmployee"
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                @change="loadAgenda"
                            >
                                <option value="">Todos</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div v-if="loading" class="h-40 animate-pulse rounded-2xl bg-[#e4e1ee]" />
                    <ul v-else class="space-y-3">
                        <li
                            v-for="appointment in appointments"
                            :key="appointment.id"
                            class="rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold">
                                        {{ time(appointment.scheduled_at) }} ·
                                        {{ appointment.customer_name || 'Sin cliente' }}
                                    </p>
                                    <p class="text-sm text-[#464555]">
                                        {{ appointment.employee_name || 'Sin asignar' }} ·
                                        {{ appointment.services.map((s) => s.name).join(', ') }}
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">RD$ {{ appointment.total }}</p>
                                </div>
                                <span class="rounded-full bg-[#e4e1ee] px-3 py-1 text-xs font-semibold text-[#302f39]">
                                    {{ STATUS_LABELS[appointment.status] }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button
                                    v-if="appointment.status === 'completada' || appointment.status === 'en_proceso'"
                                    type="button"
                                    class="min-h-9 rounded-lg bg-[#4648d4] px-3 text-xs font-bold text-white hover:bg-[#393bb3] flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                                    title="Transferir servicios al POS para cobrar y emitir comprobante fiscal"
                                    @click="billInPos(appointment)"
                                >
                                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                    <span>Facturar en POS</span>
                                </button>
                                <button
                                    v-for="next in TRANSITIONS[appointment.status]"
                                    :key="next"
                                    type="button"
                                    class="min-h-9 rounded-lg border border-[#c7c4d8] px-3 text-xs font-bold text-[#3525cd] hover:bg-[#f0ecf9] cursor-pointer"
                                    @click="changeStatus(appointment, next)"
                                >
                                    {{ STATUS_LABELS[next] }}
                                </button>
                            </div>
                        </li>
                        <li
                            v-if="!appointments.length"
                            class="rounded-2xl border border-dashed border-[#c7c4d8] p-6 text-center text-sm text-[#464555]"
                        >
                            No hay citas para esta fecha.
                        </li>
                    </ul>
                </section>

                <aside class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
                    <h2 class="font-bold">Nueva cita</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="save">
                        <label class="grid gap-1 text-sm font-semibold"
                            >Fecha y hora
                            <input
                                v-model="form.scheduled_at"
                                type="datetime-local"
                                required
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Cliente
                            <select
                                v-model="form.customer_id"
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                            >
                                <option value="">Sin cliente</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }}
                                </option>
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Empleado
                            <select
                                v-model="form.employee_id"
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                            >
                                <option value="">Sin asignar</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                        </label>
                        <fieldset class="grid gap-1 text-sm font-semibold">
                            <span>Servicios</span>
                            <label
                                v-for="service in services"
                                :key="service.id"
                                class="flex items-center gap-2 rounded-lg border border-[#e4e1ee] px-3 py-2 font-normal"
                            >
                                <input v-model="form.service_ids" type="checkbox" :value="service.id" class="h-5 w-5" />
                                {{ service.name }} — RD$ {{ service.price }}
                            </label>
                            <p v-if="!services.length" class="font-normal text-[#464555]">
                                No hay servicios disponibles para citas. Márcalos en el catálogo de servicios.
                            </p>
                        </fieldset>
                        <p class="text-sm font-semibold">Subtotal servicios: RD$ {{ formTotal.toFixed(2) }}</p>
                        <button
                            type="submit"
                            :disabled="saving || !form.service_ids.length"
                            class="min-h-12 w-full rounded-lg bg-[#3525cd] px-4 text-base font-bold text-white disabled:opacity-60"
                        >
                            {{ saving ? 'Agendando…' : 'Agendar cita' }}
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </main>
</template>
