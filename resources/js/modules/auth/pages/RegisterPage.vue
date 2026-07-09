<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session';

const router = useRouter();
const session = useSessionStore();
const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);

async function submit(): Promise<void> {
    isSubmitting.value = true;
    errorMessage.value = null;

    try {
        await session.register({
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
            device_name: 'OmniPOS Web',
        });
        await router.push({ name: 'context' });
    } catch (error) {
        errorMessage.value = axios.isAxiosError(error)
            ? (error.response?.data?.error?.message ?? 'Revisa los datos de tu cuenta.')
            : 'No se pudo crear la cuenta. Intenta de nuevo.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <main class="auth-shell">
        <section class="auth-panel" aria-labelledby="register-title">
            <div class="auth-brand"><span class="auth-brand__mark" aria-hidden="true">O</span><span>OmniPOS</span></div>
            <div class="auth-heading">
                <p class="auth-kicker">Nueva cuenta</p>
                <h1 id="register-title">Prepara tu primer turno.</h1>
                <p>Después crearás la compañía y su sucursal principal.</p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <p v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</p>
                <label class="field-label" for="name">Nombre completo</label>
                <input id="name" v-model="name" class="field-control" autocomplete="name" required />
                <label class="field-label" for="register-email">Correo electrónico</label>
                <input
                    id="register-email"
                    v-model="email"
                    class="field-control"
                    type="email"
                    autocomplete="email"
                    required
                />
                <label class="field-label" for="register-password">Contraseña</label>
                <input
                    id="register-password"
                    v-model="password"
                    class="field-control"
                    type="password"
                    autocomplete="new-password"
                    required
                />
                <label class="field-label" for="password-confirmation">Confirma la contraseña</label>
                <input
                    id="password-confirmation"
                    v-model="passwordConfirmation"
                    class="field-control"
                    type="password"
                    autocomplete="new-password"
                    required
                />
                <button class="primary-action" type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? 'Creando cuenta…' : 'Crear cuenta' }}
                </button>
            </form>
            <p class="auth-footnote">¿Ya tienes cuenta? <RouterLink to="/ingresar">Inicia sesión</RouterLink></p>
        </section>

        <aside class="auth-aside" aria-label="Información de seguridad">
            <p class="auth-kicker">Acceso protegido</p>
            <p class="auth-aside__statement">
                La sesión se vincula a este dispositivo y se puede cerrar de forma segura.
            </p>
        </aside>
    </main>
</template>
