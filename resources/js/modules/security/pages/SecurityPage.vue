<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { beginTwoFactor, confirmTwoFactor, disableTwoFactor, fetchTwoFactorStatus } from '../services';
import type { TwoFactorSetup } from '../services';

const enabled = ref(false);
const loading = ref(true);
const busy = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

// Estado del asistente de activación
const setup = ref<TwoFactorSetup | null>(null);
const confirmCode = ref('');
const disableCode = ref('');

// Clave agrupada de 4 en 4 para lectura/tecleo manual en la app autenticadora.
const groupedSecret = computed(() => (setup.value?.secret ?? '').replace(/(.{4})/g, '$1 ').trim());

function message(exception: unknown): string {
    return axios.isAxiosError(exception)
        ? (exception.response?.data?.error?.message ?? 'No se pudo completar la operación.')
        : 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        enabled.value = (await fetchTwoFactorStatus()).enabled;
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function begin(): Promise<void> {
    busy.value = true;
    error.value = null;
    success.value = null;
    try {
        setup.value = await beginTwoFactor();
    } catch (exception) {
        error.value = message(exception);
    } finally {
        busy.value = false;
    }
}

async function confirm(): Promise<void> {
    busy.value = true;
    error.value = null;
    try {
        enabled.value = (await confirmTwoFactor(confirmCode.value)).enabled;
        setup.value = null;
        confirmCode.value = '';
        success.value = 'Verificación en dos pasos activada.';
    } catch (exception) {
        error.value = message(exception);
    } finally {
        busy.value = false;
    }
}

function cancelSetup(): void {
    setup.value = null;
    confirmCode.value = '';
    error.value = null;
}

async function disable(): Promise<void> {
    busy.value = true;
    error.value = null;
    try {
        enabled.value = (await disableTwoFactor(disableCode.value)).enabled;
        disableCode.value = '';
        success.value = 'Verificación en dos pasos desactivada.';
    } catch (exception) {
        error.value = message(exception);
    } finally {
        busy.value = false;
    }
}

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-2xl">
            <header class="border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Cuenta</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Seguridad</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Añade verificación en dos pasos (2FA) con una app como Google Authenticator o Authy.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>
            <div v-if="success" class="mt-6 rounded-xl bg-[#d9f7df] p-4 text-sm text-[#006c49]" role="status">
                {{ success }}
            </div>

            <div v-if="loading" class="mt-6 h-40 animate-pulse rounded-2xl bg-[#e4e1ee]" />

            <section v-else class="mt-6 rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold">Verificación en dos pasos</h2>
                        <p class="mt-1 text-sm text-[#464555]">
                            Un código temporal de tu teléfono se pedirá al iniciar sesión.
                        </p>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="enabled ? 'bg-[#d9f7df] text-[#006c49]' : 'bg-[#e4e1ee] text-[#464555]'"
                    >
                        {{ enabled ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>

                <!-- Activar: paso 1 iniciar -->
                <div v-if="!enabled && !setup" class="mt-5">
                    <button
                        type="button"
                        :disabled="busy"
                        class="min-h-12 rounded-lg bg-[#3525cd] px-5 text-base font-bold text-white shadow-sm disabled:opacity-60"
                        @click="begin"
                    >
                        {{ busy ? 'Generando…' : 'Activar 2FA' }}
                    </button>
                </div>

                <!-- Activar: paso 2 escanear/ingresar y confirmar -->
                <div v-else-if="!enabled && setup" class="mt-5 space-y-4">
                    <div class="rounded-xl border border-[#c7c4d8] bg-[#f5f2ff] p-4">
                        <p class="text-sm font-semibold">1. Añade la cuenta en tu app autenticadora</p>
                        <p class="mt-1 text-sm text-[#464555]">Escanea el enlace o ingresa esta clave manualmente:</p>
                        <p class="mt-2 select-all break-all font-mono text-lg font-bold tracking-wider">
                            {{ groupedSecret }}
                        </p>
                        <a
                            :href="setup.otpauth_uri"
                            class="mt-2 inline-block break-all text-xs font-semibold text-[#3525cd]"
                        >
                            Abrir en la app (otpauth://)
                        </a>
                    </div>
                    <label class="grid gap-2 text-sm font-semibold"
                        >2. Ingresa el código de 6 dígitos que muestra la app
                        <input
                            v-model.trim="confirmCode"
                            inputmode="numeric"
                            maxlength="6"
                            class="min-h-11 w-40 rounded-lg border border-[#c7c4d8] px-3 font-mono text-lg tracking-widest"
                        />
                    </label>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            :disabled="busy || confirmCode.length < 6"
                            class="min-h-12 rounded-lg bg-[#006c49] px-5 text-base font-bold text-white shadow-sm disabled:opacity-60"
                            @click="confirm"
                        >
                            {{ busy ? 'Confirmando…' : 'Confirmar y activar' }}
                        </button>
                        <button
                            type="button"
                            class="min-h-11 rounded-lg px-4 text-base font-bold text-[#464555] hover:bg-[#f0ecf9]"
                            @click="cancelSetup"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>

                <!-- Desactivar -->
                <div v-else class="mt-5 space-y-3">
                    <p class="text-sm text-[#464555]">
                        Para desactivar la verificación en dos pasos, ingresa un código actual de tu app.
                    </p>
                    <div class="flex flex-wrap items-end gap-2">
                        <label class="grid gap-2 text-sm font-semibold"
                            >Código
                            <input
                                v-model.trim="disableCode"
                                inputmode="numeric"
                                maxlength="6"
                                class="min-h-11 w-40 rounded-lg border border-[#c7c4d8] px-3 font-mono text-lg tracking-widest"
                            />
                        </label>
                        <button
                            type="button"
                            :disabled="busy || disableCode.length < 6"
                            class="min-h-11 rounded-lg border border-[#ba1a1a] px-5 text-sm font-bold text-[#ba1a1a] disabled:opacity-60"
                            @click="disable"
                        >
                            {{ busy ? 'Desactivando…' : 'Desactivar 2FA' }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
