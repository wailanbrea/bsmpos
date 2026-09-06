<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session';

const router = useRouter();
const session = useSessionStore();
const email = ref('');
const password = ref('');
const code = ref('');
const needsCode = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);

async function submit(): Promise<void> {
    isSubmitting.value = true;
    errorMessage.value = null;

    try {
        await session.login({
            email: email.value,
            password: password.value,
            device_name: 'BSM-POS Web',
            code: needsCode.value ? code.value : undefined,
        });
        await router.push({ name: session.hasContext ? 'dashboard' : 'context' });
    } catch (error) {
        const errorCode = axios.isAxiosError(error) ? error.response?.data?.error?.code : null;

        // El backend pide el segundo factor: revelar el campo de código.
        if (errorCode === 'TWO_FACTOR_REQUIRED' || errorCode === 'TWO_FACTOR_INVALID') {
            needsCode.value = true;
            errorMessage.value =
                errorCode === 'TWO_FACTOR_INVALID'
                    ? 'El código de verificación no es válido.'
                    : 'Ingresa el código de tu app de verificación en dos pasos.';
        } else {
            errorMessage.value = axios.isAxiosError(error)
                ? (error.response?.data?.error?.message ?? 'No se pudo iniciar sesión. Verifica tus datos.')
                : 'No se pudo iniciar sesión. Intenta de nuevo.';
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <main class="auth-shell">
        <section class="auth-panel" aria-labelledby="login-title">
            <div class="auth-brand">
                <span class="auth-brand__mark" aria-hidden="true">B</span>
                <span>BSM-POS</span>
            </div>

            <div class="auth-heading">
                <p class="auth-kicker">Inicio de turno</p>
                <h1 id="login-title">Entra a tu operación.</h1>
                <p>Usa tu cuenta para continuar con la compañía y sucursal asignadas.</p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <p v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</p>

                <label class="field-label" for="email">Correo electrónico</label>
                <input id="email" v-model="email" class="field-control" type="email" autocomplete="email" required />

                <label class="field-label" for="password">Contraseña</label>
                <input
                    id="password"
                    v-model="password"
                    class="field-control"
                    type="password"
                    autocomplete="current-password"
                    required
                />

                <template v-if="needsCode">
                    <label class="field-label" for="code">Código de verificación (2FA)</label>
                    <input
                        id="code"
                        v-model.trim="code"
                        class="field-control"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        placeholder="123456"
                    />
                </template>

                <button class="primary-action" type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? 'Verificando acceso…' : 'Iniciar sesión' }}
                </button>
            </form>

            <p class="auth-footnote">
                ¿Primera vez en BSM-POS?
                <RouterLink to="/crear-cuenta">Crea tu cuenta</RouterLink>
            </p>
        </section>

        <aside class="auth-aside" aria-label="Información de BSM-POS">
            <p class="auth-kicker">Operación con contexto</p>
            <p class="auth-aside__statement">Cada venta empieza con la empresa y sucursal correctas.</p>
            <div class="auth-aside__rail">
                <span aria-hidden="true" />
                <p>Control multiempresa para equipos que trabajan en movimiento.</p>
            </div>
        </aside>
    </main>
</template>
