<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { api } from '../../../lib/api';

interface Table {
    id: string;
    table_number: string;
    seating_capacity: number;
    status: 'available' | 'occupied' | 'reserved';
    active_order: {
        id: string;
        order_number: string;
        total: number;
        customer_name?: string;
        created_at: string;
    } | null;
}

interface Area {
    id: string;
    name: string;
    tables: Table[];
}

const areas = ref<Area[]>([]);
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

// Gestión de Transferencia
const showTransferModal = ref(false);
const sourceTable = ref<Table | null>(null);
const destinationTableId = ref('');
const availableTablesForTransfer = ref<Table[]>([]);

async function loadLayout() {
    loading.value = true;
    errorMsg.value = '';
    try {
        const response = await api.get('/restaurant/layout');
        areas.value = response.data.data;
    } catch {
        errorMsg.value = 'Error al cargar el layout de mesas.';
    } finally {
        loading.value = false;
    }
}

async function openTable(table: Table) {
    loading.value = true;
    errorMsg.value = '';
    try {
        const response = await api.post(`/restaurant/tables/${table.id}/open`, {});
        successMsg.value = `Mesa ${table.table_number} abierta. Redirigiendo al POS...`;

        // Guardar la orden activa en localStorage y redirigir
        const orderData = response.data.data;
        window.localStorage.setItem('active_order_id', orderData.active_order_id);
        window.localStorage.setItem('active_order_number', orderData.order_number);

        window.setTimeout(() => {
            window.location.href = '/pos';
        }, 1000);
    } catch (err: unknown) {
        const error = err as { response?: { data?: { error?: { message?: string } } } };
        errorMsg.value = error.response?.data?.error?.message || 'Error al abrir la mesa.';
        loading.value = false;
    }
}

function startTransfer(table: Table) {
    sourceTable.value = table;
    destinationTableId.value = '';

    // Obtener mesas libres del mismo o de otros sectores
    const list: Table[] = [];
    areas.value.forEach((area) => {
        area.tables.forEach((t) => {
            if (t.status === 'available' && t.id !== table.id) {
                list.push(t);
            }
        });
    });
    availableTablesForTransfer.value = list;
    showTransferModal.value = true;
}

async function executeTransfer() {
    if (!sourceTable.value || !destinationTableId.value) return;
    loading.value = true;
    errorMsg.value = '';
    try {
        await api.post(`/restaurant/tables/${sourceTable.value.id}/transfer`, {
            destination_table_id: destinationTableId.value,
        });

        successMsg.value = 'Cuenta transferida con éxito.';
        showTransferModal.value = false;
        await loadLayout();
    } catch (err: unknown) {
        const error = err as { response?: { data?: { error?: { message?: string } } } };
        errorMsg.value = error.response?.data?.error?.message || 'Error al transferir la mesa.';
    } finally {
        loading.value = false;
    }
}

function goToPos(table: Table) {
    if (!table.active_order) return;
    window.localStorage.setItem('active_order_id', table.active_order.id);
    window.localStorage.setItem('active_order_number', table.active_order.order_number);
    window.location.href = '/pos';
}

function getMinutesElapsed(createdAt: string): number {
    const start = new Date(createdAt).getTime();
    const now = Date.now();
    return Math.floor((now - start) / 60000);
}

onMounted(() => {
    loadLayout();
});
</script>

<template>
    <div class="min-h-screen bg-kinetic-surface p-6">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-kinetic-ink">Control de Mesas</h1>
                <p class="text-sm text-gray-500 mt-1">Gestión interactiva del plano de comensales en restaurante.</p>
            </div>
            <button
                :disabled="loading"
                class="px-4 py-2 bg-[#3525cd] hover:bg-[#271aa3] text-white font-semibold rounded-xl shadow transition duration-200"
                @click="loadLayout"
            >
                🔄 Actualizar Plano
            </button>
        </header>

        <!-- Mensajes de Estado -->
        <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl text-red-700 text-sm">
            ⚠️ {{ errorMsg }}
        </div>
        <div
            v-if="successMsg"
            class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-xl text-green-700 text-sm"
        >
            ✅ {{ successMsg }}
        </div>

        <!-- LAYOUT DE ÁREAS -->
        <div v-if="loading && areas.length === 0" class="text-center py-12 text-gray-400">
            Cargando el plano del salón...
        </div>
        <div v-else class="space-y-10">
            <section
                v-for="area in areas"
                :key="area.id"
                class="bg-white rounded-3xl p-6 border border-[#c7c4d8]/40 shadow-sm"
            >
                <h2 class="text-xl font-bold text-[#302f39] mb-6 flex items-center gap-2">
                    📍 Sector: {{ area.name }}
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                    <div
                        v-for="t in area.tables"
                        :key="t.id"
                        :class="[
                            'relative rounded-2xl p-5 border flex flex-col justify-between min-h-[160px] transition duration-300 shadow-sm',
                            t.status === 'occupied' ? 'bg-red-50/40 border-red-200' : 'bg-green-50/40 border-green-200',
                        ]"
                    >
                        <!-- Cabecera de la mesa -->
                        <div class="flex justify-between items-start">
                            <span class="text-lg font-bold text-[#302f39]">
                                {{ t.table_number }}
                            </span>
                            <span class="text-xs text-gray-400"> 👤 {{ t.seating_capacity }} </span>
                        </div>

                        <!-- Estado y Totales -->
                        <div class="my-3 flex-1 flex flex-col justify-center">
                            <div v-if="t.status === 'occupied' && t.active_order" class="space-y-1">
                                <p class="text-xs font-semibold text-red-600">Ocupada</p>
                                <p class="text-base font-black text-red-700">
                                    RD$ {{ Number(t.active_order.total).toFixed(2) }}
                                </p>
                                <p class="text-[10px] text-gray-400">
                                    ⏱️ {{ getMinutesElapsed(t.active_order.created_at) }} min transcurridos
                                </p>
                            </div>
                            <div v-else>
                                <p class="text-xs font-semibold text-green-600">Disponible</p>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex gap-2 mt-2">
                            <button
                                v-if="t.status === 'available'"
                                class="w-full text-xs bg-[#3525cd] hover:bg-[#271aa3] text-white py-1.5 px-3 rounded-lg font-semibold transition"
                                @click="openTable(t)"
                            >
                                Abrir Cuenta
                            </button>
                            <div v-else class="w-full flex gap-1">
                                <button
                                    class="flex-1 text-xs bg-red-600 hover:bg-red-700 text-white py-1.5 rounded-lg font-semibold transition"
                                    @click="goToPos(t)"
                                >
                                    Ver POS
                                </button>
                                <button
                                    class="bg-gray-100 hover:bg-gray-200 text-[#302f39] py-1.5 px-2 rounded-lg font-bold transition"
                                    title="Transferir Mesa"
                                    @click="startTransfer(t)"
                                >
                                    ↔️
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- MODAL DE TRANSFERENCIA -->
        <div v-if="showTransferModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-xl border border-[#c7c4d8] space-y-6">
                <div>
                    <h3 class="text-xl font-bold text-[#302f39]">Transferir Cuenta</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Mover cuenta de la mesa {{ sourceTable?.table_number }} a otro destino libre.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#464555] uppercase mb-1">Mesa Destino Libre</label>
                    <select
                        v-model="destinationTableId"
                        class="w-full min-h-12 rounded-xl border border-[#c7c4d8] px-3 focus:ring-[#3525cd]"
                    >
                        <option value="">Seleccione una mesa libre...</option>
                        <option v-for="t in availableTablesForTransfer" :key="t.id" :value="t.id">
                            {{ t.table_number }} ({{ t.seating_capacity }} personas)
                        </option>
                    </select>
                </div>

                <div class="flex gap-3 justify-end">
                    <button
                        class="px-4 py-2 border border-[#c7c4d8] rounded-xl text-gray-700 font-semibold"
                        @click="showTransferModal = false"
                    >
                        Cancelar
                    </button>
                    <button
                        :disabled="!destinationTableId || loading"
                        class="px-5 py-2 bg-[#3525cd] hover:bg-[#271aa3] text-white font-semibold rounded-xl shadow transition"
                        @click="executeTransfer"
                    >
                        Confirmar Transferencia
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
