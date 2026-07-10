<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { createNcfSequence, createTax, fetchFiscalSettings, fetchNcfSequences } from '../services';
import type { FiscalSettings, NcfSequence } from '../types';

const settings = ref<FiscalSettings | null>(null);
const sequences = ref<NcfSequence[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const savingSeq = ref(false);

const seqForm = ref({
    document_type_code: 'B02',
    start_number: 1,
    end_number: 1000,
    expires_at: '',
});

const documentTypes = computed(() => settings.value?.document_types ?? []);

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        settings.value = await fetchFiscalSettings();
        sequences.value = await fetchNcfSequences();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function addSequence(): Promise<void> {
    savingSeq.value = true;
    error.value = null;
    try {
        await createNcfSequence({
            document_type_code: seqForm.value.document_type_code,
            start_number: Number(seqForm.value.start_number),
            end_number: Number(seqForm.value.end_number),
            expires_at: seqForm.value.expires_at || null,
        });
        sequences.value = await fetchNcfSequences();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingSeq.value = false;
    }
}

// Exposed for future inline tax creation; keeps the service tree-shakeable in tests.
void createTax;

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-5xl">
            <RouterLink to="/" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd]"
                >← Volver al panel</RouterLink
            >
            <header class="mt-3 border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Configuración</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Configuración fiscal</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Impuestos, métodos de pago y secuencias de comprobantes fiscales (NCF/e-CF) de la empresa.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div v-if="loading" class="mt-6 grid gap-4">
                <div v-for="row in 3" :key="row" class="h-28 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            </div>

            <div v-else-if="settings" class="mt-6 space-y-8">
                <section>
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">Impuestos y cargos</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <li
                            v-for="tax in settings.taxes"
                            :key="tax.id"
                            class="flex items-center justify-between rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                        >
                            <div>
                                <p class="font-bold">{{ tax.name }}</p>
                                <p class="text-sm text-[#464555]">{{ tax.scope }} · {{ tax.code }}</p>
                            </div>
                            <span class="font-numeric text-lg font-bold">{{ Number(tax.rate) }}%</span>
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">Métodos de pago</h2>
                    <ul class="flex flex-wrap gap-2">
                        <li
                            v-for="method in settings.payment_methods"
                            :key="method.id"
                            class="rounded-full border border-[#c7c4d8] bg-white px-4 py-2 text-sm font-semibold"
                        >
                            {{ method.name }}
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">
                        Secuencias NCF / e-CF
                    </h2>
                    <div class="overflow-x-auto rounded-2xl border border-[#c7c4d8] bg-white">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-[#e4e1ee] text-xs uppercase tracking-wider text-[#464555]">
                                <tr>
                                    <th class="p-3">Comprobante</th>
                                    <th class="p-3">Rango</th>
                                    <th class="p-3">Disponibles</th>
                                    <th class="p-3">Vence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="sequences.length === 0">
                                    <td class="p-4 text-[#464555]" colspan="4">Aún no hay secuencias configuradas.</td>
                                </tr>
                                <tr v-for="seq in sequences" :key="seq.id" class="border-b border-[#f0ecf9]">
                                    <td class="p-3 font-mono font-semibold">{{ seq.document_type_code }}</td>
                                    <td class="p-3">{{ seq.start_number }} – {{ seq.end_number }}</td>
                                    <td class="p-3">
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-semibold"
                                            :class="
                                                seq.remaining <= seq.alert_threshold
                                                    ? 'bg-[#ffdad6] text-[#93000a]'
                                                    : 'bg-[#d9f7df] text-[#006c49]'
                                            "
                                            >{{ seq.remaining }}</span
                                        >
                                    </td>
                                    <td class="p-3">{{ seq.expires_at ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <form
                        class="mt-4 grid gap-3 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm sm:grid-cols-5"
                        @submit.prevent="addSequence"
                    >
                        <label class="grid gap-1 text-xs font-semibold sm:col-span-2"
                            >Comprobante
                            <select
                                v-model="seqForm.document_type_code"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-2 font-normal"
                            >
                                <option v-for="dt in documentTypes" :key="dt.code" :value="dt.code">
                                    {{ dt.code }} — {{ dt.name }}
                                </option>
                            </select>
                        </label>
                        <label class="grid gap-1 text-xs font-semibold"
                            >Desde
                            <input
                                v-model="seqForm.start_number"
                                type="number"
                                min="1"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-2 font-normal"
                            />
                        </label>
                        <label class="grid gap-1 text-xs font-semibold"
                            >Hasta
                            <input
                                v-model="seqForm.end_number"
                                type="number"
                                min="2"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-2 font-normal"
                            />
                        </label>
                        <label class="grid gap-1 text-xs font-semibold"
                            >Vence
                            <input
                                v-model="seqForm.expires_at"
                                type="date"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-2 font-normal"
                            />
                        </label>
                        <button
                            type="submit"
                            :disabled="savingSeq"
                            class="min-h-11 rounded-lg bg-[#3525cd] px-4 text-sm font-bold text-white shadow-sm disabled:opacity-60 sm:col-span-5"
                        >
                            {{ savingSeq ? 'Guardando…' : 'Agregar secuencia' }}
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>
</template>
