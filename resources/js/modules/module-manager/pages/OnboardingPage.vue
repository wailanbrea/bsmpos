<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useModuleStore } from '../stores/modules';
import { completeOnboarding, fetchBusinessTypes } from '../services';
import type { BusinessType, PresetModule } from '../types';

const router = useRouter();
const store = useModuleStore();

const step = ref(1);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);

const businessTypes = ref<BusinessType[]>([]);
const selectedType = ref<string | null>(null);
const presetModules = ref<PresetModule[]>([]);
const chosen = ref<Set<string>>(new Set());

const selectedTypeName = computed(
    () => businessTypes.value.find((type) => type.code === selectedType.value)?.name ?? '',
);
const recommended = computed(() => presetModules.value.filter((m) => !m.is_core && m.is_recommended));
const optional = computed(() =>
    presetModules.value.filter((m) => !m.is_core && !m.enabled_by_default && !m.is_recommended),
);
const defaults = computed(() => presetModules.value.filter((m) => m.enabled_by_default && !m.is_core));

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function loadTypes(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const data = await fetchBusinessTypes();
        businessTypes.value = data.business_types;
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function chooseType(code: string): Promise<void> {
    selectedType.value = code;
    error.value = null;
    try {
        const data = await fetchBusinessTypes(code);
        presetModules.value = data.modules ?? [];
        chosen.value = new Set(data.modules?.filter((m) => m.is_recommended).map((m) => m.code));
        step.value = 2;
    } catch (exception) {
        error.value = message(exception);
    }
}

function toggle(code: string): void {
    if (chosen.value.has(code)) {
        chosen.value.delete(code);
    } else {
        chosen.value.add(code);
    }
    chosen.value = new Set(chosen.value);
}

async function finish(): Promise<void> {
    if (!selectedType.value) {
        return;
    }

    saving.value = true;
    error.value = null;
    try {
        const enabled = await completeOnboarding(selectedType.value, [...chosen.value]);
        store.setEnabled(enabled);
        store.hasBusinessType = true;
        await router.push('/');
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    void loadTypes();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-4xl">
            <header class="border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">
                    Configuración inicial · Paso {{ step }} de 2
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    {{ step === 1 ? '¿Cuál es tu tipo de negocio?' : `Módulos para ${selectedTypeName}` }}
                </h1>
                <p class="mt-2 text-sm text-[#464555]">
                    {{
                        step === 1
                            ? 'Activaremos automáticamente los módulos recomendados según tu giro.'
                            : 'Confirma los módulos recomendados y agrega los opcionales que necesites.'
                    }}
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div v-if="loading" class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="card in 6" :key="card" class="h-28 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            </div>

            <section v-else-if="step === 1" class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="type in businessTypes"
                    :key="type.code"
                    type="button"
                    class="rounded-2xl border border-[#c7c4d8] bg-white p-4 text-left shadow-sm transition hover:border-[#4f46e5] active:scale-[.98]"
                    @click="chooseType(type.code)"
                >
                    <p class="font-bold">{{ type.name }}</p>
                    <p class="mt-1 text-sm text-[#464555]">{{ type.description }}</p>
                </button>
            </section>

            <section v-else class="mt-6">
                <div class="rounded-2xl border border-[#c7c4d8] bg-[#f5f2ff] p-4">
                    <p class="text-sm font-bold text-[#006c49]">Se activarán por defecto</p>
                    <p class="mt-1 text-sm text-[#464555]">
                        {{ defaults.map((m) => m.name).join(', ') || 'Módulos básicos del núcleo.' }}
                    </p>
                </div>

                <div v-if="recommended.length > 0" class="mt-6">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">Recomendados</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <li v-for="module in recommended" :key="module.code">
                            <label
                                class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border bg-white p-4 shadow-sm"
                                :class="chosen.has(module.code) ? 'border-[#3525cd]' : 'border-[#e4e1ee]'"
                            >
                                <span class="font-semibold">{{ module.name }}</span>
                                <input
                                    type="checkbox"
                                    :checked="chosen.has(module.code)"
                                    class="h-5 w-5"
                                    @change="toggle(module.code)"
                                />
                            </label>
                        </li>
                    </ul>
                </div>

                <div v-if="optional.length > 0" class="mt-6">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">Opcionales</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <li v-for="module in optional" :key="module.code">
                            <label
                                class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border bg-white p-4 shadow-sm"
                                :class="chosen.has(module.code) ? 'border-[#3525cd]' : 'border-[#e4e1ee]'"
                            >
                                <span class="font-semibold">{{ module.name }}</span>
                                <input
                                    type="checkbox"
                                    :checked="chosen.has(module.code)"
                                    class="h-5 w-5"
                                    @change="toggle(module.code)"
                                />
                            </label>
                        </li>
                    </ul>
                </div>

                <div class="mt-8 flex items-center justify-between gap-4">
                    <button
                        type="button"
                        class="min-h-11 rounded-lg px-4 text-sm font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                        @click="step = 1"
                    >
                        ← Cambiar tipo
                    </button>
                    <button
                        type="button"
                        class="min-h-11 rounded-lg bg-[#006c49] px-6 text-sm font-bold text-white shadow-sm disabled:opacity-60"
                        :disabled="saving"
                        @click="finish"
                    >
                        {{ saving ? 'Configurando…' : 'Finalizar configuración' }}
                    </button>
                </div>
            </section>
        </div>
    </main>
</template>
