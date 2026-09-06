const AGENT_BASE_URLS = ['http://127.0.0.1:8765', 'http://localhost:8765'];
const REQUEST_TIMEOUT_MS = 10000;

async function fetchFromAgent(endpoint: string, init: RequestInit): Promise<Response> {
    let lastError: unknown;
    for (const baseUrl of AGENT_BASE_URLS) {
        try {
            const res = await fetch(`${baseUrl}${endpoint}`, {
                ...init,
                // @ts-expect-error Chrome Private Network Access hint
                targetAddressSpace: 'loopback',
            });
            return res;
        } catch (e) {
            lastError = e;
        }
    }
    throw lastError || new Error('No se pudo contactar al agente local.');
}

export type AgentPrintResult = {
    ok: boolean;
    status: string;
    message: string;
};

export type AgentPrinterInfo = {
    name: string;
    status: string;
    defaultPrinter: boolean;
    type?: 'SPOOLER' | 'BLUETOOTH' | 'SERIAL' | string;
    port?: string | null;
};

export type AgentBluetoothDevice = {
    name: string;
    status: string;
    instanceId: string;
    port?: string | null;
    isPrinter: boolean;
};

export type AgentSerialPort = {
    port: string;
    name: string;
    isBluetooth: boolean;
};

export type AgentDeviceSummary = {
    printers: AgentPrinterInfo[];
    bluetoothDevices: AgentBluetoothDevice[];
    serialPorts: AgentSerialPort[];
};

/** Envía un ticket ya renderizado al agente local Windows sin dependencias de extensiones. */
export async function printWithAgent(content: string, printerName?: string): Promise<AgentPrintResult> {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
        const response = await fetchFromAgent('/api/print', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                content: content.slice(0, 8000),
                printer: printerName || undefined,
            }),
            signal: controller.signal,
        });
        const data = (await response.json().catch(() => null)) as { status?: string; message?: string } | null;
        return {
            ok: response.ok && data?.status === 'printed',
            status: data?.status ?? `http_${response.status}`,
            message: data?.message ?? 'El agente no devolvió una respuesta válida.',
        };
    } catch (error) {
        return {
            ok: false,
            status: error instanceof DOMException && error.name === 'AbortError' ? 'timeout' : 'offline',
            message: 'No se pudo contactar al agente local de Windows en el puerto 8765.',
        };
    } finally {
        window.clearTimeout(timeout);
    }
}

/** Envía un comando de prueba a la impresora seleccionada a través del agente. */
export async function testWithAgent(printerName?: string, message?: string): Promise<AgentPrintResult> {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
        const response = await fetchFromAgent('/api/test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                printer: printerName || undefined,
                message: message || 'BSM-POS Windows Agent - Prueba de impresión',
            }),
            signal: controller.signal,
        });
        const data = (await response.json().catch(() => null)) as { status?: string; message?: string } | null;
        return {
            ok: response.ok && data?.status === 'printed',
            status: data?.status ?? `http_${response.status}`,
            message: data?.message ?? 'Prueba no completada.',
        };
    } catch (error) {
        const isTimeout = error instanceof DOMException && error.name === 'AbortError';
        return {
            ok: false,
            status: isTimeout ? 'timeout' : 'offline',
            message: isTimeout
                ? 'Tiempo de espera agotado al comunicar con la impresora (si es Bluetooth, verifique que esté encendida y en rango).'
                : 'No se pudo contactar al agente local de Windows en el puerto 8765.',
        };
    } finally {
        window.clearTimeout(timeout);
    }
}

/** Envía el pulso para abrir la gaveta de dinero conectada a la impresora. */
export async function openDrawerWithAgent(printerName?: string): Promise<AgentPrintResult> {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
        const response = await fetchFromAgent('/api/drawer/open', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ printer: printerName || undefined }),
            signal: controller.signal,
        });
        const data = (await response.json().catch(() => null)) as { status?: string; message?: string } | null;
        return {
            ok: response.ok && data?.status === 'opened',
            status: data?.status ?? `http_${response.status}`,
            message: data?.message ?? 'No se pudo abrir la gaveta.',
        };
    } catch (error) {
        const isTimeout = error instanceof DOMException && error.name === 'AbortError';
        return {
            ok: false,
            status: isTimeout ? 'timeout' : 'offline',
            message: isTimeout
                ? 'Tiempo de espera agotado al enviar pulso a la gaveta.'
                : 'No se pudo contactar al agente local de Windows en el puerto 8765.',
        };
    } finally {
        window.clearTimeout(timeout);
    }
}

/** Obtiene la lista de impresoras instaladas en Windows detectadas por el agente local. */
export async function getAgentPrinters(): Promise<AgentPrinterInfo[]> {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
        const response = await fetchFromAgent('/api/printers', {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            signal: controller.signal,
        });
        if (!response.ok) return [];
        return ((await response.json()) as AgentPrinterInfo[]) || [];
    } catch {
        return [];
    } finally {
        window.clearTimeout(timeout);
    }
}

/** Obtiene el resumen de periféricos Windows (Impresoras, Dispositivos Bluetooth, Puertos Seriales). */
export async function getAgentDevices(): Promise<AgentDeviceSummary> {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
        const response = await fetchFromAgent('/api/devices', {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            signal: controller.signal,
        });
        if (!response.ok) {
            return { printers: [], bluetoothDevices: [], serialPorts: [] };
        }
        return (await response.json()) as AgentDeviceSummary;
    } catch {
        return { printers: [], bluetoothDevices: [], serialPorts: [] };
    } finally {
        window.clearTimeout(timeout);
    }
}
