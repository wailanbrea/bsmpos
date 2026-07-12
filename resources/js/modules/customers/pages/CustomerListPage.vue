<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { createCustomer, fetchCustomers, updateCustomer } from '../services';
import type { Customer, CustomerForm } from '../types';

const customers = ref<Customer[]>([]);
const search = ref('');
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const selected = ref<Customer | null>(null);
const form = ref<CustomerForm>(emptyForm());
const editing = computed(() => selected.value !== null);

let searchTimer: ReturnType<typeof window.setTimeout> | undefined;

function emptyForm(): CustomerForm {
    return {
        kind: 'person',
        name: '',
        tax_id_type: 'none',
        tax_id: '',
        phone: '',
        whatsapp: '',
        email: '',
        address: '',
        credit_limit: 0,
        credit_days: 0,
    };
}

function select(customer: Customer | null): void {
    selected.value = customer;
    error.value = null;
    form.value = customer
        ? {
              kind: customer.kind,
              name: customer.name,
              tax_id_type: customer.tax_id_type ?? 'none',
              tax_id: customer.tax_id ?? '',
              phone: customer.phone ?? '',
              whatsapp: customer.whatsapp ?? '',
              email: customer.email ?? '',
              address: customer.address ?? '',
              credit_limit: Number(customer.credit_limit),
              credit_days: customer.credit_days,
          }
        : emptyForm();
}

function message(exception: unknown): string {
    if (axios.isAxiosError(exception)) {
        const details = exception.response?.data?.error?.details;
        if (details && typeof details === 'object') {
            const first = Object.values(details)[0];
            if (Array.isArray(first) && first.length > 0) {
                return String(first[0]);
            }
        }
        return exception.response?.data?.error?.message ?? 'No se pudo completar la operación.';
    }
    return 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        customers.value = await fetchCustomers(search.value.trim());
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
        if (selected.value) {
            await updateCustomer(selected.value.id, form.value);
        } else {
            await createCustomer(form.value);
        }
        await load();
        select(null);
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => void load(), 300);
});

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-7xl">
            <RouterLink to="/" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd]"
                >← Volver al panel</RouterLink
            >
            <header class="mt-3 border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Directorio</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Clientes</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Consumidor final, personas y empresas con sus datos fiscales, crédito y balance.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-[1.2fr_.9fr]">
                <section class="rounded-2xl border border-[#c7c4d8] bg-white p-3">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-2 pb-3">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Buscar por nombre, RNC o teléfono…"
                            class="min-h-12 flex-1 rounded-lg border border-[#c7c4d8] px-3 text-base"
                        />
                        <button
                            class="min-h-11 rounded-lg px-3 text-base font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                            @click="select(null)"
                        >
                            Nuevo cliente
                        </button>
                    </div>

                    <div v-if="loading" class="space-y-2 p-2">
                        <div v-for="row in 5" :key="row" class="h-16 animate-pulse rounded-xl bg-[#e4e1ee]" />
                    </div>
                    <p v-else-if="customers.length === 0" class="p-5 text-sm text-[#464555]">
                        No se encontraron clientes.
                    </p>
                    <button
                        v-for="customer in customers"
                        v-else
                        :key="customer.id"
                        class="mb-2 w-full rounded-xl border p-4 text-left transition hover:border-[#4f46e5]"
                        :class="selected?.id === customer.id ? 'border-[#3525cd] bg-[#f5f2ff]' : 'border-[#e4e1ee]'"
                        @click="select(customer)"
                    >
                        <span class="flex items-center justify-between gap-3 font-bold">
                            {{ customer.name }}
                            <span
                                v-if="customer.is_generic"
                                class="rounded-full bg-[#e2dfff] px-2 py-0.5 text-xs font-semibold text-[#3323cc]"
                                >Genérico</span
                            >
                        </span>
                        <span class="mt-1 flex flex-wrap gap-x-4 text-sm text-[#464555]">
                            <span v-if="customer.tax_id"
                                >{{ customer.tax_id_type?.toUpperCase() }} {{ customer.tax_id }}</span
                            >
                            <span v-if="customer.phone">{{ customer.phone }}</span>
                            <span v-if="Number(customer.balance) > 0" class="font-semibold text-[#93000a]"
                                >Debe RD$ {{ customer.balance }}</span
                            >
                        </span>
                    </button>
                </section>

                <form class="h-fit rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm" @submit.prevent="save">
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-[#006c49]">
                        {{ editing ? 'Editar cliente' : 'Nuevo cliente' }}
                    </p>
                    <div class="mt-4 grid gap-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold"
                                >Tipo
                                <select
                                    v-model="form.kind"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                >
                                    <option value="person">Persona</option>
                                    <option value="company">Empresa</option>
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-semibold"
                                >Nombre / Razón social
                                <input
                                    v-model.trim="form.name"
                                    required
                                    maxlength="150"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold"
                                >Documento
                                <select
                                    v-model="form.tax_id_type"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                >
                                    <option value="none">Ninguno</option>
                                    <option value="rnc">RNC</option>
                                    <option value="cedula">Cédula</option>
                                    <option value="passport">Pasaporte</option>
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-semibold"
                                >Número
                                <input
                                    v-model.trim="form.tax_id"
                                    maxlength="30"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 font-mono text-base font-normal"
                                />
                            </label>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold"
                                >Teléfono
                                <input
                                    v-model.trim="form.phone"
                                    type="tel"
                                    maxlength="30"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                            <label class="grid gap-2 text-sm font-semibold"
                                >WhatsApp
                                <input
                                    v-model.trim="form.whatsapp"
                                    type="tel"
                                    maxlength="30"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                        </div>
                        <label class="grid gap-2 text-sm font-semibold"
                            >Correo
                            <input
                                v-model.trim="form.email"
                                type="email"
                                maxlength="255"
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                            />
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold"
                                >Límite de crédito (RD$)
                                <input
                                    v-model="form.credit_limit"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                            <label class="grid gap-2 text-sm font-semibold"
                                >Días de crédito
                                <input
                                    v-model="form.credit_days"
                                    type="number"
                                    min="0"
                                    max="365"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                        </div>
                    </div>
                    <button
                        class="mt-6 min-h-12 rounded-lg bg-[#3525cd] px-5 text-base font-bold text-white shadow-sm disabled:opacity-60"
                        :disabled="saving"
                        type="submit"
                    >
                        {{ saving ? 'Guardando…' : editing ? 'Guardar cambios' : 'Crear cliente' }}
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
