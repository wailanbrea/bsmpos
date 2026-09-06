<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { fetchSettingsGroup, saveSettingsGroup } from '../services';
import type { SettingField } from '../types';

const props = defineProps<{
    group: string;
    title: string;
    description?: string;
    icon?: string;
}>();

const schema = ref<Record<string, SettingField>>({});
const values = ref<Record<string, unknown>>({});
const loading = ref(true);
const saving = ref(false);
const saved = ref(false);
const error = ref<string | null>(null);

const fieldMeta: Record<string, { label: string; hint?: string }> = {
    allow_sell_without_stock: {
        label: 'Vender sin existencias en almacén',
        hint: 'Permite completar transacciones en el POS incluso si el producto tiene stock cero.',
    },
    default_document_type: {
        label: 'Comprobante fiscal por defecto',
        hint: 'Tipo de comprobante preseleccionado al abrir una venta en el terminal.',
    },
    require_customer_for_credit_fiscal: {
        label: 'Exigir cliente con RNC en Crédito Fiscal',
        hint: 'Impide emitir facturas con valor fiscal (B01/E31) sin cliente identificado.',
    },
    legal_tip_enabled: {
        label: 'Propina legal del 10% activa',
        hint: 'Aplica el 10% de propina de ley para consumos en restaurante/salón.',
    },
    legal_tip_rate: {
        label: 'Tasa de propina (%)',
        hint: 'Porcentaje legal a aplicar sobre el consumo neto.',
    },
    default_outgoing_method: {
        label: 'Método de costeo y salida',
        hint: 'Estrategia de inventario: FEFO (vencimiento), FIFO (antigüedad) o Promedio ponderado.',
    },
    low_stock_alerts: {
        label: 'Alertas de stock crítico',
        hint: 'Genera notificaciones automáticas al descender del umbral de stock mínimo.',
    },
    expired_sale_policy: {
        label: 'Política de venta de productos vencidos',
        hint: 'Comportamiento ante lotes con fecha de vencimiento superada (bloquear o advertir).',
    },
    near_expiration_days: {
        label: 'Días para alerta de "Próximo a Vencer"',
        hint: 'Días de anticipación para advertir sobre lotes cercanos a su caducidad.',
    },
    price_includes_tax: {
        label: 'Precios del catálogo incluyen ITBIS',
        hint: 'Si está activo, el ITBIS se desglosa del precio final sin agregarse encima.',
    },
    default_currency: {
        label: 'Moneda principal de facturación',
        hint: 'Moneda contable predeterminada de la empresa (DOP, USD, EUR).',
    },
    ncf_alert_threshold: {
        label: 'Alerta de comprobantes restantes (NCF)',
        hint: 'Cantidad de números disponibles que disparará la alerta preventiva de agotamiento.',
    },
    ticket_width_mm: {
        label: 'Ancho de papel para impresión de tickets',
        hint: 'Formato físico admitido: 58mm (impresoras portátiles / Bluetooth) u 80mm (mostrador).',
    },
    print_copies: {
        label: 'Copias a imprimir por venta',
        hint: 'Cantidad de copias del ticket generadas automáticamente.',
    },
    auto_print_on_sale: {
        label: 'Imprimir automáticamente al cobrar',
        hint: 'Despacha el ticket a la impresora sin esperar confirmación en pantalla.',
    },
    session_timeout_minutes: {
        label: 'Tiempo de inactividad de sesión (minutos)',
        hint: 'Cierra automáticamente la sesión tras este tiempo sin actividad.',
    },
    require_2fa_admins: {
        label: 'Exigir 2FA a administradores',
        hint: 'Obliga a configurar código TOTP a usuarios con privilegios avanzados.',
    },
    cashier_pin_required: {
        label: 'Exigir PIN de cajero',
        hint: 'Solicita PIN rápido de 4 dígitos para autorizaciones de caja.',
    },
    enabled: {
        label: 'Copias de seguridad automáticas',
        hint: 'Respaldos programados periódicos de los datos de la empresa.',
    },
    frequency: {
        label: 'Frecuencia de respaldo',
        hint: 'Periodicidad con la que se generan las instantáneas de la base de datos.',
    },
    retention_days: {
        label: 'Días de retención de respaldos',
        hint: 'Días antes de purgar los archivos de copia históricos.',
    },
};

function getFieldLabel(key: string): string {
    return fieldMeta[key]?.label ?? key.replace(/_/g, ' ');
}

function getFieldHint(key: string): string | undefined {
    return fieldMeta[key]?.hint;
}

const cardIcon = computed(() => {
    if (props.icon) return props.icon;
    const map: Record<string, string> = {
        pos: 'point_of_sale',
        inventory: 'inventory_2',
        invoice: 'receipt_long',
        printing: 'print',
        security: 'security',
        backup: 'cloud_sync',
    };
    return map[props.group] ?? 'settings';
});

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const data = await fetchSettingsGroup(props.group);
        schema.value = data.schema;
        values.value = { ...data.values };
    } catch (exception) {
        error.value = axios.isAxiosError(exception)
            ? (exception.response?.data?.error?.message ?? 'No se pudo cargar la configuración.')
            : 'No se pudo cargar la configuración.';
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
        window.setTimeout(() => (saved.value = false), 2500);
    } catch (exception) {
        error.value = axios.isAxiosError(exception)
            ? (exception.response?.data?.error?.message ?? 'No se pudo guardar la configuración.')
            : 'No se pudo guardar la configuración.';
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    void load();
});
</script>

<template>
    <section class="flex flex-col justify-between rounded-2xl border border-[#e2e8f0] bg-white p-5 shadow-xs transition-shadow hover:shadow-md">
        <div>
            <!-- CABECERA -->
            <div class="mb-4 flex items-start justify-between gap-3 border-b border-[#f1f5f9] pb-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eff4ff] text-[#4648d4]">
                        <span class="material-symbols-outlined text-[20px]">{{ cardIcon }}</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#0b1c30] leading-tight">{{ title }}</h3>
                        <p v-if="description" class="text-xs text-[#64748b] mt-0.5">{{ description }}</p>
                    </div>
                </div>
                <transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
                    <span v-if="saved" class="flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                        <span class="material-symbols-outlined text-[14px]">check</span> Guardado
                    </span>
                </transition>
            </div>

            <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-2.5 text-xs text-red-700 border border-red-200" role="alert">
                {{ error }}
            </p>

            <div v-if="loading" class="space-y-3 py-2">
                <div class="h-10 animate-pulse rounded-lg bg-slate-100" />
                <div class="h-10 animate-pulse rounded-lg bg-slate-100" />
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(field, key) in schema"
                    :key="key"
                    class="rounded-xl p-2.5 transition-colors hover:bg-[#f8fafc]"
                >
                    <!-- Booleano (Switch) -->
                    <div v-if="field.type === 'bool'" class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <label :for="`switch-${group}-${key}`" class="text-xs font-semibold text-[#1e293b] cursor-pointer">
                                {{ getFieldLabel(key) }}
                            </label>
                            <p v-if="getFieldHint(key)" class="text-[11px] text-[#64748b] mt-0.5 leading-snug">
                                {{ getFieldHint(key) }}
                            </p>
                        </div>
                        <label :for="`switch-${group}-${key}`" class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input
                                :id="`switch-${group}-${key}`"
                                v-model="values[key]"
                                type="checkbox"
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#4648d4]" />
                        </label>
                    </div>

                    <!-- Enum (Select) -->
                    <div v-else-if="field.type === 'enum'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <label :for="`select-${group}-${key}`" class="text-xs font-semibold text-[#1e293b]">
                                {{ getFieldLabel(key) }}
                            </label>
                            <p v-if="getFieldHint(key)" class="text-[11px] text-[#64748b] mt-0.5 leading-snug">
                                {{ getFieldHint(key) }}
                            </p>
                        </div>
                        <select
                            :id="`select-${group}-${key}`"
                            v-model="values[key]"
                            class="min-h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-[#0f172a] shadow-2xs focus:border-[#4648d4] focus:outline-none"
                        >
                            <option v-for="option in field.options" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>

                    <!-- Numérico -->
                    <div v-else class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <label :for="`input-${group}-${key}`" class="text-xs font-semibold text-[#1e293b]">
                                {{ getFieldLabel(key) }}
                            </label>
                            <p v-if="getFieldHint(key)" class="text-[11px] text-[#64748b] mt-0.5 leading-snug">
                                {{ getFieldHint(key) }}
                            </p>
                        </div>
                        <input
                            :id="`input-${group}-${key}`"
                            v-model="values[key]"
                            type="number"
                            :step="field.type === 'decimal' ? '0.01' : '1'"
                            class="h-9 w-28 rounded-lg border border-slate-200 px-3 text-right text-xs font-medium text-[#0f172a] shadow-2xs focus:border-[#4648d4] focus:outline-none"
                        >
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-[#f1f5f9] flex justify-end">
            <button
                type="button"
                :disabled="saving || loading"
                class="flex items-center gap-1.5 rounded-lg bg-[#4648d4] px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-[#393bb3] active:scale-95 disabled:opacity-60 cursor-pointer"
                @click="save"
            >
                <span class="material-symbols-outlined text-[16px]">save</span>
                <span>{{ saving ? 'Guardando…' : 'Guardar preferencias' }}</span>
            </button>
        </div>
    </section>
</template>