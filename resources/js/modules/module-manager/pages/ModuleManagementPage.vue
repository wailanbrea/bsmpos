<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useModuleStore } from '../stores/modules';
import { disableModule, enableModule } from '../services';
import type { SystemModule } from '../types';

const store = useModuleStore();
const error = ref<string | null>(null);
const busy = ref<string | null>(null);

const categories = computed<[string, SystemModule[]][]>(() => {
    const groups = new Map<string, SystemModule[]>();
    for (const module of store.modules) {
        const list = groups.get(module.category) ?? [];
        list.push(module);
        groups.set(module.category, list);
    }
    return [...groups.entries()];
});

const categoryLabels: Record<string, string> = {
    core: 'Núcleo',
    sales: 'Ventas',
    catalog: 'Catálogo',
    inventory: 'Inventario',
    purchasing: 'Compras',
    restaurant: 'Restaurante',
    services: 'Servicios',
    workshop: 'Taller',
    finance: 'Finanzas',
    operations: 'Operaciones',
    hr: 'Personal',
    general: 'General',
};

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function toggle(module: SystemModule): Promise<void> {
    if (module.is_core || busy.value) {
        return;
    }

    busy.value = module.code;
    error.value = null;

    try {
        const enabled = module.is_enabled ? await disableModule(module.code) : await enableModule(module.code);
        store.setEnabled(enabled);
    } catch (exception) {
        error.value = message(exception);
    } finally {
        busy.value = null;
    }
}

onMounted(() => {
    void store.loadModules(true);
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
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Módulos del sistema</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Activa solo lo que tu negocio necesita. Desactivar un módulo nunca borra datos históricos.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div v-if="store.loading && !store.loaded" class="mt-6 grid gap-4">
                <div v-for="row in 4" :key="row" class="h-20 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            </div>

            <div v-else class="mt-6 space-y-8">
                <section v-for="[category, modules] in categories" :key="category">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-[.12em] text-[#464555]">
                        {{ categoryLabels[category] ?? category }}
                    </h2>
                    <ul class="grid gap-3 md:grid-cols-2">
                        <li
                            v-for="module in modules"
                            :key="module.code"
                            class="flex items-start justify-between gap-4 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                        >
                            <div>
                                <p class="flex items-center gap-2 font-bold">
                                    {{ module.name }}
                                    <span
                                        v-if="module.is_core"
                                        class="rounded-full bg-[#e2dfff] px-2 py-0.5 text-xs font-semibold text-[#3323cc]"
                                        >Núcleo</span
                                    >
                                </p>
                                <p class="mt-1 text-sm text-[#464555]">{{ module.description }}</p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="module.is_enabled"
                                :aria-label="`${module.is_enabled ? 'Desactivar' : 'Activar'} ${module.name}`"
                                :disabled="module.is_core || busy === module.code"
                                class="relative mt-1 h-7 w-12 shrink-0 rounded-full transition disabled:opacity-50"
                                :class="module.is_enabled ? 'bg-[#006c49]' : 'bg-[#c7c4d8]'"
                                @click="toggle(module)"
                            >
                                <span
                                    class="absolute top-1 h-5 w-5 rounded-full bg-white transition-all"
                                    :class="module.is_enabled ? 'left-6' : 'left-1'"
                                />
                            </button>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </main>
</template>
