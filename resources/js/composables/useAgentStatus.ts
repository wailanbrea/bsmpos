import { onBeforeUnmount, onMounted, ref } from 'vue';

export type AgentConnectionState = 'checking' | 'connected' | 'disconnected';

type AgentStatusPayload = {
    status?: string;
    agent?: string;
    version?: string;
    host?: string;
    port?: number;
};

const AGENT_STATUS_URL = 'http://127.0.0.1:8765/api/status';
const RETRY_DELAY_MS = 15_000;
const REQUEST_TIMEOUT_MS = 3_000;

/** Monitorea el estado del agente local de hardware (BSM-POS Windows Agent) sin bloquear la aplicación web. */
export function useAgentStatus() {
    const state = ref<AgentConnectionState>('checking');
    const version = ref<string | null>(null);
    const agentName = ref<string | null>(null);
    const lastCheckedAt = ref<Date | null>(null);

    let retryTimer: number | undefined;
    let requestTimer: number | undefined;
    let controller: AbortController | undefined;
    let mounted = false;

    function clearTimers() {
        if (retryTimer !== undefined) {
            window.clearTimeout(retryTimer);
            retryTimer = undefined;
        }
        if (requestTimer !== undefined) {
            window.clearTimeout(requestTimer);
            requestTimer = undefined;
        }
    }

    function scheduleRetry() {
        if (!mounted || retryTimer !== undefined) {
            return;
        }
        retryTimer = window.setTimeout(() => {
            retryTimer = undefined;
            void checkNow();
        }, RETRY_DELAY_MS);
    }

    async function checkNow() {
        if (!mounted) {
            return;
        }
        clearTimers();
        controller?.abort();
        controller = new AbortController();
        requestTimer = window.setTimeout(() => controller?.abort(), REQUEST_TIMEOUT_MS);
        state.value = 'checking';

        try {
            const response = await fetch(AGENT_STATUS_URL, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: controller.signal,
            });
            if (!response.ok) {
                throw new Error(`El agente respondió con código ${response.status}`);
            }
            const payload = (await response.json()) as AgentStatusPayload;
            if (payload.status !== 'ok') {
                throw new Error('El agente no se encuentra en estado operativo');
            }
            state.value = 'connected';
            version.value = payload.version ?? null;
            agentName.value = payload.agent ?? 'BSM-POS Windows Agent';
        } catch {
            state.value = 'disconnected';
            version.value = null;
            agentName.value = null;
        } finally {
            if (requestTimer !== undefined) {
                window.clearTimeout(requestTimer);
                requestTimer = undefined;
            }
            lastCheckedAt.value = new Date();
            scheduleRetry();
        }
    }

    function handleVisibilityChange() {
        if (document.visibilityState === 'visible') {
            void checkNow();
        }
    }

    onMounted(() => {
        mounted = true;
        document.addEventListener('visibilitychange', handleVisibilityChange);
        void checkNow();
    });

    onBeforeUnmount(() => {
        mounted = false;
        clearTimers();
        controller?.abort();
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    });

    return { state, version, agentName, lastCheckedAt, checkNow };
}
