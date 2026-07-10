<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '../../../lib/api';
import type { ApiEnvelope } from '../../auth/types';
import type { AuditLog, AuditPagination } from '../types';

interface AuditLogResponse extends ApiEnvelope<AuditLog[]> {
    meta: {
        pagination: AuditPagination;
    };
}

const logs = ref<AuditLog[]>([]);
const isLoading = ref(true);
const errorMessage = ref<string | null>(null);
const selectedLog = ref<AuditLog | null>(null);
const pagination = ref<AuditPagination | null>(null);
const filters = ref({ module: '', from: '', to: '' });

const hasFilters = computed(() => Object.values(filters.value).some((value) => value !== ''));

function actionLabel(action: string): string {
    const labels: Record<string, string> = {
        'company.created': 'Compañía creada',
        'access.role.created': 'Rol creado',
        'auth.login': 'Inicio de sesión',
        'auth.logout': 'Cierre de sesión',
    };

    return labels[action] ?? action.replaceAll('.', ' · ');
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('es-DO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function requestError(error: unknown): string {
    if (axios.isAxiosError(error)) {
        return error.response?.data?.error?.message ?? 'No se pudo cargar la bitácora. Intente de nuevo.';
    }

    return 'No se pudo cargar la bitácora. Intente de nuevo.';
}

async function loadLogs(page = 1): Promise<void> {
    isLoading.value = true;
    errorMessage.value = null;
    selectedLog.value = null;

    try {
        const response = await api.get<AuditLogResponse>('/audit-logs', {
            params: {
                module: filters.value.module || undefined,
                from: filters.value.from || undefined,
                to: filters.value.to || undefined,
                page,
            },
        });

        logs.value = response.data.data;
        pagination.value = response.data.meta.pagination;
    } catch (error) {
        logs.value = [];
        pagination.value = null;
        errorMessage.value = requestError(error);
    } finally {
        isLoading.value = false;
    }
}

function resetFilters(): void {
    filters.value = { module: '', from: '', to: '' };
    void loadLogs();
}

onMounted(() => {
    void loadLogs();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface px-4 py-6 text-kinetic-ink md:px-8 md:py-10">
        <div class="mx-auto max-w-6xl">
            <RouterLink
                to="/"
                class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd] hover:underline"
            >
                ← Volver al panel
            </RouterLink>

            <header class="mt-4 border-b border-[#c7c4d8] pb-6 md:flex md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#3525cd]">Seguridad operativa</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight md:text-4xl">Bitácora de auditoría</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#464555]">
                        Revise qué cambió, quién lo hizo y cuándo. Los datos sensibles nunca se muestran en esta vista.
                    </p>
                </div>
                <p v-if="pagination" class="mt-4 text-sm font-semibold text-[#464555] md:mt-0">
                    {{ pagination.total }} {{ pagination.total === 1 ? 'evento' : 'eventos' }}
                </p>
            </header>

            <form
                class="mt-6 grid gap-4 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm md:grid-cols-[1.2fr_1fr_1fr_auto] md:items-end"
                @submit.prevent="loadLogs()"
            >
                <label class="grid gap-2 text-sm font-semibold">
                    Módulo
                    <input
                        v-model.trim="filters.module"
                        class="min-h-11 rounded-lg border border-[#c7c4d8] px-3 font-normal outline-none transition focus:border-[#3525cd] focus:ring-2 focus:ring-[#4f46e5]/20"
                        name="module"
                        placeholder="Ej.: access, company"
                        autocomplete="off"
                    />
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Desde
                    <input
                        v-model="filters.from"
                        class="min-h-11 rounded-lg border border-[#c7c4d8] px-3 font-normal outline-none transition focus:border-[#3525cd] focus:ring-2 focus:ring-[#4f46e5]/20"
                        name="from"
                        type="date"
                    />
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Hasta
                    <input
                        v-model="filters.to"
                        class="min-h-11 rounded-lg border border-[#c7c4d8] px-3 font-normal outline-none transition focus:border-[#3525cd] focus:ring-2 focus:ring-[#4f46e5]/20"
                        name="to"
                        type="date"
                    />
                </label>
                <div class="flex gap-2">
                    <button
                        class="min-h-11 rounded-lg bg-[#3525cd] px-5 text-sm font-bold text-white shadow-[0_4px_10px_rgb(53_37_205_/_22%)] transition hover:bg-[#4f46e5] active:scale-[.98]"
                        type="submit"
                    >
                        Filtrar
                    </button>
                    <button
                        v-if="hasFilters"
                        class="min-h-11 rounded-lg px-3 text-sm font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                        type="button"
                        @click="resetFilters"
                    >
                        Limpiar
                    </button>
                </div>
            </form>

            <section class="mt-6" aria-live="polite">
                <div v-if="isLoading" class="grid gap-3" aria-label="Cargando auditoría">
                    <div v-for="item in 5" :key="item" class="h-20 animate-pulse rounded-xl bg-[#e4e1ee]" />
                </div>

                <div
                    v-else-if="errorMessage"
                    class="rounded-2xl border border-[#ffb4ab] bg-[#ffdad6] p-6 text-[#93000a]"
                >
                    <h2 class="font-bold">No se pudo consultar la bitácora</h2>
                    <p class="mt-1 text-sm">{{ errorMessage }}</p>
                    <button
                        class="mt-4 min-h-11 rounded-lg border border-current px-4 text-sm font-bold"
                        type="button"
                        @click="loadLogs()"
                    >
                        Reintentar
                    </button>
                </div>

                <div
                    v-else-if="logs.length === 0"
                    class="rounded-2xl border border-dashed border-[#c7c4d8] bg-[#f5f2ff] px-6 py-12 text-center"
                >
                    <h2 class="text-lg font-bold">No hay eventos para estos filtros</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#464555]">
                        Los cambios relevantes de esta compañía se registrarán aquí.
                    </p>
                    <button
                        v-if="hasFilters"
                        class="mt-4 min-h-11 font-bold text-[#3525cd]"
                        type="button"
                        @click="resetFilters"
                    >
                        Quitar filtros
                    </button>
                </div>

                <div v-else class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <ol class="overflow-hidden rounded-2xl border border-[#c7c4d8] bg-white">
                        <li v-for="log in logs" :key="log.id" class="border-b border-[#e4e1ee] last:border-b-0">
                            <button
                                class="group grid w-full min-h-20 grid-cols-[20px_1fr_auto] items-center gap-3 px-4 py-3 text-left transition hover:bg-[#f5f2ff] focus:bg-[#f5f2ff]"
                                type="button"
                                :aria-pressed="selectedLog?.id === log.id"
                                @click="selectedLog = log"
                            >
                                <span
                                    class="h-3 w-3 rounded-full border-4 border-[#e2dfff] bg-[#3525cd]"
                                    aria-hidden="true"
                                />
                                <span>
                                    <span class="block font-bold">{{ actionLabel(log.action) }}</span>
                                    <span class="mt-1 block text-sm text-[#464555]">
                                        {{ log.user?.name ?? 'Sistema' }} · {{ log.module }}
                                    </span>
                                </span>
                                <time class="text-right text-xs leading-5 text-[#464555]" :datetime="log.created_at">
                                    {{ formatDate(log.created_at) }}
                                </time>
                            </button>
                        </li>
                    </ol>

                    <aside
                        class="rounded-2xl border border-[#c7c4d8] bg-[#302f39] p-5 text-[#f3effc]"
                        aria-label="Detalle del evento"
                    >
                        <template v-if="selectedLog">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#c3c0ff]">
                                Detalle seleccionado
                            </p>
                            <h2 class="mt-2 text-xl font-bold">{{ actionLabel(selectedLog.action) }}</h2>
                            <dl class="mt-5 grid gap-4 text-sm">
                                <div>
                                    <dt class="text-[#c7c4d8]">Responsable</dt>
                                    <dd class="mt-1 font-semibold">{{ selectedLog.user?.name ?? 'Sistema' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[#c7c4d8]">Fecha y hora</dt>
                                    <dd class="mt-1 font-semibold">{{ formatDate(selectedLog.created_at) }}</dd>
                                </div>
                                <div v-if="selectedLog.ip">
                                    <dt class="text-[#c7c4d8]">Dirección IP</dt>
                                    <dd class="mt-1 font-mono text-xs">{{ selectedLog.ip }}</dd>
                                </div>
                                <div v-if="Object.keys(selectedLog.old_values).length > 0">
                                    <dt class="text-[#c7c4d8]">Datos anteriores</dt>
                                    <dd
                                        class="mt-2 overflow-x-auto rounded-lg bg-white/10 p-3 font-mono text-xs leading-5"
                                    >
                                        <pre>{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                                    </dd>
                                </div>
                                <div v-if="Object.keys(selectedLog.new_values).length > 0">
                                    <dt class="text-[#c7c4d8]">Datos nuevos</dt>
                                    <dd
                                        class="mt-2 overflow-x-auto rounded-lg bg-white/10 p-3 font-mono text-xs leading-5"
                                    >
                                        <pre>{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                                    </dd>
                                </div>
                            </dl>
                        </template>
                        <div v-else class="flex min-h-56 items-center">
                            <p class="text-sm leading-6 text-[#c7c4d8]">
                                Seleccione un evento para revisar los datos registrados.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>

            <nav
                v-if="pagination && pagination.last_page > 1"
                class="mt-6 flex items-center justify-end gap-3"
                aria-label="Paginación"
            >
                <button
                    class="min-h-11 rounded-lg border border-[#c7c4d8] px-4 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-45"
                    type="button"
                    :disabled="pagination.current_page === 1"
                    @click="loadLogs(pagination.current_page - 1)"
                >
                    Anterior
                </button>
                <span class="text-sm text-[#464555]"
                    >Página {{ pagination.current_page }} de {{ pagination.last_page }}</span
                >
                <button
                    class="min-h-11 rounded-lg border border-[#c7c4d8] px-4 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-45"
                    type="button"
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="loadLogs(pagination.current_page + 1)"
                >
                    Siguiente
                </button>
            </nav>
        </div>
    </main>
</template>
