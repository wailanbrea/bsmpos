<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '../../../lib/api';
import type { ApiEnvelope } from '../../auth/types';
import type { ManagedBranch } from '../types';

interface BranchForm {
    name: string;
    code: string;
    phone: string;
    address: string;
    is_active: boolean;
}

const branches = ref<ManagedBranch[]>([]);
const selected = ref<ManagedBranch | null>(null);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const form = ref<BranchForm>(emptyForm());
const editing = computed(() => selected.value !== null);

function emptyForm(): BranchForm {
    return { name: '', code: '', phone: '', address: '', is_active: true };
}

function reset(branch: ManagedBranch | null = null): void {
    selected.value = branch;
    form.value = branch
        ? {
              name: branch.name,
              code: branch.code,
              phone: branch.phone ?? '',
              address: branch.address ?? '',
              is_active: branch.is_active,
          }
        : emptyForm();
    error.value = null;
}

function message(errorValue: unknown): string {
    return axios.isAxiosError(errorValue)
        ? (errorValue.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const response = await api.get<ApiEnvelope<ManagedBranch[]>>('/branches');
        branches.value = response.data.data;
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function save(): Promise<void> {
    if (selected.value?.is_main && !form.value.is_active) {
        error.value = 'La sucursal principal no se puede desactivar.';
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const details = {
            name: form.value.name,
            code: form.value.code,
            phone: form.value.phone || null,
            address: form.value.address || null,
        };

        if (selected.value) {
            await api.patch(`/branches/${selected.value.id}`, { ...details, is_active: form.value.is_active });
        } else {
            await api.post('/branches', details);
        }

        await load();
        reset();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

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
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Configuración</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Sucursales</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Mantén los puntos de operación de la empresa. La sucursal principal siempre debe permanecer activa.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }} <button class="ml-3 min-h-11 font-bold underline" @click="load">Reintentar</button>
            </div>
            <div v-else-if="loading" class="mt-6 grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
                <div class="h-96 animate-pulse rounded-2xl bg-[#e4e1ee]" />
                <div class="h-96 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            </div>
            <div v-else class="mt-6 grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
                <section class="rounded-2xl border border-[#c7c4d8] bg-white p-3">
                    <div class="flex items-center justify-between gap-3 px-2 pb-3">
                        <h2 class="font-bold">Puntos de operación</h2>
                        <button
                            class="min-h-11 rounded-lg px-3 text-sm font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                            @click="reset()"
                        >
                            Nueva sucursal
                        </button>
                    </div>
                    <p v-if="branches.length === 0" class="p-5 text-sm text-[#464555]">
                        No hay sucursales configuradas.
                    </p>
                    <button
                        v-for="branch in branches"
                        :key="branch.id"
                        class="mb-2 w-full rounded-xl border p-4 text-left transition hover:border-[#4f46e5]"
                        :class="selected?.id === branch.id ? 'border-[#3525cd] bg-[#f5f2ff]' : 'border-[#e4e1ee]'"
                        @click="reset(branch)"
                    >
                        <span class="flex items-center justify-between gap-3 font-bold">
                            {{ branch.name }}
                            <span
                                class="rounded-full px-2 py-1 text-xs"
                                :class="
                                    branch.is_active ? 'bg-[#d9f7df] text-[#006c49]' : 'bg-[#ffdad6] text-[#93000a]'
                                "
                            >
                                {{ branch.is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </span>
                        <span class="mt-1 block text-sm text-[#464555]">{{ branch.code }}</span>
                        <span v-if="branch.is_main" class="mt-2 inline-block text-xs font-semibold text-[#3525cd]"
                            >Sucursal principal</span
                        >
                    </button>
                </section>

                <form class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm" @submit.prevent="save">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.12em] text-[#006c49]">
                            {{ editing ? 'Edición de sucursal' : 'Nueva sucursal' }}
                        </p>
                        <h2 class="mt-1 text-xl font-bold">
                            {{ editing ? selected?.name : 'Agrega un punto de operación' }}
                        </h2>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2 text-sm font-semibold"
                            >Nombre
                            <input
                                v-model.trim="form.name"
                                required
                                maxlength="150"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-3 font-normal"
                            />
                        </label>
                        <label class="grid gap-2 text-sm font-semibold"
                            >Código
                            <input
                                v-model.trim="form.code"
                                required
                                maxlength="30"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-3 font-mono text-sm font-normal uppercase"
                            />
                        </label>
                    </div>
                    <label class="mt-4 grid gap-2 text-sm font-semibold"
                        >Teléfono
                        <input
                            v-model.trim="form.phone"
                            type="tel"
                            maxlength="30"
                            class="min-h-11 rounded-lg border border-[#c7c4d8] px-3 font-normal"
                        />
                    </label>
                    <label class="mt-4 grid gap-2 text-sm font-semibold"
                        >Dirección
                        <textarea
                            v-model.trim="form.address"
                            maxlength="2000"
                            class="min-h-24 rounded-lg border border-[#c7c4d8] p-3 font-normal"
                        />
                    </label>
                    <label v-if="editing" class="mt-5 flex min-h-11 items-center gap-3 text-sm font-semibold">
                        <input v-model="form.is_active" type="checkbox" :disabled="selected?.is_main" />
                        Sucursal activa
                    </label>
                    <p v-if="selected?.is_main" class="mt-2 text-sm text-[#464555]">
                        La sucursal principal no se puede desactivar.
                    </p>
                    <button
                        class="mt-6 min-h-11 rounded-lg bg-[#3525cd] px-5 text-sm font-bold text-white shadow-sm disabled:opacity-60"
                        :disabled="saving"
                        type="submit"
                    >
                        {{ saving ? 'Guardando…' : editing ? 'Guardar cambios' : 'Crear sucursal' }}
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
