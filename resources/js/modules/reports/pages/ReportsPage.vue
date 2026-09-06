<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { downloadReport, fetchReport } from '../services';
import type { ReportResult } from '../services';

interface ReportTab {
    key: string;
    label: string;
    path: string;
    columns: Array<{ key: string; label: string; money?: boolean; date?: boolean }>;
}

const tabs: ReportTab[] = [
    {
        key: 'sales',
        label: 'Ventas',
        path: 'sales',
        columns: [
            { key: 'issued_at', label: 'Fecha', date: true },
            { key: 'invoice_number', label: 'Factura' },
            { key: 'ncf', label: 'NCF' },
            { key: 'customer', label: 'Cliente' },
            { key: 'total', label: 'Total', money: true },
        ],
    },
    {
        key: 'by-product',
        label: 'Por producto',
        path: 'sales/by-product',
        columns: [
            { key: 'product_name', label: 'Producto' },
            { key: 'sku', label: 'SKU' },
            { key: 'quantity', label: 'Cantidad' },
            { key: 'total', label: 'Total', money: true },
        ],
    },
    {
        key: 'by-category',
        label: 'Por categoría',
        path: 'sales/by-category',
        columns: [
            { key: 'category_name', label: 'Categoría' },
            { key: 'quantity', label: 'Cantidad' },
            { key: 'total', label: 'Total', money: true },
        ],
    },
    {
        key: 'by-payment',
        label: 'Por método de pago',
        path: 'sales/by-payment-method',
        columns: [
            { key: 'payment_method', label: 'Método' },
            { key: 'total', label: 'Total', money: true },
        ],
    },
    {
        key: 'taxes',
        label: 'Impuestos',
        path: 'sales/taxes',
        columns: [
            { key: 'tax_name', label: 'Impuesto' },
            { key: 'rate', label: 'Tasa %' },
            { key: 'taxable_amount', label: 'Base', money: true },
            { key: 'tax_total', label: 'Impuesto', money: true },
        ],
    },
    {
        key: 'cash',
        label: 'Caja',
        path: 'cash',
        columns: [
            { key: 'closed_at', label: 'Cierre', date: true },
            { key: 'register', label: 'Caja' },
            { key: 'expected_amount', label: 'Esperado', money: true },
            { key: 'counted_amount', label: 'Contado', money: true },
            { key: 'difference', label: 'Diferencia', money: true },
        ],
    },
    {
        key: 'annulments',
        label: 'Anulaciones',
        path: 'annulments',
        columns: [
            { key: 'canceled_at', label: 'Anulada', date: true },
            { key: 'invoice_number', label: 'Factura' },
            { key: 'ncf', label: 'NCF' },
            { key: 'customer', label: 'Cliente' },
            { key: 'total', label: 'Total', money: true },
        ],
    },
];

const activeTab = ref<ReportTab>(tabs[0]);
const result = ref<ReportResult>({ rows: [] });
const loading = ref(false);
const error = ref<string | null>(null);
const downloading = ref<string | null>(null);

const today = new Date().toISOString().slice(0, 10);
const monthStart = `${today.slice(0, 7)}-01`;
const from = ref(monthStart);
const to = ref(today);
const period = computed(() => from.value.slice(0, 7));

const summaryEntries = computed(() => Object.entries(result.value.summary ?? {}));

const summaryLabels: Record<string, string> = {
    invoice_count: 'Facturas',
    subtotal: 'Subtotal',
    discount_total: 'Descuentos',
    tax_total: 'ITBIS',
    tip_total: 'Propina',
    total: 'Total',
    session_count: 'Turnos',
    expected_amount: 'Esperado',
    counted_amount: 'Contado',
    difference: 'Diferencia',
};

function isMoney(key: string): boolean {
    return !['invoice_count', 'session_count'].includes(key);
}

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo cargar el reporte.')
        : 'No se pudo cargar el reporte.';
}

function formatCell(value: unknown, col: ReportTab['columns'][number]): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    if (col.date) {
        return String(value).slice(0, 10);
    }
    if (col.money) {
        return `RD$ ${Number(value).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    return String(value);
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        result.value = await fetchReport(activeTab.value.path, { from: from.value, to: to.value });
    } catch (exception) {
        error.value = message(exception);
        result.value = { rows: [] };
    } finally {
        loading.value = false;
    }
}

async function download(kind: 'csv' | 'xlsx' | 'pdf' | '606' | '607' | '608'): Promise<void> {
    downloading.value = kind;
    error.value = null;
    try {
        if (kind === 'csv' || kind === 'xlsx' || kind === 'pdf') {
            await downloadReport(
                `sales/export.${kind}`,
                { from: from.value, to: to.value },
                `ventas-${from.value}-${to.value}.${kind}`,
            );
        } else {
            await downloadReport(`dgii/${kind}`, { period: period.value }, `${kind}-${period.value}.txt`);
        }
    } catch (exception) {
        error.value = message(exception);
    } finally {
        downloading.value = null;
    }
}

function selectTab(tab: ReportTab): void {
    activeTab.value = tab;
    void load();
}

function printReport(): void {
    window.print();
}

watch([from, to], () => void load());

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-6xl">
            <header class="border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Análisis</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Reportes</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Ventas, caja, desgloses y formatos fiscales DGII (606/607/608) por período.
                </p>
            </header>

            <div class="mt-6 flex flex-wrap items-end gap-4 print:hidden">
                <label class="grid gap-1 text-sm font-semibold"
                    >Desde
                    <input
                        v-model="from"
                        type="date"
                        class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                    />
                </label>
                <label class="grid gap-1 text-sm font-semibold"
                    >Hasta
                    <input
                        v-model="to"
                        type="date"
                        class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                    />
                </label>
                <div class="ml-auto flex flex-wrap gap-2">
                    <button
                        type="button"
                        :disabled="downloading === 'csv'"
                        class="min-h-12 rounded-lg border border-[#3525cd] px-4 text-base font-bold text-[#3525cd] disabled:opacity-60"
                        @click="download('csv')"
                    >
                        Exportar CSV
                    </button>
                    <button
                        type="button"
                        :disabled="downloading === 'xlsx'"
                        class="min-h-12 rounded-lg border border-[#006c49] px-4 text-base font-bold text-[#006c49] disabled:opacity-60"
                        @click="download('xlsx')"
                    >
                        Excel
                    </button>
                    <button
                        type="button"
                        :disabled="downloading === 'pdf'"
                        class="min-h-12 rounded-lg border border-[#ba1a1a] px-4 text-base font-bold text-[#ba1a1a] disabled:opacity-60"
                        @click="download('pdf')"
                    >
                        PDF
                    </button>
                    <button
                        v-for="fmt in ['606', '607', '608']"
                        :key="fmt"
                        type="button"
                        :disabled="downloading === fmt"
                        class="min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-base font-bold text-[#464555] disabled:opacity-60"
                        @click="download(fmt as '606' | '607' | '608')"
                    >
                        DGII {{ fmt }}
                    </button>
                    <button
                        type="button"
                        class="min-h-12 rounded-lg bg-[#3525cd] px-4 text-base font-bold text-white shadow-sm"
                        @click="printReport"
                    >
                        Imprimir
                    </button>
                </div>
            </div>

            <div v-if="error" class="mt-4 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <nav class="mt-6 flex flex-wrap gap-2 border-b border-[#e4e1ee] print:hidden" aria-label="Tipos de reporte">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="min-h-12 rounded-t-lg px-4 text-base font-semibold"
                    :class="
                        activeTab.key === tab.key
                            ? 'bg-white text-[#3525cd] shadow-sm'
                            : 'text-[#464555] hover:bg-white/60'
                    "
                    @click="selectTab(tab)"
                >
                    {{ tab.label }}
                </button>
            </nav>

            <section
                v-if="summaryEntries.length > 0"
                class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                aria-label="Resumen"
            >
                <div
                    v-for="[key, value] in summaryEntries"
                    :key="key"
                    class="rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#464555]">
                        {{ summaryLabels[key] ?? key }}
                    </p>
                    <p class="mt-1 font-numeric text-2xl font-bold text-[#3525cd]">
                        {{
                            isMoney(key)
                                ? 'RD$ ' + Number(value).toLocaleString('es-DO', { minimumFractionDigits: 2 })
                                : value
                        }}
                    </p>
                </div>
            </section>

            <div v-if="loading" class="mt-6 h-64 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            <div v-else class="mt-6 overflow-x-auto rounded-2xl border border-[#c7c4d8] bg-white">
                <table class="w-full text-left text-base">
                    <thead class="border-b border-[#e4e1ee] text-sm uppercase tracking-wider text-[#464555]">
                        <tr>
                            <th v-for="col in activeTab.columns" :key="col.key" class="p-3">{{ col.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="result.rows.length === 0">
                            <td class="p-4 text-[#464555]" :colspan="activeTab.columns.length">
                                Sin datos en el período seleccionado.
                            </td>
                        </tr>
                        <tr v-for="(row, index) in result.rows" :key="index" class="border-b border-[#f0ecf9]">
                            <td
                                v-for="col in activeTab.columns"
                                :key="col.key"
                                class="p-3"
                                :class="col.money ? 'text-right font-semibold' : ''"
                            >
                                {{ formatCell(row[col.key], col) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</template>
