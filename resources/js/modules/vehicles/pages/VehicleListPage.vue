<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { createVehicle, fetchCustomerOptions, fetchVehicles, updateVehicle } from '../services';
import type { CustomerOption, Vehicle, VehicleForm } from '../types';

const vehicles = ref<Vehicle[]>([]);
const customers = ref<CustomerOption[]>([]);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const selected = ref<Vehicle | null>(null);
const form = ref<VehicleForm>(emptyForm());

function emptyForm(): VehicleForm {
    return { customer_id: '', brand: '', model: '', year: '', plate: '', vin: '', color: '', mileage: '', notes: '' };
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
        vehicles.value = await fetchVehicles();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

function edit(vehicle: Vehicle): void {
    selected.value = vehicle;
    form.value = {
        customer_id: vehicle.customer_id ?? '',
        brand: vehicle.brand,
        model: vehicle.model ?? '',
        year: vehicle.year?.toString() ?? '',
        plate: vehicle.plate ?? '',
        vin: vehicle.vin ?? '',
        color: vehicle.color ?? '',
        mileage: vehicle.mileage?.toString() ?? '',
        notes: vehicle.notes ?? '',
    };
}

function reset(): void {
    selected.value = null;
    form.value = emptyForm();
}

async function save(): Promise<void> {
    saving.value = true;
    error.value = null;
    try {
        if (selected.value) {
            await updateVehicle(selected.value.id, form.value);
        } else {
            await createVehicle(form.value);
        }
        reset();
        await load();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    try {
        customers.value = await fetchCustomerOptions();
    } catch (exception) {
        error.value = message(exception);
    }
    await load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-5xl">
            <RouterLink to="/" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd]"
                >← Volver al panel</RouterLink
            >
            <header class="mt-3 border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Taller mecánico</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Vehículos</h1>
                <p class="mt-2 text-sm text-[#464555]">Vehículos de tus clientes para las órdenes de trabajo.</p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                <section>
                    <div v-if="loading" class="h-40 animate-pulse rounded-2xl bg-[#e4e1ee]" />
                    <ul v-else class="space-y-3">
                        <li
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                        >
                            <div>
                                <p class="font-bold">{{ vehicle.brand }} {{ vehicle.model }} · {{ vehicle.plate }}</p>
                                <p class="text-sm text-[#464555]">
                                    {{ vehicle.customer_name }}<span v-if="vehicle.year"> · {{ vehicle.year }}</span>
                                </p>
                            </div>
                            <button
                                type="button"
                                class="min-h-11 rounded-lg px-4 text-base font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                                @click="edit(vehicle)"
                            >
                                Editar
                            </button>
                        </li>
                        <li
                            v-if="!vehicles.length"
                            class="rounded-2xl border border-dashed border-[#c7c4d8] p-6 text-center text-sm text-[#464555]"
                        >
                            Aún no hay vehículos registrados.
                        </li>
                    </ul>
                </section>

                <aside class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
                    <h2 class="font-bold">{{ selected ? 'Editar vehículo' : 'Nuevo vehículo' }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="save">
                        <label class="grid gap-1 text-sm font-semibold"
                            >Cliente
                            <select
                                v-model="form.customer_id"
                                :disabled="!!selected"
                                required
                                class="min-h-12 w-full rounded-lg border border-[#c7c4d8] px-3 text-base disabled:opacity-60"
                            >
                                <option value="" disabled>Selecciona…</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }}
                                </option>
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="grid gap-1 text-sm font-semibold"
                                >Marca
                                <input
                                    v-model.trim="form.brand"
                                    required
                                    class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </label>
                            <label class="grid gap-1 text-sm font-semibold"
                                >Modelo
                                <input
                                    v-model.trim="form.model"
                                    class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </label>
                            <label class="grid gap-1 text-sm font-semibold"
                                >Año
                                <input
                                    v-model="form.year"
                                    type="number"
                                    class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </label>
                            <label class="grid gap-1 text-sm font-semibold"
                                >Placa
                                <input
                                    v-model.trim="form.plate"
                                    class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </label>
                            <label class="grid gap-1 text-sm font-semibold"
                                >Color
                                <input
                                    v-model.trim="form.color"
                                    class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </label>
                            <label class="grid gap-1 text-sm font-semibold"
                                >Kilometraje
                                <input
                                    v-model="form.mileage"
                                    type="number"
                                    class="min-h-12 w-full min-w-0 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </label>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button
                                type="submit"
                                :disabled="saving || !form.brand || !form.customer_id"
                                class="min-h-12 flex-1 rounded-lg bg-[#3525cd] px-4 text-base font-bold text-white disabled:opacity-60"
                            >
                                {{ saving ? 'Guardando…' : 'Guardar' }}
                            </button>
                            <button
                                v-if="selected"
                                type="button"
                                class="min-h-11 rounded-lg px-4 text-base font-bold text-[#464555] hover:bg-[#f0ecf9]"
                                @click="reset"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </aside>
            </div>
        </div>
    </main>
</template>
