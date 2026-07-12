<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { fetchSettingsGroup, saveSettingsGroup } from '../services';
import type { SettingField } from '../types';

const props = defineProps<{ group: string; title: string }>();

const schema = ref<Record<string, SettingField>>({});
const values = ref<Record<string, unknown>>({});
const loading = ref(true);
const saving = ref(false);
const saved = ref(false);
const error = ref<string | null>(null);

const labels: Record<string, string> = {
    allow_sell_without_stock: 'Vender sin existencias',
    default_document_type: 'Comprobante por defecto',
    require_customer_for_credit_fiscal: 'Exigir cliente en crédito fiscal',
    legal_tip_enabled: 'Propina legal activa',
    legal_tip_rate: 'Tasa de propina (%)',
    default_outgoing_method: 'Método de salida',
    low_stock_alerts: 'Alertas de stock bajo',
    expired_sale_policy: 'Venta de vencidos',
    near_expiration_days: 'Días para "próximo a vencer"',
};

function label(key: string): string {
    return labels[key] ?? key.replace(/_/g, ' ');
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const data = await fetchSettingsGroup(props.group);
        schema.value = data.schema;
        values.value = { ...data.values };
    } catch (exception) {
        error.value = axios.isAxiosError(exception)
            ? (exception.response?.data?.error?.message ?? 'No se pudo cargar.')
            : 'No se pudo cargar.';
    } finally {
        loading.value = false;
    }
}

async function save(): Promise<void> {
    saving.value = true;
    saved.value = false;
    error.value = null;
    try {
        const data = await saveSettingsGroup(props.group, values.value);
        values.value = { ...data.values };
        saved.value = true;
        window.setTimeout(() => (saved.value = false), 2000);
    } catch (exception) {
        error.value = axios.isAxiosError(exception)
            ? (exception.response?.data?.error?.message ?? 'No se pudo guardar.')
            : 'No se pudo guardar.';
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    void load();
});
</script>

<template>
    <section class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold">{{ title }}</h3>
            <span v-if="saved" class="text-xs font-semibold text-[#006c49]">Guardado ✓</span>
        </div>

        <p v-if="error" class="mb-3 rounded-lg bg-[#ffdad6] p-2 text-sm text-[#93000a]" role="alert">{{ error }}</p>

        <div v-if="loading" class="h-32 animate-pulse rounded-xl bg-[#e4e1ee]" />
        <div v-else class="grid gap-3">
            <label
                v-for="(field, key) in schema"
                :key="key"
                class="flex items-center justify-between gap-3 text-sm font-semibold"
            >
                {{ label(key) }}
                <input v-if="field.type === 'bool'" v-model="values[key]" type="checkbox" class="h-5 w-5" />
                <select
                    v-else-if="field.type === 'enum'"
                    v-model="values[key]"
                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                >
                    <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                </select>
                <input
                    v-else
                    v-model="values[key]"
                    type="number"
                    class="min-h-11 w-28 rounded-lg border border-[#c7c4d8] px-2 text-right font-normal"
                />
            </label>
            <button
                type="button"
                :disabled="saving"
                class="mt-2 min-h-12 rounded-lg bg-[#3525cd] px-4 text-base font-bold text-white shadow-sm disabled:opacity-60"
                @click="save"
            >
                {{ saving ? 'Guardando…' : 'Guardar' }}
            </button>
        </div>
    </section>
</template>
