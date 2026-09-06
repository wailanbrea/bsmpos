<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { setExternalOrderBridge } from '../../pos/services';
import {
    createWorkOrder,
    fetchServiceOptions,
    fetchVehicleOptions,
    fetchWorkOrders,
    updateWorkOrderStatus,
} from '../services';
import type { ServiceOption, VehicleOption, WorkOrder, WorkOrderForm, WorkOrderStatus } from '../types';

const STATUS_LABELS: Record<WorkOrderStatus, string> = {
    recibida: 'Recibida',
    diagnosticando: 'Diagnosticando',
    cotizada: 'Cotizada',
    aprobada: 'Aprobada',
    en_proceso: 'En proceso',
    lista: 'Lista',
    entregada: 'Entregada',
    cancelada: 'Cancelada',
};

// Espejo de WorkOrder::TRANSITIONS del backend.
const TRANSITIONS: Record<WorkOrderStatus, WorkOrderStatus[]> = {
    recibida: ['diagnosticando', 'cancelada'],
    diagnosticando: ['cotizada', 'cancelada'],
    cotizada: ['aprobada', 'cancelada'],
    aprobada: ['en_proceso', 'cancelada'],
    en_proceso: ['lista', 'cancelada'],
    lista: ['entregada'],
    entregada: [],
    cancelada: [],
};

const orders = ref<WorkOrder[]>([]);
const vehicles = ref<VehicleOption[]>([]);
const services = ref<ServiceOption[]>([]);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const filterStatus = ref('');
const form = ref<WorkOrderForm>(emptyForm());

function emptyForm(): WorkOrderForm {
    return { vehicle_id: '', diagnosis: '', labor_amount: '0', service_ids: [], parts: [] };
}

function addPart(): void {
    form.value.parts.push({ name: '', quantity: '1', price: '0' });
}

function removePart(index: number): void {
    form.value.parts.splice(index, 1);
}

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        orders.value = await fetchWorkOrders({ status: filterStatus.value || undefined });
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
        await createWorkOrder(form.value);
        form.value = emptyForm();
        await load();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

async function changeStatus(order: WorkOrder, status: WorkOrderStatus): Promise<void> {
    error.value = null;
    try {
        const updated = await updateWorkOrderStatus(order.id, status);
        const index = orders.value.findIndex((item) => item.id === order.id);
        if (index !== -1) orders.value[index] = { ...orders.value[index], status: updated.status };
    } catch (exception) {
        error.value = message(exception);
    }
}

const router = useRouter();

function billInPos(order: WorkOrder): void {
    const items = [
        ...order.services.map((s) => ({
            product_id: 'srv-' + s.name,
            product_name: `[Servicio] ${s.name}`,
            quantity: 1,
            price: Number(s.price),
            discount: 0,
            tax_id: null,
            tax_rate: Number(s.tax_rate) || 18,
        })),
        ...order.parts.map((p) => ({
            product_id: 'part-' + p.name,
            product_name: `[Repuesto] ${p.name}`,
            quantity: Number(p.quantity) || 1,
            price: Number(p.price),
            discount: 0,
            tax_id: null,
            tax_rate: 18,
        })),
    ];

    if (Number(order.labor_amount) > 0) {
        items.push({
            product_id: 'labor-workshop',
            product_name: 'Mano de obra (Taller)',
            quantity: 1,
            price: Number(order.labor_amount),
            discount: 0,
            tax_id: null,
            tax_rate: 18,
        });
    }

    setExternalOrderBridge({
        source: 'work_order',
        reference_id: order.id,
        customer_id: order.customer_id,
        customer_name: order.customer_name,
        notes: `Orden de Trabajo #${order.id.slice(-6)} · ${order.vehicle_label || 'Vehículo'}${order.diagnosis ? ' (' + order.diagnosis + ')' : ''}`,
        items,
    });

    void router.push({ path: '/pos', query: { source: 'work_order', ref: order.id } });
}

onMounted(async () => {
    try {
        [vehicles.value, services.value] = await Promise.all([fetchVehicleOptions(), fetchServiceOptions()]);
    } catch (exception) {
        error.value = message(exception);
    }
    await load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-6xl">
            <header class="border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Taller mecánico</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Órdenes de trabajo</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Diagnóstico, servicios, repuestos y mano de obra por vehículo.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
                <section>
                    <label class="mb-4 grid max-w-[220px] gap-1 text-sm font-semibold"
                        >Estado
                        <select
                            v-model="filterStatus"
                            class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                            @change="load"
                        >
                            <option value="">Todos</option>
                            <option v-for="(label, value) in STATUS_LABELS" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </label>

                    <div v-if="loading" class="h-40 animate-pulse rounded-2xl bg-[#e4e1ee]" />
                    <ul v-else class="space-y-3">
                        <li
                            v-for="order in orders"
                            :key="order.id"
                            class="rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold">{{ order.vehicle_label }}</p>
                                    <p class="text-sm text-[#464555]">{{ order.diagnosis || 'Sin diagnóstico' }}</p>
                                    <p class="mt-1 text-sm font-semibold">RD$ {{ order.total }}</p>
                                </div>
                                <span class="rounded-full bg-[#e4e1ee] px-3 py-1 text-xs font-semibold text-[#302f39]">
                                    {{ STATUS_LABELS[order.status] }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button
                                    v-if="order.status === 'lista' || order.status === 'entregada' || order.status === 'en_proceso'"
                                    type="button"
                                    class="min-h-9 rounded-lg bg-[#4648d4] px-3 text-xs font-bold text-white hover:bg-[#393bb3] flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                                    title="Transferir servicios, repuestos y mano de obra al POS para cobrar y emitir comprobante fiscal"
                                    @click="billInPos(order)"
                                >
                                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                    <span>Facturar en POS</span>
                                </button>
                                <button
                                    v-for="next in TRANSITIONS[order.status]"
                                    :key="next"
                                    type="button"
                                    class="min-h-9 rounded-lg border border-[#c7c4d8] px-3 text-xs font-bold text-[#3525cd] hover:bg-[#f0ecf9] cursor-pointer"
                                    @click="changeStatus(order, next)"
                                >
                                    {{ STATUS_LABELS[next] }}
                                </button>
                            </div>
                        </li>
                        <li
                            v-if="!orders.length"
                            class="rounded-2xl border border-dashed border-[#c7c4d8] p-6 text-center text-sm text-[#464555]"
                        >
                            No hay órdenes de trabajo.
                        </li>
                    </ul>
                </section>

                <aside class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
                    <h2 class="font-bold">Nueva orden</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="save">
                        <label class="grid gap-1 text-sm font-semibold"
                            >Vehículo
                            <select
                                v-model="form.vehicle_id"
                                required
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                            >
                                <option value="" disabled>Selecciona…</option>
                                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                    {{ vehicle.label }}
                                </option>
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Diagnóstico
                            <textarea
                                v-model.trim="form.diagnosis"
                                rows="2"
                                class="rounded-lg border border-[#c7c4d8] px-3 py-2"
                            />
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
                                Sin servicios en el catálogo.
                            </p>
                        </fieldset>
                        <div class="grid gap-2 text-sm font-semibold">
                            <div class="flex items-center justify-between">
                                <span>Repuestos</span>
                                <button
                                    type="button"
                                    class="min-h-9 rounded-lg px-2 text-xs font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                                    @click="addPart"
                                >
                                    + Agregar
                                </button>
                            </div>
                            <div v-for="(part, index) in form.parts" :key="index" class="flex items-center gap-2">
                                <input
                                    v-model.trim="part.name"
                                    placeholder="Repuesto"
                                    class="min-h-12 flex-1 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                                <input
                                    v-model="part.quantity"
                                    type="number"
                                    min="0"
                                    class="min-h-12 w-16 rounded-lg border border-[#c7c4d8] px-2 text-base font-normal"
                                />
                                <input
                                    v-model="part.price"
                                    type="number"
                                    min="0"
                                    class="min-h-12 w-24 rounded-lg border border-[#c7c4d8] px-2 text-base font-normal"
                                />
                                <button
                                    type="button"
                                    class="min-h-9 px-1 text-sm font-bold text-[#ba1a1a]"
                                    @click="removePart(index)"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Mano de obra (RD$)
                            <input
                                v-model="form.labor_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                            />
                        </label>
                        <button
                            type="submit"
                            :disabled="saving || !form.vehicle_id"
                            class="min-h-12 w-full rounded-lg bg-[#3525cd] px-4 text-base font-bold text-white disabled:opacity-60"
                        >
                            {{ saving ? 'Creando…' : 'Crear orden' }}
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </main>
</template>
