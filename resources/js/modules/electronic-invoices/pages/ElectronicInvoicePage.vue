<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '../../../lib/api';

interface EInvoice {
    id: string;
    invoice_number: string | null;
    ncf: string | null;
    customer_name: string | null;
    total: string | null;
    provider_code: string;
    environment: string;
    status: string;
    track_id: string | null;
    last_error: string | null;
    created_at: string | null;
}

interface EInvoiceSettings {
    provider_code: string;
    environment: string;
    is_active: boolean;
}

const records = ref<EInvoice[]>([]);
const settings = ref<EInvoiceSettings>({ provider_code: 'mock', environment: 'test', is_active: false });
const loading = ref(true);
const savingSettings = ref(false);
const retrying = ref<string | null>(null);
const error = ref<string | null>(null);
const savedOk = ref(false);

const statusLabels: Record<string, { label: string; classes: string }> = {
    accepted: { label: 'Aceptada', classes: 'bg-[#d9f7df] text-[#006c49]' },
    pending: { label: 'Pendiente', classes: 'bg-[#fff3cd] text-[#8a6d00]' },
    sent: { label: 'Enviada', classes: 'bg-[#e2dfff] text-[#3323cc]' },
    rejected: { label: 'Rechazada', classes: 'bg-[#ffdad6] text-[#93000a]' },
    error: { label: 'Error', classes: 'bg-[#ffdad6] text-[#93000a]' },
    canceled: { label: 'Anulada', classes: 'bg-[#e4e1ee] text-[#464555]' },
    contingency: { label: 'Contingencia', classes: 'bg-[#fff3cd] text-[#8a6d00]' },
};

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const [listRes, settingsRes] = await Promise.all([
            api.get('/electronic-invoices'),
            api.get('/electronic-invoices/settings'),
        ]);
        records.value = listRes.data.data;
        settings.value = settingsRes.data.data;
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function saveSettings(): Promise<void> {
    savingSettings.value = true;
    error.value = null;
    savedOk.value = false;
    try {
        const res = await api.put('/electronic-invoices/settings', settings.value);
        settings.value = res.data.data;
        savedOk.value = true;
        window.setTimeout(() => (savedOk.value = false), 2000);
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingSettings.value = false;
    }
}

async function retry(record: EInvoice): Promise<void> {
    retrying.value = record.id;
    error.value = null;
    try {
        await api.post(`/electronic-invoices/${record.id}/retry`);
        await load();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        retrying.value = null;
    }
}

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-6xl">
            <RouterLink to="/" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd]"
                >← Volver al panel</RouterLink
            >
            <header class="mt-3 border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Fiscal</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Facturación Electrónica (e-CF)</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Estado de los comprobantes fiscales electrónicos. El proveedor real se conecta cuando la empresa
                    complete su certificación ante la DGII; mientras tanto el simulador permite operar y probar.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div v-if="loading" class="mt-6 grid gap-4">
                <div v-for="row in 3" :key="row" class="h-24 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            </div>

            <div v-else class="mt-6 space-y-8">
                <section class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-bold">Proveedor de emisión</h2>
                        <span v-if="savedOk" class="text-xs font-semibold text-[#006c49]">Guardado ✓</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-4">
                        <label class="grid gap-2 text-sm font-semibold"
                            >Proveedor
                            <select
                                v-model="settings.provider_code"
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                            >
                                <option value="mock">Simulador (pruebas)</option>
                                <option value="null">Ninguno (solo NCF)</option>
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold"
                            >Ambiente
                            <select
                                v-model="settings.environment"
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                            >
                                <option value="test">Pruebas</option>
                                <option value="cert">Certificación</option>
                                <option value="prod">Producción</option>
                            </select>
                        </label>
                        <label class="flex min-h-11 items-center gap-3 self-end text-sm font-semibold">
                            <input v-model="settings.is_active" type="checkbox" class="h-5 w-5" />
                            Emisión activa
                        </label>
                        <button
                            type="button"
                            :disabled="savingSettings"
                            class="min-h-12 self-end rounded-lg bg-[#3525cd] px-4 text-base font-bold text-white shadow-sm disabled:opacity-60"
                            @click="saveSettings"
                        >
                            {{ savingSettings ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </section>

                <section>
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">Comprobantes</h2>
                    <div class="overflow-x-auto rounded-2xl border border-[#c7c4d8] bg-white">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-[#e4e1ee] text-xs uppercase tracking-wider text-[#464555]">
                                <tr>
                                    <th class="p-3">Factura / NCF</th>
                                    <th class="p-3">Cliente</th>
                                    <th class="p-3">Total</th>
                                    <th class="p-3">Estado</th>
                                    <th class="p-3">Track</th>
                                    <th class="p-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="records.length === 0">
                                    <td class="p-4 text-[#464555]" colspan="6">
                                        Aún no hay comprobantes electrónicos. Activa la emisión y factura desde el POS.
                                    </td>
                                </tr>
                                <tr v-for="record in records" :key="record.id" class="border-b border-[#f0ecf9]">
                                    <td class="p-3">
                                        <span class="block font-semibold">{{ record.invoice_number }}</span>
                                        <span class="font-mono text-xs text-[#464555]">{{ record.ncf }}</span>
                                    </td>
                                    <td class="p-3">{{ record.customer_name ?? '—' }}</td>
                                    <td class="p-3 font-semibold">RD$ {{ record.total }}</td>
                                    <td class="p-3">
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-semibold"
                                            :class="statusLabels[record.status]?.classes ?? 'bg-[#e4e1ee]'"
                                        >
                                            {{ statusLabels[record.status]?.label ?? record.status }}
                                        </span>
                                        <p v-if="record.last_error" class="mt-1 text-xs text-[#93000a]">
                                            {{ record.last_error }}
                                        </p>
                                    </td>
                                    <td class="p-3 font-mono text-xs">{{ record.track_id ?? '—' }}</td>
                                    <td class="p-3">
                                        <button
                                            v-if="
                                                ['rejected', 'error', 'pending', 'contingency'].includes(record.status)
                                            "
                                            type="button"
                                            :disabled="retrying === record.id"
                                            class="min-h-9 rounded-lg border border-[#3525cd] px-3 text-xs font-bold text-[#3525cd] disabled:opacity-60"
                                            @click="retry(record)"
                                        >
                                            {{ retrying === record.id ? 'Reintentando…' : 'Reintentar' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
