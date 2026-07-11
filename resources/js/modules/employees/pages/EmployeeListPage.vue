<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { createEmployee, fetchEmployees, updateEmployee } from '../services';
import type { Employee, EmployeeForm } from '../types';

const employees = ref<Employee[]>([]);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const selected = ref<Employee | null>(null);
const form = ref<EmployeeForm>(emptyForm());

function emptyForm(): EmployeeForm {
    return { name: '', position: '', phone: '', email: '', commission_rate: '0', is_active: true };
}

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        employees.value = await fetchEmployees();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

function edit(employee: Employee): void {
    selected.value = employee;
    form.value = {
        name: employee.name,
        position: employee.position ?? '',
        phone: employee.phone ?? '',
        email: employee.email ?? '',
        commission_rate: employee.commission_rate,
        is_active: employee.is_active,
    };
}

function reset(): void {
    selected.value = null;
    form.value = emptyForm();
}

async function save(): Promise<void> {
    saving.value = true;
    error.value = null;
    try {
        if (selected.value) {
            await updateEmployee(selected.value.id, form.value);
        } else {
            await createEmployee(form.value);
        }
        reset();
        await load();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

onMounted(() => void load());
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-5xl">
            <RouterLink to="/" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd]"
                >← Volver al panel</RouterLink
            >
            <header class="mt-3 border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Barbería / Salón</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Empleados</h1>
                <p class="mt-2 text-sm text-[#464555]">Barberos y estilistas con su porcentaje de comisión.</p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                <section>
                    <div v-if="loading" class="h-40 animate-pulse rounded-2xl bg-[#e4e1ee]" />
                    <ul v-else class="space-y-3">
                        <li
                            v-for="employee in employees"
                            :key="employee.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm"
                        >
                            <div>
                                <p class="font-bold">
                                    {{ employee.name }}
                                    <span v-if="!employee.is_active" class="text-xs font-semibold text-[#93000a]"
                                        >(inactivo)</span
                                    >
                                </p>
                                <p class="text-sm text-[#464555]">
                                    {{ employee.position || 'Sin cargo' }} · Comisión {{ employee.commission_rate }}%
                                </p>
                            </div>
                            <button
                                type="button"
                                class="min-h-11 rounded-lg px-4 text-sm font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                                @click="edit(employee)"
                            >
                                Editar
                            </button>
                        </li>
                        <li
                            v-if="!employees.length"
                            class="rounded-2xl border border-dashed border-[#c7c4d8] p-6 text-center text-sm text-[#464555]"
                        >
                            Aún no hay empleados. Agrega el primero.
                        </li>
                    </ul>
                </section>

                <aside class="rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
                    <h2 class="font-bold">{{ selected ? 'Editar empleado' : 'Nuevo empleado' }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="save">
                        <label class="grid gap-1 text-sm font-semibold"
                            >Nombre
                            <input
                                v-model.trim="form.name"
                                required
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-3"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Cargo
                            <input
                                v-model.trim="form.position"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-3"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Teléfono
                            <input v-model.trim="form.phone" class="min-h-11 rounded-lg border border-[#c7c4d8] px-3" />
                        </label>
                        <label class="grid gap-1 text-sm font-semibold"
                            >Comisión (%)
                            <input
                                v-model="form.commission_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                class="min-h-11 rounded-lg border border-[#c7c4d8] px-3"
                            />
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold">
                            <input v-model="form.is_active" type="checkbox" class="h-5 w-5" /> Activo
                        </label>
                        <div class="flex gap-2 pt-2">
                            <button
                                type="submit"
                                :disabled="saving || !form.name"
                                class="min-h-11 flex-1 rounded-lg bg-[#3525cd] px-4 text-sm font-bold text-white disabled:opacity-60"
                            >
                                {{ saving ? 'Guardando…' : 'Guardar' }}
                            </button>
                            <button
                                v-if="selected"
                                type="button"
                                class="min-h-11 rounded-lg px-4 text-sm font-bold text-[#464555] hover:bg-[#f0ecf9]"
                                @click="reset"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </aside>
            </div>
        </div>
    </main>
</template>
