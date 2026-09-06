<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../../../lib/api';
import type { ApiEnvelope, Company } from '../types';
import { useSessionStore } from '../stores/session';

const router = useRouter();
const session = useSessionStore();
const selectedCompanyId = ref(session.company?.id ?? '');
const selectedBranchId = ref(session.branch?.id ?? '');
const companyName = ref('');
const branchName = ref('Sucursal principal');
const branchCode = ref('PRINCIPAL');
const isLoading = ref(false);
const isCreatingCompany = ref(false);
const errorMessage = ref<string | null>(null);

const companies = computed(() => session.user?.companies.filter((company) => company.is_active) ?? []);
const selectedCompany = computed(
    () => companies.value.find((company) => company.id === selectedCompanyId.value) ?? null,
);
const availableBranches = computed(() => selectedCompany.value?.branches.filter((branch) => branch.is_active) ?? []);

watch(selectedCompanyId, () => {
    selectedBranchId.value =
        availableBranches.value.find((branch) => branch.is_main)?.id ?? availableBranches.value[0]?.id ?? '';
});

onMounted(async () => {
    isLoading.value = true;
    try {
        await session.refreshUser();
        selectedCompanyId.value = session.company?.id ?? companies.value[0]?.id ?? '';
        selectedBranchId.value =
            session.branch?.id ?? availableBranches.value.find((branch) => branch.is_main)?.id ?? '';
    } catch {
        await session.logout();
        await router.replace({ name: 'login' });
    } finally {
        isLoading.value = false;
    }
});

async function continueToDashboard(): Promise<void> {
    errorMessage.value = null;
    try {
        session.selectContext(selectedCompanyId.value, selectedBranchId.value);
        await router.push({ name: 'dashboard' });
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Selecciona una compañía y sucursal válidas.';
    }
}

async function createCompany(): Promise<void> {
    isCreatingCompany.value = true;
    errorMessage.value = null;
    try {
        const response = await api.post<ApiEnvelope<Company>>('/companies', {
            name: companyName.value,
            branch_name: branchName.value,
            branch_code: branchCode.value,
        });
        await session.refreshUser();
        const company = response.data.data;
        session.selectContext(company.id, company.branches[0].id);
        await router.push({ name: 'dashboard' });
    } catch (error) {
        errorMessage.value = axios.isAxiosError(error)
            ? (error.response?.data?.error?.message ?? 'No se pudo crear la compañía.')
            : 'No se pudo crear la compañía.';
    } finally {
        isCreatingCompany.value = false;
    }
}
</script>

<template>
    <main class="context-shell">
        <header class="context-header">
            <div class="auth-brand"><span class="auth-brand__mark" aria-hidden="true">B</span><span>BSM-POS</span></div>
            <button
                class="quiet-action"
                type="button"
                @click="session.logout().then(() => router.push({ name: 'login' }))"
            >
                Cerrar sesión
            </button>
        </header>

        <section v-if="isLoading" class="context-card" aria-live="polite">Preparando tu contexto de trabajo…</section>

        <section v-else-if="companies.length" class="context-card" aria-labelledby="context-title">
            <p class="auth-kicker">Pase de turno</p>
            <h1 id="context-title">Selecciona dónde vas a operar.</h1>
            <p class="context-card__description">
                Esta selección acompaña cada operación y evita cruces entre empresas.
            </p>
            <p v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</p>

            <div class="context-rail" aria-hidden="true"><span /><span /></div>
            <label class="field-label" for="company">Compañía</label>
            <select id="company" v-model="selectedCompanyId" class="field-control">
                <option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option>
            </select>
            <label class="field-label" for="branch">Sucursal</label>
            <select id="branch" v-model="selectedBranchId" class="field-control">
                <option v-for="branch in availableBranches" :key="branch.id" :value="branch.id">
                    {{ branch.name }} · {{ branch.code }}
                </option>
            </select>
            <button class="primary-action" type="button" @click="continueToDashboard">Continuar al panel</button>
        </section>

        <section v-else class="context-card" aria-labelledby="company-title">
            <p class="auth-kicker">Primer paso</p>
            <h1 id="company-title">Crea tu compañía.</h1>
            <p class="context-card__description">
                Agrega lo esencial ahora; los datos fiscales se completan en Configuración.
            </p>
            <p v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</p>
            <form class="auth-form" @submit.prevent="createCompany">
                <label class="field-label" for="company-name">Nombre comercial</label>
                <input
                    id="company-name"
                    v-model="companyName"
                    class="field-control"
                    autocomplete="organization"
                    required
                />
                <label class="field-label" for="branch-name">Sucursal principal</label>
                <input id="branch-name" v-model="branchName" class="field-control" required />
                <label class="field-label" for="branch-code">Código de sucursal</label>
                <input id="branch-code" v-model="branchCode" class="field-control" required />
                <button class="primary-action" type="submit" :disabled="isCreatingCompany">
                    {{ isCreatingCompany ? 'Creando compañía…' : 'Crear y continuar' }}
                </button>
            </form>
        </section>
    </main>
</template>
