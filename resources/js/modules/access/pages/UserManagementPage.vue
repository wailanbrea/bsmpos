<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '../../../lib/api';
import type { ApiEnvelope } from '../../auth/types';
import type { AccessBranch, CompanyUser, Role } from '../types';

interface UserAccessForm {
    email: string;
    branch_ids: string[];
    default_branch_id: string;
    role_ids: string[];
}

const users = ref<CompanyUser[]>([]);
const branches = ref<AccessBranch[]>([]);
const roles = ref<Role[]>([]);
const selected = ref<CompanyUser | null>(null);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const form = ref<UserAccessForm>(emptyForm());
const editing = computed(() => selected.value !== null);
const selectedBranches = computed(() => new Set(form.value.branch_ids));

function emptyForm(): UserAccessForm {
    return { email: '', branch_ids: [], default_branch_id: '', role_ids: [] };
}

function reset(user: CompanyUser | null = null): void {
    selected.value = user;
    form.value = user
        ? {
              email: user.email,
              branch_ids: user.branches.map((branch) => branch.id),
              default_branch_id: user.default_branch_id ?? user.branches[0]?.id ?? '',
              role_ids: user.roles.map((role) => role.id),
          }
        : emptyForm();
    error.value = null;
}

function message(errorValue: unknown): string {
    return axios.isAxiosError(errorValue)
        ? (errorValue.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

function ensureDefaultBranch(): void {
    if (!selectedBranches.value.has(form.value.default_branch_id)) {
        form.value.default_branch_id = form.value.branch_ids[0] ?? '';
    }
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const [userResponse, branchResponse, roleResponse] = await Promise.all([
            api.get<ApiEnvelope<CompanyUser[]>>('/users'),
            api.get<ApiEnvelope<AccessBranch[]>>('/branches'),
            api.get<ApiEnvelope<Role[]>>('/roles'),
        ]);
        users.value = userResponse.data.data;
        branches.value = branchResponse.data.data.filter((branch) => branch.is_active);
        roles.value = roleResponse.data.data.filter((role) => !role.is_system);
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function save(): Promise<void> {
    ensureDefaultBranch();

    if (form.value.branch_ids.length === 0 || !form.value.default_branch_id) {
        error.value = 'Asigna al menos una sucursal y selecciona la predeterminada.';
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const payload = {
            branch_ids: form.value.branch_ids,
            default_branch_id: form.value.default_branch_id,
            role_ids: form.value.role_ids,
        };

        if (selected.value) {
            await api.patch(`/users/${selected.value.id}/access`, payload);
        } else {
            await api.post('/users', { ...payload, email: form.value.email });
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
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Control de acceso</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Usuarios y accesos</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Asigna sucursales y roles a cuentas ya registradas, sin alterar sus accesos en otras empresas.
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
                        <h2 class="font-bold">Accesos configurados</h2>
                        <button
                            class="min-h-11 rounded-lg px-3 text-base font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                            @click="reset()"
                        >
                            Asignar acceso
                        </button>
                    </div>
                    <p v-if="users.length === 0" class="p-5 text-sm text-[#464555]">No hay accesos configurados.</p>
                    <button
                        v-for="user in users"
                        :key="user.id"
                        class="mb-2 w-full rounded-xl border p-4 text-left transition hover:border-[#4f46e5] disabled:cursor-not-allowed disabled:opacity-70"
                        :class="selected?.id === user.id ? 'border-[#3525cd] bg-[#f5f2ff]' : 'border-[#e4e1ee]'"
                        :disabled="user.is_owner"
                        :title="
                            user.is_owner
                                ? 'El acceso del propietario se administra fuera de esta pantalla.'
                                : undefined
                        "
                        @click="reset(user)"
                    >
                        <span class="flex items-center justify-between gap-3 font-bold">
                            {{ user.name }}
                            <span v-if="user.is_owner" class="rounded-full bg-[#e4e1ee] px-2 py-1 text-xs"
                                >Propietario</span
                            >
                        </span>
                        <span class="mt-1 block text-sm text-[#464555]">{{ user.email }}</span>
                        <span class="mt-1 block text-xs text-[#464555]"
                            >{{ user.branches.length }} sucursal(es) · {{ user.roles.length }} rol(es)</span
                        >
                    </button>
                </section>

                <form class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm" @submit.prevent="save">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.12em] text-[#006c49]">
                            {{ editing ? 'Edición de acceso' : 'Nuevo acceso' }}
                        </p>
                        <h2 class="mt-1 text-xl font-bold">
                            {{ editing ? selected?.name : 'Configura una cuenta existente' }}
                        </h2>
                    </div>
                    <label class="mt-5 grid gap-2 text-sm font-semibold"
                        >Correo de la cuenta existente
                        <input
                            v-model.trim="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal disabled:bg-[#f0ecf9]"
                            :disabled="editing"
                        />
                    </label>
                    <p v-if="!editing" class="mt-2 text-xs text-[#464555]">
                        La persona debe haberse registrado previamente. Esta acción no crea una cuenta ni comparte su
                        acceso entre empresas.
                    </p>

                    <fieldset class="mt-5">
                        <legend class="font-bold">Sucursales autorizadas</legend>
                        <p class="mt-1 text-sm text-[#464555]">Selecciona al menos una sucursal activa.</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="branch in branches"
                                :key="branch.id"
                                class="flex min-h-11 items-center gap-3 rounded-lg bg-[#f5f2ff] px-3 text-sm"
                            >
                                <input
                                    v-model="form.branch_ids"
                                    type="checkbox"
                                    :value="branch.id"
                                    @change="ensureDefaultBranch"
                                />
                                <span
                                    ><b>{{ branch.name }}</b
                                    ><small class="block text-[#464555]">{{ branch.code }}</small></span
                                >
                            </label>
                        </div>
                    </fieldset>

                    <label class="mt-5 grid gap-2 text-sm font-semibold"
                        >Sucursal predeterminada
                        <select
                            v-model="form.default_branch_id"
                            required
                            class="min-h-12 rounded-lg border border-[#c7c4d8] bg-white px-3 text-base font-normal"
                        >
                            <option value="" disabled>Selecciona una sucursal</option>
                            <option
                                v-for="branch in branches.filter((item) => selectedBranches.has(item.id))"
                                :key="branch.id"
                                :value="branch.id"
                            >
                                {{ branch.name }}
                            </option>
                        </select>
                    </label>

                    <fieldset class="mt-5">
                        <legend class="font-bold">Roles</legend>
                        <p class="mt-1 text-sm text-[#464555]">
                            Los permisos se derivan exclusivamente de los roles asignados.
                        </p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="role in roles"
                                :key="role.id"
                                class="flex min-h-11 items-center gap-3 rounded-lg bg-[#f5f2ff] px-3 text-sm"
                            >
                                <input v-model="form.role_ids" type="checkbox" :value="role.id" />
                                <span
                                    ><b>{{ role.name }}</b
                                    ><small class="block text-[#464555]">{{ role.code }}</small></span
                                >
                            </label>
                        </div>
                    </fieldset>

                    <button
                        class="mt-6 min-h-12 rounded-lg bg-[#3525cd] px-5 text-base font-bold text-white shadow-sm disabled:opacity-60"
                        :disabled="saving"
                        type="submit"
                    >
                        {{ saving ? 'Guardando…' : editing ? 'Guardar acceso' : 'Asignar acceso' }}
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
