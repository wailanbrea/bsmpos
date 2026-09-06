<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { api } from '../../../lib/api';
import { useAgentStatus } from '../../../composables/useAgentStatus';
import {
    getAgentDevices,
    openDrawerWithAgent,
    testWithAgent,
    type AgentPrinterInfo,
    type AgentBluetoothDevice,
    type AgentSerialPort,
} from '../../../composables/useAgentPrinter';

interface BranchOption {
    id: number;
    name: string;
}

interface CashRegisterOption {
    id: number;
    branch_id: number;
    code: string;
    name: string;
}

interface TerminalRecord {
    id: number;
    terminal_id: string;
    token_last4: string;
    active: boolean;
    last_seen_at: string | null;
    created_at: string;
    branch?: { id: number; name: string };
    cash_register?: { id: number; branch_id: number; code: string; name: string };
    creator?: { id: number; name: string };
}

const { state: agentState, version: agentVersion, checkNow: checkAgentStatus } = useAgentStatus();

const loading = ref(false);
const submitting = ref(false);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

const terminals = ref<TerminalRecord[]>([]);
const branches = ref<BranchOption[]>([]);
const cashRegisters = ref<CashRegisterOption[]>([]);
const localPrinters = ref<AgentPrinterInfo[]>([]);
const bluetoothDevices = ref<AgentBluetoothDevice[]>([]);
const serialPorts = ref<AgentSerialPort[]>([]);

// Formulario de registro
const showCreateModal = ref(false);
const formTerminalId = ref('');
const formBranchId = ref<number | ''>('');
const formCashRegisterId = ref<number | ''>('');

// Token generado visible una sola vez
const generatedToken = ref<string | null>(null);
const tokenCopied = ref(false);

// Filtro de cajas por sucursal
const availableRegisters = computed(() => {
    if (!formBranchId.value) return [];
    return cashRegisters.value.filter((r) => r.branch_id === formBranchId.value);
});

async function loadData(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get('/agent-terminals');
        terminals.value = response.data.data.terminals ?? [];
        branches.value = response.data.data.branches ?? [];
        cashRegisters.value = response.data.data.cash_registers ?? [];
    } catch (e: unknown) {
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        error.value = (e as any)?.response?.data?.error?.message ?? 'Error al cargar las terminales';
    } finally {
        loading.value = false;
    }
}

async function refreshPrinters(): Promise<void> {
    await checkAgentStatus();
    if (agentState.value === 'connected') {
        const summary = await getAgentDevices();
        localPrinters.value = summary.printers;
        bluetoothDevices.value = summary.bluetoothDevices;
        serialPorts.value = summary.serialPorts;
    } else {
        localPrinters.value = [];
        bluetoothDevices.value = [];
        serialPorts.value = [];
    }
}

async function handleCreate(): Promise<void> {
    if (!formTerminalId.value || !formBranchId.value) return;
    submitting.value = true;
    error.value = null;
    try {
        const response = await api.post('/agent-terminals', {
            terminal_id: formTerminalId.value.trim(),
            branch_id: Number(formBranchId.value),
            cash_register_id: formCashRegisterId.value ? Number(formCashRegisterId.value) : null,
        });

        const created = response.data.data.terminal;
        const plainToken = response.data.data.plain_token;

        terminals.value.push(created);
        generatedToken.value = plainToken;
        successMessage.value = response.data.message;
        showCreateModal.value = false;
        formTerminalId.value = '';
        formCashRegisterId.value = '';
    } catch (e: unknown) {
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        const err = e as any;
        const details = err?.response?.data?.error?.details;
        const firstDetail = details && typeof details === 'object' ? (Object.values(details).flat()[0] as string) : null;
        error.value = firstDetail || err?.response?.data?.error?.message || err?.response?.data?.message || 'No se pudo registrar la terminal';
    } finally {
        submitting.value = false;
    }
}

async function copyToken(): Promise<void> {
    if (!generatedToken.value) return;
    try {
        await navigator.clipboard.writeText(generatedToken.value);
        tokenCopied.value = true;
        window.setTimeout(() => (tokenCopied.value = false), 2500);
    } catch {
        tokenCopied.value = false;
    }
}

async function handleRotateToken(terminal: TerminalRecord): Promise<void> {
    if (!window.confirm(`¿Rotar token para la terminal ${terminal.terminal_id}? El token anterior dejará de funcionar inmediatamente.`)) {
        return;
    }
    loading.value = true;
    error.value = null;
    try {
        const response = await api.post(`/agent-terminals/${terminal.id}/rotate-token`);
        generatedToken.value = response.data.data.plain_token;
        successMessage.value = `Nuevo token generado para ${terminal.terminal_id}. Cópialo y actualiza el agente.`;
        await loadData();
    } catch (e: unknown) {
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        error.value = (e as any)?.response?.data?.error?.message ?? 'No se pudo rotar el token';
    } finally {
        loading.value = false;
    }
}

async function handleToggle(terminal: TerminalRecord): Promise<void> {
    loading.value = true;
    try {
        const response = await api.patch(`/agent-terminals/${terminal.id}/toggle`);
        terminal.active = response.data.data.active;
        successMessage.value = response.data.message;
    } catch (e: unknown) {
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        error.value = (e as any)?.response?.data?.error?.message ?? 'No se pudo cambiar el estado';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(terminal: TerminalRecord): Promise<void> {
    if (!window.confirm(`¿Estás seguro de eliminar la terminal ${terminal.terminal_id}?`)) {
        return;
    }
    loading.value = true;
    try {
        await api.delete(`/agent-terminals/${terminal.id}`);
        terminals.value = terminals.value.filter((t) => t.id !== terminal.id);
        successMessage.value = 'Terminal eliminada.';
    } catch (e: unknown) {
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        error.value = (e as any)?.response?.data?.error?.message ?? 'No se pudo eliminar la terminal';
    } finally {
        loading.value = false;
    }
}

async function testPrinter(printerName?: string): Promise<void> {
    const res = await testWithAgent(printerName);
    if (res.ok) {
        alert('Prueba enviada exitosamente a la impresora.');
    } else {
        alert(`Error al imprimir: ${res.message}`);
    }
}

async function testDrawer(printerName?: string): Promise<void> {
    const res = await openDrawerWithAgent(printerName);
    if (res.ok) {
        alert('Pulso de apertura de gaveta enviado.');
    } else {
        alert(`Error al abrir gaveta: ${res.message}`);
    }
}

onMounted(async () => {
    await loadData();
    await refreshPrinters();
});
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="text-xs uppercase tracking-wider font-semibold text-[#5f5e61] px-2 py-0.5 rounded bg-[#e5eeff]">
                        Hardware Local Windows
                    </span>
                    <span class="text-xs text-[#767586]">•</span>
                    <span class="text-xs text-[#5f5e61]">Puerto 8765 Loopback</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30]">
                    Terminales Windows & Hardware POS
                </h1>
                <p class="text-xs md:text-sm text-[#5f5e61] mt-1">
                    Conexión directa con impresoras térmicas ESC/POS y gaveta de dinero a través de BSM-POS Windows Agent.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a
                    href="/downloads/bsm-pos-agent.zip"
                    download="BSM-POS-Agent.zip"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center shadow-xs transition cursor-pointer"
                    title="Descargar paquete de instalación del agente para Windows"
                >
                    <span class="material-symbols-outlined mr-1.5 text-[18px]">download</span>
                    <span>Descargar Agente BSM-POS</span>
                </a>
                <button
                    type="button"
                    class="bg-white border border-[#c7c4d7]/60 hover:bg-[#eff4ff] text-[#0b1c30] px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center transition shadow-2xs cursor-pointer"
                    @click="refreshPrinters"
                >
                    <span class="material-symbols-outlined mr-1.5 text-[18px]">sync</span>
                    <span>Probar Agente Local</span>
                </button>
                <button
                    type="button"
                    class="bg-[#4648d4] hover:bg-[#393bb3] text-white px-4 py-2 rounded-lg text-xs md:text-sm font-semibold flex items-center shadow-xs transition cursor-pointer"
                    @click="showCreateModal = true"
                >
                    <span class="material-symbols-outlined mr-1.5 text-[18px]">add_circle</span>
                    <span>Registrar Terminal</span>
                </button>
            </div>
        </div>

        <!-- ALERTA DE TOKEN GENERADO (SOLO SE MUESTRA UNA VEZ) -->
        <div
            v-if="generatedToken"
            class="p-5 bg-amber-50 border border-amber-200 rounded-xl shadow-xs space-y-3"
        >
            <div class="flex items-start space-x-3">
                <span class="material-symbols-outlined text-amber-600 text-[24px] shrink-0 mt-0.5">vpn_key</span>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-amber-950">
                        Token de Seguridad Generado — ¡Cópialo Ahora!
                    </h3>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Por motivos de seguridad, este token solo se muestra en este momento. Colócalo en el archivo <code class="bg-amber-100 px-1 py-0.5 rounded font-mono text-[11px]">agent/src/main/resources/application.conf</code> o como variable <code class="bg-amber-100 px-1 py-0.5 rounded font-mono text-[11px]">AGENT_TERMINAL_TOKEN</code> en Windows.
                    </p>
                    <div class="mt-3 flex items-center gap-2 max-w-xl">
                        <input
                            readonly
                            :value="generatedToken"
                            class="flex-1 bg-white border border-amber-300 rounded-lg px-3 py-2 text-xs font-mono text-[#0b1c30] select-all outline-none"
                        >
                        <button
                            type="button"
                            class="bg-amber-700 hover:bg-amber-800 text-white px-4 py-2 rounded-lg text-xs font-semibold flex items-center shadow-xs transition cursor-pointer"
                            @click="copyToken"
                        >
                            <span class="material-symbols-outlined mr-1 text-[16px]">content_copy</span>
                            <span>{{ tokenCopied ? '¡Copiado!' : 'Copiar Token' }}</span>
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="text-amber-800 hover:text-amber-950 text-xs p-1"
                    title="Cerrar aviso"
                    @click="generatedToken = null"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- NOTIFICACIONES FEEDBACK -->
        <div v-if="successMessage" class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                <span>{{ successMessage }}</span>
            </div>
            <button type="button" class="text-emerald-700 hover:text-emerald-900" @click="successMessage = null">✕</button>
        </div>

        <div v-if="error" class="p-4 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="material-symbols-outlined text-rose-600 text-[18px]">error</span>
                <span>{{ error }}</span>
            </div>
            <button type="button" class="text-rose-700 hover:text-rose-900" @click="error = null">✕</button>
        </div>

        <!-- BENTO ROW: ESTADO DEL AGENTE LOCAL + IMPRESORAS DETECTADAS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- CARD 1: ESTADO AGENTE LOCAL (1 COL) -->
            <div class="bg-white p-5 rounded-xl border border-[#e2e8f0] shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Agente en este Equipo</span>
                        <span
                            class="w-2.5 h-2.5 rounded-full"
                            :class="agentState === 'connected' ? 'bg-emerald-500 animate-pulse' : 'bg-zinc-400'"
                        />
                    </div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center"
                            :class="agentState === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-500'"
                        >
                            <span class="material-symbols-outlined text-[24px]">desktop_windows</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold font-geist text-[#0b1c30]">
                                {{ agentState === 'connected' ? 'BSM-POS Windows Agent' : 'Agente no detectado' }}
                            </h3>
                            <p class="text-xs text-[#5f5e61]">
                                {{ agentState === 'connected' ? `Versión ${agentVersion} en 127.0.0.1:8765` : 'Servicio apagado o puerto 8765 cerrado' }}
                            </p>
                        </div>
                    </div>
                    <p class="text-xs text-[#5f5e61] leading-relaxed mt-2">
                        {{ agentState === 'connected'
                            ? 'El agente local está en línea y listo para recibir impresiones térmicas y pulsos de apertura de gaveta desde el POS.'
                            : 'Para activar la impresión silenciosa y apertura de gaveta, descarga el paquete del agente e instálalo como servicio de Windows en este equipo.'
                        }}
                    </p>
                    <div v-if="agentState !== 'connected'" class="mt-3">
                        <a
                            href="/downloads/bsm-pos-agent.zip"
                            download="BSM-POS-Agent.zip"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition"
                        >
                            <span class="material-symbols-outlined text-[16px]">download</span>
                            <span>Descargar instalador (.zip 21 MB)</span>
                        </a>
                    </div>
                </div>
                <div class="pt-4 mt-4 border-t border-[#e2e8f0] flex items-center justify-between">
                    <span class="text-xs text-[#5f5e61]">Estado: <strong class="capitalize" :class="agentState === 'connected' ? 'text-emerald-700' : 'text-zinc-600'">{{ agentState }}</strong></span>
                    <button
                        type="button"
                        class="text-xs text-[#4648d4] font-semibold hover:underline cursor-pointer"
                        @click="refreshPrinters"
                    >
                        Verificar conexión →
                    </button>
                </div>
            </div>

            <!-- CARD 2: IMPRESORAS DETECTADAS EN WINDOWS (2 COLS) -->
            <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-[#e2e8f0] shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-semibold text-[#5f5e61] uppercase tracking-wider">Impresoras Detectadas</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded">USB · Spooler · Bluetooth</span>
                        </div>
                        <span class="text-xs text-[#5f5e61]">{{ localPrinters.length }} encontradas</span>
                    </div>

                    <div v-if="agentState !== 'connected'" class="py-8 text-center text-zinc-400 text-xs">
                        <span class="material-symbols-outlined text-[36px] text-zinc-300 block mb-1">print_disabled</span>
                        Conecta el agente local para listar las impresoras instaladas en este Windows.
                    </div>

                    <div v-else-if="localPrinters.length === 0" class="py-8 text-center text-zinc-400 text-xs">
                        No se detectaron impresoras instaladas en el sistema operativo.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-56 overflow-y-auto pr-1">
                        <div
                            v-for="p in localPrinters"
                            :key="p.name"
                            class="p-3 rounded-lg border flex items-center justify-between"
                            :class="p.defaultPrinter ? 'bg-[#eff4ff] border-[#4648d4]/30' : 'bg-white border-[#e2e8f0]'"
                        >
                            <div class="min-w-0 pr-2">
                                <div class="flex items-center space-x-1.5">
                                    <span class="material-symbols-outlined text-[16px]" :class="p.type === 'BLUETOOTH' ? 'text-blue-600' : 'text-zinc-500'">
                                        {{ p.type === 'BLUETOOTH' ? 'bluetooth' : p.type === 'SERIAL' ? 'cable' : 'print' }}
                                    </span>
                                    <span class="text-xs font-bold text-[#0b1c30] truncate" :title="p.name">{{ p.name }}</span>
                                    <span v-if="p.defaultPrinter" class="text-[9px] bg-[#4648d4] text-white px-1.5 py-0.2 rounded font-semibold shrink-0">Predeterminada</span>
                                    <span v-if="p.type === 'BLUETOOTH'" class="text-[9px] bg-blue-100 text-blue-800 px-1.5 py-0.2 rounded font-semibold shrink-0">BT ESC/POS</span>
                                </div>
                                <p class="text-[11px] text-[#5f5e61] mt-0.5">
                                    Estado: {{ p.status }} <span v-if="p.port" class="font-mono text-[10px] text-[#4648d4]">({{ p.port }})</span>
                                </p>
                            </div>
                            <div class="flex items-center space-x-1 shrink-0">
                                <button
                                    type="button"
                                    class="p-1.5 text-xs text-[#4648d4] hover:bg-[#dce9ff] rounded transition cursor-pointer"
                                    title="Imprimir ticket de prueba"
                                    @click="testPrinter(p.name)"
                                >
                                    <span class="material-symbols-outlined text-[16px]">receipt</span>
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-xs text-emerald-700 hover:bg-emerald-100 rounded transition cursor-pointer"
                                    title="Probar pulso de gaveta"
                                    @click="testDrawer(p.name)"
                                >
                                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 mt-3 border-t border-[#e2e8f0] flex items-center justify-between text-xs text-[#5f5e61]">
                    <span>Spooler Windows + Puertos COM / Bluetooth SPP</span>
                    <span class="text-emerald-700 font-medium">Soporte RAW ESC/POS directo</span>
                </div>
            </div>
        </div>

        <!-- BENTO ROW 2: DISPOSITIVOS BLUETOOTH & PUERTOS SERIALES -->
        <div v-if="agentState === 'connected' && (bluetoothDevices.length > 0 || serialPorts.length > 0)" class="bg-white p-5 rounded-xl border border-[#e2e8f0] shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-blue-600 text-[20px]">bluetooth_searching</span>
                    <h3 class="text-sm font-bold text-[#0b1c30]">Dispositivos Bluetooth & Periféricos Seriales en Windows</h3>
                </div>
                <span class="text-xs text-[#5f5e61]">
                    {{ bluetoothDevices.length }} Bluetooth · {{ serialPorts.length }} Puertos COM
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                <!-- Dispositivos Bluetooth -->
                <div
                    v-for="bt in bluetoothDevices"
                    :key="bt.instanceId"
                    class="p-3 rounded-lg border flex items-center justify-between"
                    :class="bt.isPrinter ? 'bg-blue-50/60 border-blue-200' : 'bg-zinc-50/80 border-zinc-200'"
                >
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center space-x-1.5">
                            <span class="material-symbols-outlined text-[18px]" :class="bt.isPrinter ? 'text-blue-600' : 'text-zinc-500'">
                                {{ bt.isPrinter ? 'print' : 'bluetooth' }}
                            </span>
                            <span class="text-xs font-bold text-[#0b1c30] truncate" :title="bt.name">{{ bt.name }}</span>
                        </div>
                        <p class="text-[11px] text-[#5f5e61] mt-0.5 flex items-center space-x-1">
                            <span>{{ bt.isPrinter ? 'Impresora Bluetooth' : 'Dispositivo Vinculado' }}</span>
                            <span v-if="bt.port" class="font-mono text-[10px] bg-blue-100 text-blue-800 px-1 rounded">{{ bt.port }}</span>
                            <span class="text-emerald-700 font-semibold text-[10px]">• {{ bt.status }}</span>
                        </p>
                    </div>

                    <button
                        v-if="bt.isPrinter"
                        type="button"
                        class="p-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded font-medium transition cursor-pointer flex items-center space-x-1 shrink-0"
                        title="Probar impresión en impresora Bluetooth"
                        @click="testPrinter(bt.port || bt.name)"
                    >
                        <span class="material-symbols-outlined text-[14px]">receipt</span>
                        <span>Test</span>
                    </button>
                </div>

                <!-- Puertos Seriales COM -->
                <div
                    v-for="sp in serialPorts"
                    :key="sp.port"
                    class="p-3 rounded-lg border bg-zinc-50 border-zinc-200 flex items-center justify-between"
                >
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center space-x-1.5">
                            <span class="material-symbols-outlined text-zinc-600 text-[18px]">cable</span>
                            <span class="text-xs font-bold font-mono text-[#0b1c30]">{{ sp.port }}</span>
                            <span v-if="sp.isBluetooth" class="text-[9px] bg-blue-100 text-blue-800 px-1 py-0.2 rounded font-semibold">Vínculo BT</span>
                        </div>
                        <p class="text-[11px] text-[#5f5e61] truncate mt-0.5" :title="sp.name">{{ sp.name }}</p>
                    </div>

                    <button
                        type="button"
                        class="p-1.5 text-xs text-[#4648d4] hover:bg-[#dce9ff] rounded transition cursor-pointer"
                        title="Enviar prueba directa a este puerto COM"
                        @click="testPrinter(sp.port)"
                    >
                        <span class="material-symbols-outlined text-[16px]">send</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- LISTA DE TERMINALES REGISTRADAS -->
        <div class="bg-white rounded-xl border border-[#e2e8f0] shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e2e8f0] flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold font-geist text-[#0b1c30]">Terminales de la Empresa</h3>
                    <p class="text-xs text-[#5f5e61] mt-0.5">Equipos autorizados para vincularse al hardware de punto de venta.</p>
                </div>
                <span class="text-xs text-[#5f5e61]">{{ terminals.length }} registradas</span>
            </div>

            <div v-if="loading && terminals.length === 0" class="py-12 text-center text-xs text-zinc-400">
                Cargando terminales...
            </div>

            <div v-else-if="terminals.length === 0" class="py-12 text-center text-zinc-400 text-xs">
                <span class="material-symbols-outlined text-[40px] text-zinc-300 block mb-2">devices</span>
                No hay terminales registradas. Haz clic en "Registrar Terminal" para asociar un equipo POS.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#eff4ff] text-[11px] font-semibold text-[#5f5e61] uppercase tracking-wider">
                            <th class="py-3 px-5">ID de Terminal</th>
                            <th class="py-3 px-5">Sucursal</th>
                            <th class="py-3 px-5">Caja Asignada</th>
                            <th class="py-3 px-5">Token</th>
                            <th class="py-3 px-5">Estado</th>
                            <th class="py-3 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e2e8f0]">
                        <tr v-for="term in terminals" :key="term.id" class="hover:bg-[#f8f9ff] transition-colors">
                            <td class="py-3 px-5 font-mono font-bold text-[#0b1c30]">
                                {{ term.terminal_id }}
                            </td>
                            <td class="py-3 px-5 text-[#5f5e61]">
                                {{ term.branch?.name ?? '—' }}
                            </td>
                            <td class="py-3 px-5 text-[#5f5e61]">
                                <span v-if="term.cash_register" class="bg-zinc-100 px-2 py-0.5 rounded font-mono text-[11px]">
                                    {{ term.cash_register.code }} · {{ term.cash_register.name }}
                                </span>
                                <span v-else class="text-zinc-400 italic">Sin caja específica</span>
                            </td>
                            <td class="py-3 px-5 font-mono text-zinc-500">
                                ••••••••{{ term.token_last4 }}
                            </td>
                            <td class="py-3 px-5">
                                <button
                                    type="button"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold transition cursor-pointer"
                                    :class="term.active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600'"
                                    @click="handleToggle(term)"
                                >
                                    {{ term.active ? 'Activa' : 'Inactiva' }}
                                </button>
                            </td>
                            <td class="py-3 px-5 text-right space-x-1">
                                <button
                                    type="button"
                                    class="p-1.5 text-[#4648d4] hover:bg-[#eff4ff] rounded transition cursor-pointer"
                                    title="Rotar credencial de terminal"
                                    @click="handleRotateToken(term)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">key</span>
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                    title="Eliminar terminal"
                                    @click="handleDelete(term)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL PARA REGISTRAR TERMINAL -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            @click.self="showCreateModal = false"
        >
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl border border-[#e2e8f0] space-y-4">
                <div class="flex items-center justify-between border-b border-[#e2e8f0] pb-3">
                    <h3 class="text-lg font-bold font-geist text-[#0b1c30]">Registrar Terminal Windows</h3>
                    <button type="button" class="text-zinc-400 hover:text-zinc-600" @click="showCreateModal = false">✕</button>
                </div>

                <form class="space-y-4" @submit.prevent="handleCreate">
                    <div>
                        <label class="block text-xs font-semibold text-[#0b1c30] mb-1">
                            ID de Terminal (Único en la empresa) *
                        </label>
                        <input
                            v-model="formTerminalId"
                            type="text"
                            required
                            maxlength="100"
                            placeholder="Ej. POS-CAJA-01"
                            class="w-full bg-[#f8f9ff] border border-[#c7c4d7]/60 rounded-lg px-3 py-2 text-xs font-mono text-[#0b1c30] outline-none focus:border-[#4648d4]"
                        >
                        <p class="text-[10px] text-[#5f5e61] mt-1">Usa letras, números, guiones o dos puntos.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#0b1c30] mb-1">
                            Sucursal *
                        </label>
                        <select
                            v-model="formBranchId"
                            required
                            class="w-full bg-[#f8f9ff] border border-[#c7c4d7]/60 rounded-lg px-3 py-2 text-xs text-[#0b1c30] outline-none focus:border-[#4648d4]"
                            @change="formCashRegisterId = ''"
                        >
                            <option value="" disabled>Selecciona una sucursal</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#0b1c30] mb-1">
                            Caja de Punto de Venta (Opcional)
                        </label>
                        <select
                            v-model="formCashRegisterId"
                            :disabled="!formBranchId"
                            class="w-full bg-[#f8f9ff] border border-[#c7c4d7]/60 rounded-lg px-3 py-2 text-xs text-[#0b1c30] outline-none focus:border-[#4648d4] disabled:opacity-50"
                        >
                            <option value="">Sin caja asignada fija</option>
                            <option v-for="r in availableRegisters" :key="r.id" :value="r.id">
                                {{ r.code }} · {{ r.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-[#e2e8f0]">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg text-xs font-semibold text-zinc-600 hover:bg-zinc-100 transition cursor-pointer"
                            @click="showCreateModal = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting || !formTerminalId || !formBranchId"
                            class="bg-[#4648d4] hover:bg-[#393bb3] text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-xs transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ submitting ? 'Generando...' : 'Generar Token y Registrar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
