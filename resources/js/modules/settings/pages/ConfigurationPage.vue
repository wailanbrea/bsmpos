<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSessionStore } from '../../auth/stores/session';
import { useModuleStore } from '../../module-manager/stores/modules';
import {
    createExchangeRate,
    createNcfSequence,
    createPaymentMethod,
    createTax,
    fetchCompanyProfile,
    fetchExchangeRates,
    fetchFiscalSettings,
    fetchNcfSequences,
    updateCompanyProfile,
    updateNcfSequence,
    updatePaymentMethod,
    updateTax,
} from '../services';
import SettingsGroupCard from '../components/SettingsGroupCard.vue';
import type { CompanyProfile, ExchangeRate, FiscalSettings, NcfSequence, PaymentMethod, Tax } from '../types';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const modules = useModuleStore();

type TabKey = 'empresa' | 'fiscal' | 'operativa' | 'hub';
const activeTab = ref<TabKey>('empresa');

// Estado Perfil Empresa
const company = ref<CompanyProfile | null>(null);
const savingCompany = ref(false);
const savedCompanyOk = ref(false);
const companyForm = ref({
    name: '',
    legal_name: '',
    tax_id_type: 'RNC' as 'RNC' | 'CEDULA',
    tax_id: '',
    phone: '',
    whatsapp: '',
    email: '',
    address: '',
    timezone: 'America/Santo_Domingo',
    currency_code: 'DOP',
});

// Estado Fiscal
const settings = ref<FiscalSettings | null>(null);
const sequences = ref<NcfSequence[]>([]);
const rates = ref<ExchangeRate[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const success = ref<string | null>(null);
const savingSeq = ref(false);
const savingRate = ref(false);

const rateForm = ref({ currency_code: 'USD', rate: 60.5, effective_date: new Date().toISOString().slice(0, 10) });

const seqForm = ref({
    document_type_code: 'B02',
    start_number: 1,
    end_number: 1000,
    expires_at: '',
    alert_threshold: 50,
});

// Modal Nuevo / Editar Impuesto
const showTaxModal = ref(false);
const editingTax = ref<Tax | null>(null);
const savingTax = ref(false);
const taxForm = ref({
    name: '',
    code: '',
    rate: 18,
    type: 'percentage',
    scope: 'both',
    is_inclusive: false,
    is_active: true,
});

// Modal Nuevo / Editar Método de Pago
const showPaymentModal = ref(false);
const editingPayment = ref<PaymentMethod | null>(null);
const savingPayment = ref(false);
const paymentForm = ref({
    name: '',
    code: '',
    requires_reference: false,
    is_active: true,
});

// Modal Editar Secuencia NCF
const showSeqEditModal = ref(false);
const editingSeq = ref<NcfSequence | null>(null);
const savingSeqEdit = ref(false);
const seqEditForm = ref({
    end_number: 1000,
    alert_threshold: 50,
    expires_at: '',
    is_active: true,
});

const documentTypes = computed(() => settings.value?.document_types ?? []);
const branchName = computed(() => session.branch?.name ?? 'Sucursal Principal');

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

function notifySuccess(msg: string) {
    success.value = msg;
    window.setTimeout(() => {
        if (success.value === msg) success.value = null;
    }, 3500);
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const [compData, fiscalData, seqData, rateData] = await Promise.all([
            fetchCompanyProfile(),
            fetchFiscalSettings(),
            fetchNcfSequences(),
            fetchExchangeRates(),
        ]);
        company.value = compData;
        companyForm.value = {
            name: compData.name || '',
            legal_name: compData.legal_name || '',
            tax_id_type: compData.tax_id_type || 'RNC',
            tax_id: compData.tax_id || '',
            phone: compData.phone || '',
            whatsapp: compData.whatsapp || '',
            email: compData.email || '',
            address: compData.address || '',
            timezone: compData.timezone || 'America/Santo_Domingo',
            currency_code: compData.currency_code || 'DOP',
        };
        settings.value = fiscalData;
        sequences.value = seqData;
        rates.value = rateData;
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function saveCompany(): Promise<void> {
    savingCompany.value = true;
    error.value = null;
    try {
        const updated = await updateCompanyProfile(companyForm.value);
        company.value = updated;
        savedCompanyOk.value = true;
        notifySuccess('Datos de la empresa actualizados exitosamente.');
        window.setTimeout(() => (savedCompanyOk.value = false), 2500);
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingCompany.value = false;
    }
}

// ---------------------------------------------------------------------
// Impuestos CRUD
// ---------------------------------------------------------------------
function openNewTaxModal() {
    editingTax.value = null;
    taxForm.value = {
        name: '',
        code: '',
        rate: 18,
        type: 'percentage',
        scope: 'both',
        is_inclusive: false,
        is_active: true,
    };
    showTaxModal.value = true;
}

function openEditTaxModal(tax: Tax) {
    editingTax.value = tax;
    taxForm.value = {
        name: tax.name,
        code: tax.code,
        rate: Number(tax.rate),
        type: tax.type || 'percentage',
        scope: tax.scope || 'both',
        is_inclusive: Boolean(tax.is_inclusive),
        is_active: Boolean(tax.is_active),
    };
    showTaxModal.value = true;
}

async function saveTaxModal() {
    savingTax.value = true;
    error.value = null;
    try {
        if (editingTax.value) {
            await updateTax(editingTax.value.id, {
                name: taxForm.value.name,
                rate: Number(taxForm.value.rate),
                type: taxForm.value.type,
                scope: taxForm.value.scope,
                is_inclusive: taxForm.value.is_inclusive,
                is_active: taxForm.value.is_active,
            });
            notifySuccess(`Impuesto ${taxForm.value.name} actualizado correctamente.`);
        } else {
            await createTax({
                name: taxForm.value.name,
                code: taxForm.value.code.toLowerCase().trim().replace(/[^a-z0-9_]/g, '_'),
                rate: Number(taxForm.value.rate),
                type: taxForm.value.type,
                scope: taxForm.value.scope,
                is_inclusive: taxForm.value.is_inclusive,
            });
            notifySuccess(`Impuesto ${taxForm.value.name} creado exitosamente.`);
        }
        showTaxModal.value = false;
        settings.value = await fetchFiscalSettings();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingTax.value = false;
    }
}

// ---------------------------------------------------------------------
// Métodos de Pago CRUD
// ---------------------------------------------------------------------
function openNewPaymentModal() {
    editingPayment.value = null;
    paymentForm.value = {
        name: '',
        code: '',
        requires_reference: false,
        is_active: true,
    };
    showPaymentModal.value = true;
}

function openEditPaymentModal(method: PaymentMethod) {
    editingPayment.value = method;
    paymentForm.value = {
        name: method.name,
        code: method.code,
        requires_reference: Boolean(method.requires_reference),
        is_active: Boolean(method.is_active),
    };
    showPaymentModal.value = true;
}

async function savePaymentModal() {
    savingPayment.value = true;
    error.value = null;
    try {
        if (editingPayment.value) {
            await updatePaymentMethod(editingPayment.value.id, {
                name: paymentForm.value.name,
                requires_reference: paymentForm.value.requires_reference,
                is_active: paymentForm.value.is_active,
            });
            notifySuccess(`Método de pago ${paymentForm.value.name} actualizado.`);
        } else {
            await createPaymentMethod({
                name: paymentForm.value.name,
                code: paymentForm.value.code.toLowerCase().trim().replace(/[^a-z0-9_]/g, '_'),
                requires_reference: paymentForm.value.requires_reference,
            });
            notifySuccess(`Método de pago ${paymentForm.value.name} creado.`);
        }
        showPaymentModal.value = false;
        settings.value = await fetchFiscalSettings();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingPayment.value = false;
    }
}

// ---------------------------------------------------------------------
// Secuencias NCF CRUD
// ---------------------------------------------------------------------
function openEditSeqModal(seq: NcfSequence) {
    editingSeq.value = seq;
    seqEditForm.value = {
        end_number: seq.end_number,
        alert_threshold: seq.alert_threshold || 50,
        expires_at: seq.expires_at || '',
        is_active: Boolean(seq.is_active),
    };
    showSeqEditModal.value = true;
}

async function saveSeqEditModal() {
    if (!editingSeq.value) return;
    savingSeqEdit.value = true;
    error.value = null;
    try {
        await updateNcfSequence(editingSeq.value.id, {
            end_number: Number(seqEditForm.value.end_number),
            alert_threshold: Number(seqEditForm.value.alert_threshold),
            expires_at: seqEditForm.value.expires_at || null,
            is_active: seqEditForm.value.is_active,
        });
        notifySuccess(`Secuencia ${editingSeq.value.document_type_code} actualizada correctamente.`);
        showSeqEditModal.value = false;
        sequences.value = await fetchNcfSequences();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingSeqEdit.value = false;
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
            alert_threshold: Number(seqForm.value.alert_threshold) || 50,
        });
        notifySuccess(`Secuencia ${seqForm.value.document_type_code} registrada exitosamente.`);
        sequences.value = await fetchNcfSequences();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingSeq.value = false;
    }
}

async function addRate(): Promise<void> {
    savingRate.value = true;
    error.value = null;
    try {
        await createExchangeRate({
            currency_code: rateForm.value.currency_code,
            rate: Number(rateForm.value.rate),
            effective_date: rateForm.value.effective_date,
        });
        notifySuccess(`Tasa de cambio ${rateForm.value.currency_code} actualizada a RD$ ${rateForm.value.rate}.`);
        rates.value = await fetchExchangeRates();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        savingRate.value = false;
    }
}

function setTab(tab: TabKey): void {
    activeTab.value = tab;
    void router.replace({ query: { ...route.query, tab } });
}

watch(
    () => route.query.tab,
    (val) => {
        if (val === 'empresa' || val === 'fiscal' || val === 'operativa' || val === 'hub') {
            activeTab.value = val;
        }
    },
    { immediate: true },
);

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-[#f8f9ff] p-4 text-[#0b1c30] md:p-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- CABECERA DEL CENTRO DE CONFIGURACIÓN -->
            <header class="rounded-2xl border border-[#e2e8f0] bg-white p-6 shadow-xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#4648d4]">
                            <span class="material-symbols-outlined text-[18px]">tune</span>
                            <span>Ajustes Generales del Sistema</span>
                            <span class="text-zinc-300">•</span>
                            <span class="text-zinc-500 font-normal normal-case">{{ company?.name || 'Empresa' }} ({{ branchName }})</span>
                        </div>
                        <h1 class="mt-1 text-2xl md:text-3xl font-bold tracking-tight text-[#0b1c30]">
                            Centro de Configuración
                        </h1>
                        <p class="mt-1 text-xs md:text-sm text-[#64748b]">
                            Personaliza los datos de tu empresa, comprobantes fiscales DGII (NCF/e-CF), impuestos, métodos de pago y periféricos.
                        </p>
                    </div>

                    <!-- ESTADO MODULAR E-CF -->
                    <div class="flex flex-wrap items-center gap-2 self-start md:self-auto">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                            :class="modules.canUse('electronic_invoice') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="modules.canUse('electronic_invoice') ? 'bg-emerald-500 animate-pulse' : 'bg-blue-500'" />
                            <span>{{ modules.canUse('electronic_invoice') ? 'e-CF DGII: Activo' : 'NCF Tradicional: Listo' }}</span>
                        </span>

                        <router-link
                            to="/configuracion/terminales"
                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition"
                        >
                            <span class="material-symbols-outlined text-[15px] text-[#4648d4]">devices</span>
                            <span>Terminales Windows</span>
                        </router-link>
                    </div>
                </div>

                <!-- NAVEGACIÓN POR PESTAÑAS (TABS) -->
                <div class="mt-6 flex border-b border-slate-200 gap-2 sm:gap-4 overflow-x-auto no-scrollbar">
                    <button
                        type="button"
                        class="flex items-center gap-2 border-b-2 px-3 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer shrink-0"
                        :class="activeTab === 'empresa' ? 'border-[#4648d4] text-[#4648d4]' : 'border-transparent text-[#64748b] hover:text-[#0b1c30]'"
                        @click="setTab('empresa')"
                    >
                        <span class="material-symbols-outlined text-[18px]">business</span>
                        <span>Empresa & Perfil</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 border-b-2 px-3 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer shrink-0"
                        :class="activeTab === 'fiscal' ? 'border-[#4648d4] text-[#4648d4]' : 'border-transparent text-[#64748b] hover:text-[#0b1c30]'"
                        @click="setTab('fiscal')"
                    >
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        <span>Fiscal & Comprobantes</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 border-b-2 px-3 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer shrink-0"
                        :class="activeTab === 'operativa' ? 'border-[#4648d4] text-[#4648d4]' : 'border-transparent text-[#64748b] hover:text-[#0b1c30]'"
                        @click="setTab('operativa')"
                    >
                        <span class="material-symbols-outlined text-[18px]">storefront</span>
                        <span>Preferencias Operativas</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 border-b-2 px-3 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer shrink-0"
                        :class="activeTab === 'hub' ? 'border-[#4648d4] text-[#4648d4]' : 'border-transparent text-[#64748b] hover:text-[#0b1c30]'"
                        @click="setTab('hub')"
                    >
                        <span class="material-symbols-outlined text-[18px]">hub</span>
                        <span>Hub del Sistema</span>
                    </button>
                </div>
            </header>

            <!-- MENSAJES DE NOTIFICACIÓN GLOBAL -->
            <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 translate-y-1" leave-active-class="transition duration-150" leave-to-class="opacity-0">
                <div v-if="success" class="flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-800" role="alert">
                    <span class="material-symbols-outlined text-[18px] text-emerald-600">check_circle</span>
                    <span>{{ success }}</span>
                </div>
            </transition>

            <div v-if="error" class="flex items-center justify-between rounded-xl bg-red-50 border border-red-200 p-4 text-xs font-medium text-red-700" role="alert">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>{{ error }}</span>
                </div>
                <button type="button" class="text-red-500 hover:text-red-800" @click="error = null">✕</button>
            </div>

            <!-- CARGANDO -->
            <div v-if="loading" class="grid gap-4 md:grid-cols-2">
                <div v-for="row in 4" :key="row" class="h-44 animate-pulse rounded-2xl bg-white border border-[#e2e8f0]" />
            </div>

            <!-- CONTENIDO DE PESTAÑAS -->
            <div v-else class="space-y-6">
                <!-- ============================================================== -->
                <!-- PESTAÑA 1: EMPRESA & PERFIL                                    -->
                <!-- ============================================================== -->
                <div v-if="activeTab === 'empresa'" class="space-y-6">
                    <section class="rounded-2xl border border-[#e2e8f0] bg-white p-6 shadow-xs space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#eff4ff] text-[#4648d4] flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[22px]">corporate_fare</span>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900">Perfil Comercial y Fiscal de la Empresa</h2>
                                    <p class="text-xs text-slate-500">Estos datos aparecen en la cabecera de las facturas impresas, tickets y reportes fiscales.</p>
                                </div>
                            </div>
                            <span v-if="savedCompanyOk" class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">check</span> Actualizado
                            </span>
                        </div>

                        <form class="space-y-4 text-xs" @submit.prevent="saveCompany">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Nombre Comercial de la Empresa *</label>
                                    <input
                                        v-model="companyForm.name"
                                        type="text"
                                        required
                                        placeholder="Ej. Taller AutoMax"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Razón Social Legal</label>
                                    <input
                                        v-model="companyForm.legal_name"
                                        type="text"
                                        placeholder="Ej. AutoMax Servicios SRL"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Tipo de Identificación Fiscal</label>
                                    <select
                                        v-model="companyForm.tax_id_type"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                        <option value="RNC">RNC (Empresa / Persona Jurídica)</option>
                                        <option value="CEDULA">Cédula de Identidad</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Número de RNC / Cédula</label>
                                    <input
                                        v-model="companyForm.tax_id"
                                        type="text"
                                        placeholder="Ej. 131000037"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Moneda Contable Base</label>
                                    <select
                                        v-model="companyForm.currency_code"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                        <option value="DOP">DOP - Peso Dominicano (RD$)</option>
                                        <option value="USD">USD - Dólar Estadounidense ($)</option>
                                        <option value="EUR">EUR - Euro (€)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Teléfono Principal</label>
                                    <input
                                        v-model="companyForm.phone"
                                        type="text"
                                        placeholder="Ej. (809) 555-0199"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">WhatsApp de Atención</label>
                                    <input
                                        v-model="companyForm.whatsapp"
                                        type="text"
                                        placeholder="Ej. (829) 555-0199"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Correo Electrónico Comercial</label>
                                    <input
                                        v-model="companyForm.email"
                                        type="email"
                                        placeholder="contacto@empresa.com"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Dirección Fiscal / Física Completa</label>
                                    <textarea
                                        v-model="companyForm.address"
                                        rows="2"
                                        placeholder="Calle, No., Sector, Ciudad, República Dominicana"
                                        class="w-full rounded-lg border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    />
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Zona Horaria del Negocio</label>
                                    <input
                                        v-model="companyForm.timezone"
                                        type="text"
                                        class="w-full h-10 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none focus:ring-1 focus:ring-[#4648d4]"
                                    >
                                    <p class="text-[11px] text-slate-500 mt-1">Predeterminada: America/Santo_Domingo (UTC-4).</p>
                                </div>
                            </div>

                            <div class="flex justify-end pt-3 border-t border-slate-100">
                                <button
                                    type="submit"
                                    :disabled="savingCompany"
                                    class="flex items-center gap-1.5 rounded-lg bg-[#4648d4] px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-[#393bb3] active:scale-95 disabled:opacity-60 transition cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[16px]">save</span>
                                    <span>{{ savingCompany ? 'Guardando datos…' : 'Guardar Datos de la Empresa' }}</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- ============================================================== -->
                <!-- PESTAÑA 2: FISCAL & COMPROBANTES                               -->
                <!-- ============================================================== -->
                <div v-if="activeTab === 'fiscal'" class="space-y-6">
                    <!-- BANNER SAAS E-CF INFORMATIVO -->
                    <div class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-[#eff4ff] p-4.5 shadow-2xs">
                        <span class="material-symbols-outlined text-[24px] text-[#4648d4] shrink-0">verified</span>
                        <div class="text-xs text-[#0b1c30] leading-relaxed">
                            <p class="font-bold text-[#4648d4] text-sm">Facturación Electrónica e-CF (DGII República Dominicana)</p>
                            <p class="mt-1 text-slate-600">
                                BSM-POS emite con <strong>NCF Tradicional</strong> (B01 Crédito Fiscal, B02 Consumidor Final, B04 Nota de Crédito, B14 Regímenes Especiales, B15 Gubernamental) y está preparado para <strong>e-CF (Ley 32-23)</strong> como servicio SaaS opcional bajo demanda (E31, E32, etc.).
                            </p>
                            <div class="mt-2.5 flex items-center gap-3">
                                <router-link
                                    to="/facturacion-electronica"
                                    class="inline-flex items-center gap-1 font-semibold text-[#4648d4] hover:underline"
                                >
                                    Ver monitor y simulador e-CF →
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- IMPUESTOS Y MÉTODOS DE PAGO -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Impuestos -->
                        <section class="rounded-2xl border border-[#e2e8f0] bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-4 pb-2 border-b border-[#f1f5f9]">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[20px] text-[#4648d4]">percent</span>
                                        <h2 class="text-sm font-bold text-[#0b1c30]">Impuestos de Ley (ITBIS)</h2>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg bg-[#eff4ff] px-2.5 py-1 text-xs font-semibold text-[#4648d4] hover:bg-[#e0e7ff] transition cursor-pointer"
                                        @click="openNewTaxModal"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">add</span>
                                        <span>Nuevo</span>
                                    </button>
                                </div>
                                <ul class="grid gap-2.5">
                                    <li
                                        v-for="tax in settings.taxes"
                                        :key="tax.id"
                                        class="flex items-center justify-between rounded-xl border border-slate-100 bg-[#fafafa] p-3 text-xs hover:border-slate-300 transition"
                                    >
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-bold text-slate-900">{{ tax.name }}</p>
                                                <span v-if="!tax.is_active" class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-bold">Inactivo</span>
                                            </div>
                                            <p class="text-slate-500 font-mono text-[11px] mt-0.5">{{ tax.code }} · {{ tax.scope }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm font-bold text-[#4648d4] bg-white border border-slate-200 px-2.5 py-1 rounded-lg">
                                                {{ Number(tax.rate) }}%
                                            </span>
                                            <button
                                                type="button"
                                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-[#4648d4] hover:bg-white flex items-center justify-center transition cursor-pointer"
                                                title="Editar impuesto"
                                                @click="openEditTaxModal(tax)"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </section>

                        <!-- Métodos de Pago -->
                        <section class="rounded-2xl border border-[#e2e8f0] bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-4 pb-2 border-b border-[#f1f5f9]">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[20px] text-[#4648d4]">payments</span>
                                        <h2 class="text-sm font-bold text-[#0b1c30]">Métodos de Pago Habilitados</h2>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg bg-[#eff4ff] px-2.5 py-1 text-xs font-semibold text-[#4648d4] hover:bg-[#e0e7ff] transition cursor-pointer"
                                        @click="openNewPaymentModal"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">add</span>
                                        <span>Nuevo</span>
                                    </button>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div
                                        v-for="method in settings.payment_methods"
                                        :key="method.id"
                                        class="flex items-center justify-between rounded-xl border border-slate-200 bg-[#fafafa] px-3 py-2.5 text-xs font-semibold text-slate-800"
                                    >
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="material-symbols-outlined text-[16px]" :class="method.is_active ? 'text-emerald-600' : 'text-slate-400'">
                                                {{ method.is_active ? 'check_circle' : 'cancel' }}
                                            </span>
                                            <span class="truncate">{{ method.name }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            class="w-6 h-6 rounded text-slate-400 hover:text-[#4648d4] flex items-center justify-center transition cursor-pointer shrink-0"
                                            title="Editar método de pago"
                                            @click="openEditPaymentModal(method)"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">edit</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-4 text-[11px] text-slate-500">
                                Los cobros en el terminal POS pueden realizarse con pago mixto (efectivo + tarjeta + transferencia).
                            </p>
                        </section>
                    </div>

                    <!-- SECUENCIAS NCF / E-CF -->
                    <section class="rounded-2xl border border-[#e2e8f0] bg-white p-5 shadow-xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#f1f5f9] pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[20px] text-[#4648d4]">confirmation_number</span>
                                <div>
                                    <h2 class="text-sm font-bold text-[#0b1c30]">Secuencias NCF y e-CF</h2>
                                    <p class="text-xs text-slate-500">Comprobantes fiscales autorizados por la DGII. Puedes ajustar números finales y umbrales de alerta.</p>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA DE SECUENCIAS -->
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-slate-200 bg-[#fafafa] text-slate-600 font-semibold uppercase tracking-wider">
                                    <tr>
                                        <th class="p-3">Tipo</th>
                                        <th class="p-3">Rango Numérico</th>
                                        <th class="p-3">Disponibles</th>
                                        <th class="p-3">Vencimiento</th>
                                        <th class="p-3">Estado</th>
                                        <th class="p-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="sequences.length === 0">
                                        <td class="p-4 text-slate-500 text-center" colspan="6">No hay secuencias fiscales configuradas.</td>
                                    </tr>
                                    <tr v-for="seq in sequences" :key="seq.id" class="border-b border-slate-100 hover:bg-slate-50 transition">
                                        <td class="p-3 font-mono font-bold text-slate-900">{{ seq.document_type_code }}</td>
                                        <td class="p-3 font-mono">{{ seq.start_number }} – {{ seq.end_number }}</td>
                                        <td class="p-3">
                                            <span
                                                class="rounded-full px-2.5 py-0.5 font-bold"
                                                :class="seq.remaining <= seq.alert_threshold ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                                            >
                                                {{ seq.remaining }} restantes
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-600">{{ seq.expires_at ?? 'Sin vencimiento' }}</td>
                                        <td class="p-3">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold" :class="seq.is_active ? 'text-emerald-600' : 'text-slate-400'">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="seq.is_active ? 'bg-emerald-500' : 'bg-slate-400'" />
                                                {{ seq.is_active ? 'Activa' : 'Pausada' }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                                title="Ajustar secuencia"
                                                @click="openEditSeqModal(seq)"
                                            >
                                                <span class="material-symbols-outlined text-[14px]">tune</span>
                                                <span>Ajustar</span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- FORMULARIO AGREGAR SECUENCIA -->
                        <form
                            class="rounded-xl border border-slate-200 bg-[#fafafa] p-4 text-xs space-y-3"
                            @submit.prevent="addSequence"
                        >
                            <p class="font-bold text-slate-800 text-xs">Nueva Secuencia Autorizada por la DGII</p>
                            <div class="grid gap-3 sm:grid-cols-5">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Comprobante</label>
                                    <select
                                        v-model="seqForm.document_type_code"
                                        class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                    >
                                        <option v-for="dt in documentTypes" :key="dt.code" :value="dt.code">
                                            {{ dt.code }} — {{ dt.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Número Inicial</label>
                                    <input
                                        v-model="seqForm.start_number"
                                        type="number"
                                        min="1"
                                        class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Número Final</label>
                                    <input
                                        v-model="seqForm.end_number"
                                        type="number"
                                        min="2"
                                        class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Vence el</label>
                                    <input
                                        v-model="seqForm.expires_at"
                                        type="date"
                                        class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Umbral Alerta</label>
                                    <input
                                        v-model="seqForm.alert_threshold"
                                        type="number"
                                        min="1"
                                        class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                    >
                                </div>
                            </div>
                            <div class="flex justify-end pt-1">
                                <button
                                    type="submit"
                                    :disabled="savingSeq"
                                    class="rounded-lg bg-[#4648d4] px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#393bb3] active:scale-95 disabled:opacity-60 transition cursor-pointer"
                                >
                                    {{ savingSeq ? 'Guardando…' : '+ Registrar secuencia' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- TASAS DE CAMBIO MULTIMONEDA -->
                    <section class="rounded-2xl border border-[#e2e8f0] bg-white p-5 shadow-xs space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-[#f1f5f9] pb-3">
                            <span class="material-symbols-outlined text-[20px] text-[#4648d4]">currency_exchange</span>
                            <div>
                                <h2 class="text-sm font-bold text-[#0b1c30]">Tasas de Cambio Multimoneda</h2>
                                <p class="text-xs text-slate-500">Conversión en vivo para cobros en USD o EUR con vuelto automático en pesos dominicanos (RD$).</p>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="border-b border-slate-200 bg-[#fafafa] font-semibold uppercase text-slate-600">
                                        <tr>
                                            <th class="p-3">Moneda</th>
                                            <th class="p-3">Tasa (RD$)</th>
                                            <th class="p-3">Vigencia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="rates.length === 0">
                                            <td class="p-3 text-slate-500" colspan="3">Sin tasas registradas.</td>
                                        </tr>
                                        <tr v-for="rate in rates" :key="`${rate.currency_code}-${rate.effective_date}`" class="border-b border-slate-100">
                                            <td class="p-3 font-mono font-bold text-slate-900">{{ rate.currency_code }}</td>
                                            <td class="p-3 font-mono font-bold text-[#4648d4]">RD$ {{ Number(rate.rate).toFixed(2) }}</td>
                                            <td class="p-3 text-slate-600">{{ rate.effective_date }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <form class="rounded-xl border border-slate-200 bg-[#fafafa] p-4 text-xs space-y-3" @submit.prevent="addRate">
                                <p class="font-bold text-slate-800 text-xs">Actualizar Tasa de Cambio</p>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Moneda</label>
                                        <select
                                            v-model="rateForm.currency_code"
                                            class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                        >
                                            <option value="USD">USD ($)</option>
                                            <option value="EUR">EUR (€)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tasa en RD$</label>
                                        <input
                                            v-model="rateForm.rate"
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Vigente desde</label>
                                        <input
                                            v-model="rateForm.effective_date"
                                            type="date"
                                            class="w-full h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                                        >
                                    </div>
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button
                                        type="submit"
                                        :disabled="savingRate"
                                        class="rounded-lg bg-[#4648d4] px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#393bb3] active:scale-95 disabled:opacity-60 transition cursor-pointer"
                                    >
                                        {{ savingRate ? 'Guardando…' : '+ Registrar tasa' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>

                <!-- ============================================================== -->
                <!-- PESTAÑA 3: PREFERENCIAS OPERATIVAS                             -->
                <!-- ============================================================== -->
                <div v-else-if="activeTab === 'operativa'" class="grid gap-6 md:grid-cols-2">
                    <SettingsGroupCard
                        group="pos"
                        title="Punto de Venta (POS)"
                        description="Políticas de caja, venta sin existencias y comprobantes por defecto."
                    />
                    <SettingsGroupCard
                        group="invoice"
                        title="Facturación & Precios"
                        description="Tratamiento de ITBIS, moneda base y umbrales de alerta de NCF."
                    />
                    <SettingsGroupCard
                        group="inventory"
                        title="Inventario & Almacén"
                        description="Estrategia FEFO/FIFO, políticas de vencimiento y alertas críticas."
                    />
                    <SettingsGroupCard
                        group="printing"
                        title="Impresión de Tickets & Comandas"
                        description="Ancho de papel de 58mm/80mm y despacho automático al cobrar."
                    />
                    <SettingsGroupCard
                        group="backup"
                        title="Copias de Seguridad (Backup)"
                        description="Instantáneas programadas y políticas de retención de base de datos."
                    />
                </div>

                <!-- ============================================================== -->
                <!-- PESTAÑA 4: HUB DEL SISTEMA & ACCESOS RÁPIDOS                   -->
                <!-- ============================================================== -->
                <div v-else-if="activeTab === 'hub'" class="space-y-6">
                    <p class="text-xs text-slate-500">
                        Accede directamente a los módulos administrativos y de hardware de la empresa:
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <router-link
                            to="/configuracion/terminales"
                            class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition group"
                        >
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#4648d4] flex items-center justify-center mb-3 group-hover:scale-105 transition">
                                    <span class="material-symbols-outlined text-[22px]">devices</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Terminales Windows & Bluetooth</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Gestión del BSM-POS Windows Agent, escaneo PnP de impresoras térmicas, puertos COM y gaveta de dinero.
                                </p>
                            </div>
                            <span class="mt-4 text-xs font-semibold text-[#4648d4] flex items-center gap-1">
                                Abrir panel de terminales →
                            </span>
                        </router-link>

                        <router-link
                            to="/configuracion/modulos"
                            class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition group"
                        >
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                                    <span class="material-symbols-outlined text-[22px]">extension</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Gestor de Módulos SaaS</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Enciende o apaga módulos según el tipo de negocio (POS, Restaurante, Barbería, Taller, e-CF).
                                </p>
                            </div>
                            <span class="mt-4 text-xs font-semibold text-purple-600 flex items-center gap-1">
                                Gestionar catálogo de módulos →
                            </span>
                        </router-link>

                        <router-link
                            to="/facturacion-electronica"
                            class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition group"
                        >
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                                    <span class="material-symbols-outlined text-[22px]">verified</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Facturación Electrónica (e-CF)</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Monitor de estado DGII, reintentos asíncronos y contingencia fiscal electrónica.
                                </p>
                            </div>
                            <span class="mt-4 text-xs font-semibold text-emerald-600 flex items-center gap-1">
                                Ver monitor de emisión e-CF →
                            </span>
                        </router-link>

                        <router-link
                            to="/sucursales"
                            class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition group"
                        >
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                                    <span class="material-symbols-outlined text-[22px]">business</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Sucursales & Almacenes</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Administración de tiendas físicas, códigos de sucursal y asignación de administradores.
                                </p>
                            </div>
                            <span class="mt-4 text-xs font-semibold text-amber-600 flex items-center gap-1">
                                Administrar sucursales →
                            </span>
                        </router-link>

                        <router-link
                            to="/roles"
                            class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition group"
                        >
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                                    <span class="material-symbols-outlined text-[22px]">shield_person</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Roles & Permisos (RBAC)</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Control granular de acceso por módulo y protección de operaciones críticas de caja.
                                </p>
                            </div>
                            <span class="mt-4 text-xs font-semibold text-indigo-600 flex items-center gap-1">
                                Configurar roles de usuario →
                            </span>
                        </router-link>

                        <router-link
                            to="/seguridad"
                            class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition group"
                        >
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                                    <span class="material-symbols-outlined text-[22px]">security</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Seguridad & 2FA</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Doble factor de autenticación TOTP (Google/Microsoft Authenticator) y sesiones.
                                </p>
                            </div>
                            <span class="mt-4 text-xs font-semibold text-rose-600 flex items-center gap-1">
                                Ajustes de seguridad →
                            </span>
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL NUEVO / EDITAR IMPUESTO                                  -->
        <!-- ============================================================== -->
        <div v-if="showTaxModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 animate-fade-in">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ editingTax ? 'Editar Impuesto' : 'Nuevo Impuesto de Ley' }}
                    </h3>
                    <button type="button" class="text-slate-400 hover:text-slate-700" @click="showTaxModal = false">✕</button>
                </div>

                <form class="space-y-3 text-xs" @submit.prevent="saveTaxModal">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nombre descriptivo *</label>
                        <input
                            v-model="taxForm.name"
                            type="text"
                            required
                            placeholder="Ej. ITBIS 18%"
                            class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                        >
                    </div>

                    <div v-if="!editingTax">
                        <label class="block font-semibold text-slate-700 mb-1">Código único del sistema *</label>
                        <input
                            v-model="taxForm.code"
                            type="text"
                            required
                            placeholder="Ej. itbis_18"
                            class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs font-mono text-slate-900 focus:border-[#4648d4] focus:outline-none"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tasa (%) *</label>
                            <input
                                v-model="taxForm.rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                required
                                class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Ámbito</label>
                            <select
                                v-model="taxForm.scope"
                                class="w-full h-9 rounded-lg border border-slate-200 px-2.5 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                            >
                                <option value="both">Ambos (Productos y Servicios)</option>
                                <option value="product">Solo Productos</option>
                                <option value="service">Solo Servicios</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-2 border-t border-slate-100">
                        <label class="text-xs font-semibold text-slate-700 cursor-pointer">Impuesto Activo en Facturación</label>
                        <input v-model="taxForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#4648d4]">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer"
                            @click="showTaxModal = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="savingTax"
                            class="px-4 py-2 rounded-lg bg-[#4648d4] font-semibold text-white hover:bg-[#393bb3] disabled:opacity-60 cursor-pointer"
                        >
                            {{ savingTax ? 'Guardando…' : 'Guardar Impuesto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL NUEVO / EDITAR MÉTODO DE PAGO                            -->
        <!-- ============================================================== -->
        <div v-if="showPaymentModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 animate-fade-in">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ editingPayment ? 'Editar Método de Pago' : 'Nuevo Método de Pago' }}
                    </h3>
                    <button type="button" class="text-slate-400 hover:text-slate-700" @click="showPaymentModal = false">✕</button>
                </div>

                <form class="space-y-3 text-xs" @submit.prevent="savePaymentModal">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nombre visible en caja *</label>
                        <input
                            v-model="paymentForm.name"
                            type="text"
                            required
                            placeholder="Ej. Cheque Comercial, Zelle"
                            class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                        >
                    </div>

                    <div v-if="!editingPayment">
                        <label class="block font-semibold text-slate-700 mb-1">Código del método *</label>
                        <input
                            v-model="paymentForm.code"
                            type="text"
                            required
                            placeholder="Ej. cheque, zelle"
                            class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs font-mono text-slate-900 focus:border-[#4648d4] focus:outline-none"
                        >
                    </div>

                    <div class="space-y-2 py-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 cursor-pointer">Exigir número de referencia / voucher</label>
                            <input v-model="paymentForm.requires_reference" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#4648d4]">
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 cursor-pointer">Método activo en terminales POS</label>
                            <input v-model="paymentForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#4648d4]">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer"
                            @click="showPaymentModal = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="savingPayment"
                            class="px-4 py-2 rounded-lg bg-[#4648d4] font-semibold text-white hover:bg-[#393bb3] disabled:opacity-60 cursor-pointer"
                        >
                            {{ savingPayment ? 'Guardando…' : 'Guardar Método' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL AJUSTAR SECUENCIA NCF                                    -->
        <!-- ============================================================== -->
        <div v-if="showSeqEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 animate-fade-in">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Ajustar Secuencia NCF</h3>
                        <p class="text-xs text-[#4648d4] font-mono font-bold">{{ editingSeq?.document_type_code }}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-700" @click="showSeqEditModal = false">✕</button>
                </div>

                <form class="space-y-3 text-xs" @submit.prevent="saveSeqEditModal">
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <p class="text-[11px] text-slate-500">Número actual emitido: <strong>{{ editingSeq?.current_number }}</strong> (restantes: {{ editingSeq?.remaining }})</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nuevo número final del rango autorizado</label>
                        <input
                            v-model="seqEditForm.end_number"
                            type="number"
                            :min="(editingSeq?.current_number || 0) + 1"
                            required
                            class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Umbral alerta</label>
                            <input
                                v-model="seqEditForm.alert_threshold"
                                type="number"
                                min="1"
                                required
                                class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Fecha vencimiento</label>
                            <input
                                v-model="seqEditForm.expires_at"
                                type="date"
                                class="w-full h-9 rounded-lg border border-slate-200 px-3 text-xs text-slate-900 focus:border-[#4648d4] focus:outline-none"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-2 border-t border-slate-100">
                        <label class="text-xs font-semibold text-slate-700 cursor-pointer">Secuencia activa en facturación</label>
                        <input v-model="seqEditForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#4648d4]">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer"
                            @click="showSeqEditModal = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="savingSeqEdit"
                            class="px-4 py-2 rounded-lg bg-[#4648d4] font-semibold text-white hover:bg-[#393bb3] disabled:opacity-60 cursor-pointer"
                        >
                            {{ savingSeqEdit ? 'Guardando…' : 'Guardar Ajustes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</template>