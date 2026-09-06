import { onBeforeUnmount, onMounted, ref } from 'vue';

export type AgentConnectionState = 'checking' | 'connected' | 'disconnected';

type AgentStatusPayload = {
    status?: string;
    agent?: string;
    version?: string;
    host?: string;
    port?: number;
};

const AGENT_STATUS_URLS = [
    'http://127.0.0.1:8765/api/status',
    'http://localhost:8765/api/status',
];
const RETRY_DELAY_DISCONNECTED_MS = 3_000;
const RETRY_DELAY_CONNECTED_MS = 15_000;
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

    function scheduleRetry(delayMs: number) {
        if (!mounted || retryTimer !== undefined) {
            return;
        }
        retryTimer = window.setTimeout(() => {
            retryTimer = undefined;
            void checkNow();
        }, delayMs);
    }

    async function checkNow() {
        if (!mounted) {
            return;
        }
        clearTimers();
        controller?.abort();
        controller = new AbortController();
        requestTimer = window.setTimeout(() => controller?.abort(), REQUEST_TIMEOUT_MS);

        let connectedPayload: AgentStatusPayload | null = null;

        for (const url of AGENT_STATUS_URLS) {
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    signal: controller.signal,
                    // @ts-expect-error Chrome Private Network Access hint
                    targetAddressSpace: 'loopback',
                });
                if (response.ok) {
                    const payload = (await response.json()) as AgentStatusPayload;
                    if (payload.status === 'ok') {
                        connectedPayload = payload;
                        break;
                    }
                }
            } catch {
                // Siguiente URL de fallback
            }
        }

        if (connectedPayload) {
            state.value = 'connected';
            version.value = connectedPayload.version ?? null;
            agentName.value = connectedPayload.agent ?? 'BSM-POS Windows Agent';
        } else {
            state.value = 'disconnected';
            version.value = null;
            agentName.value = null;
        }

        if (requestTimer !== undefined) {
            window.clearTimeout(requestTimer);
            requestTimer = undefined;
        }
        lastCheckedAt.value = new Date();
        scheduleRetry(state.value === 'connected' ? RETRY_DELAY_CONNECTED_MS : RETRY_DELAY_DISCONNECTED_MS);
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
