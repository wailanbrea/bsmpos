<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { api } from '../../../lib/api';
import type { ApiEnvelope } from '../../auth/types';
import type { Permission, Role } from '../types';

const roles = ref<Role[]>([]);
const permissions = ref<Permission[]>([]);
const selected = ref<Role | null>(null);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const form = ref({ code: '', name: '', description: '', permission_codes: [] as string[] });
const editing = computed(() => selected.value !== null);
function reset(role: Role | null = null): void {
    selected.value = role;
    form.value = role
        ? {
              code: role.code,
              name: role.name,
              description: role.description ?? '',
              permission_codes: role.permissions.map((p) => p.code),
          }
        : { code: '', name: '', description: '', permission_codes: [] };
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
        const [roleResponse, permissionResponse] = await Promise.all([
            api.get<ApiEnvelope<Role[]>>('/roles'),
            api.get<ApiEnvelope<Permission[]>>('/permissions'),
        ]);
        roles.value = roleResponse.data.data;
        permissions.value = permissionResponse.data.data;
    } catch (e) {
        error.value = message(e);
    } finally {
        loading.value = false;
    }
}
async function save(): Promise<void> {
    saving.value = true;
    error.value = null;
    try {
        if (selected.value)
            await api.patch(`/roles/${selected.value.id}`, {
                name: form.value.name,
                description: form.value.description || null,
                permission_codes: form.value.permission_codes,
            });
        else await api.post('/roles', form.value);
        await load();
        reset();
    } catch (e) {
        error.value = message(e);
    } finally {
        saving.value = false;
    }
}
async function deactivate(): Promise<void> {
    if (!selected.value || !window.confirm(`¿Desactivar el rol ${selected.value.name}?`)) return;
    saving.value = true;
    try {
        await api.delete(`/roles/${selected.value.id}`);
        await load();
        reset();
    } catch (e) {
        error.value = message(e);
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
            <header class="border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Control de acceso</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Roles y permisos</h1>
                <p class="mt-2 text-sm text-[#464555]">Define con precisión lo que cada persona puede administrar.</p>
            </header>
            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]">
                {{ error }} <button class="ml-3 font-bold underline" @click="load">Reintentar</button>
            </div>
            <div v-else-if="loading" class="mt-6 grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
                <div class="h-96 animate-pulse rounded-2xl bg-[#e4e1ee]" />
                <div class="h-96 animate-pulse rounded-2xl bg-[#e4e1ee]" />
            </div>
            <div v-else class="mt-6 grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
                <section class="rounded-2xl border border-[#c7c4d8] bg-white p-3">
                    <div class="flex items-center justify-between px-2 pb-3">
                        <h2 class="font-bold">Roles activos</h2>
                        <button
                            class="min-h-11 rounded-lg px-3 text-base font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                            @click="reset()"
                        >
                            Nuevo rol
                        </button>
                    </div>
                    <p v-if="roles.length === 0" class="p-5 text-sm text-[#464555]">No hay roles personalizados.</p>
                    <button
                        v-for="role in roles"
                        :key="role.id"
                        class="mb-2 w-full rounded-xl border p-4 text-left transition hover:border-[#4f46e5]"
                        :class="selected?.id === role.id ? 'border-[#3525cd] bg-[#f5f2ff]' : 'border-[#e4e1ee]'"
                        @click="reset(role)"
                    >
                        <span class="font-bold">{{ role.name }}</span
                        ><span class="mt-1 block text-xs text-[#464555]"
                            >{{ role.permissions.length }} permisos · {{ role.is_system ? 'Sistema' : role.code }}</span
                        >
                    </button>
                </section>
                <form class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm" @submit.prevent="save">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.12em] text-[#006c49]">
                                {{ editing ? 'Edición' : 'Nuevo rol' }}
                            </p>
                            <h2 class="mt-1 text-xl font-bold">{{ editing ? form.name : 'Configura el acceso' }}</h2>
                        </div>
                        <button
                            v-if="editing && !selected?.is_system"
                            type="button"
                            class="min-h-11 text-sm font-bold text-[#ba1a1a]"
                            :disabled="saving"
                            @click="deactivate"
                        >
                            Desactivar
                        </button>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2 text-sm font-semibold"
                            >Nombre<input
                                v-model.trim="form.name"
                                required
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                :disabled="selected?.is_system" /></label
                        ><label class="grid gap-2 text-sm font-semibold"
                            >Código<input
                                v-model.trim="form.code"
                                required
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 font-mono text-base font-normal"
                                :disabled="editing || selected?.is_system"
                        /></label>
                    </div>
                    <label class="mt-4 grid gap-2 text-sm font-semibold"
                        >Descripción<textarea
                            v-model.trim="form.description"
                            class="min-h-20 rounded-lg border border-[#c7c4d8] p-3 font-normal"
                            :disabled="selected?.is_system"
                        />
                    </label>
                    <fieldset class="mt-5" :disabled="selected?.is_system">
                        <legend class="font-bold">Permisos</legend>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="permission in permissions"
                                :key="permission.code"
                                class="flex min-h-11 items-center gap-3 rounded-lg bg-[#f5f2ff] px-3 text-sm"
                                ><input v-model="form.permission_codes" type="checkbox" :value="permission.code" /><span
                                    ><b>{{ permission.name }}</b
                                    ><small class="block text-[#464555]">{{ permission.code }}</small></span
                                ></label
                            >
                        </div>
                    </fieldset>
                    <p v-if="selected?.is_system" class="mt-4 text-sm text-[#464555]">
                        Los roles del sistema no se modifican.
                    </p>
                    <button
                        v-else
                        class="mt-6 min-h-12 rounded-lg bg-[#3525cd] px-5 text-base font-bold text-white shadow-sm disabled:opacity-60"
                        :disabled="saving"
                        type="submit"
                    >
                        {{ saving ? 'Guardando…' : editing ? 'Guardar cambios' : 'Crear rol' }}
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
