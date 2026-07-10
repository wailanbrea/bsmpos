<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { api } from '../../../lib/api';

interface KitchenItem {
    id: string;
    order_id: string;
    order_number: string;
    table_number: string;
    product_name: string;
    quantity: string;
    notes: string | null;
    status: 'pending' | 'cooking' | 'ready';
    created_at: string;
}

const items = ref<KitchenItem[]>([]);
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

async function loadKds() {
    loading.value = true;
    errorMsg.value = '';
    try {
        const response = await api.get('/kitchen/kds');
        items.value = response.data.data;
    } catch {
        errorMsg.value = 'Error al cargar comandas de cocina.';
    } finally {
        loading.value = false;
    }
}

async function updateStatus(item: KitchenItem, status: 'cooking' | 'ready' | 'delivered') {
    loading.value = true;
    errorMsg.value = '';
    try {
        await api.post(`/kitchen/items/${item.id}/status`, { status });
        successMsg.value = `Comanda actualizada a: ${status}.`;
        await loadKds();
    } catch (err: unknown) {
        const error = err as { response?: { data?: { error?: { message?: string } } } };
        errorMsg.value = error.response?.data?.error?.message || 'Error al actualizar comanda.';
    } finally {
        loading.value = false;
    }
}

function getMinutesElapsed(createdAt: string): number {
    const start = new Date(createdAt).getTime();
    const now = Date.now();
    return Math.floor((now - start) / 60000);
}

function getTimeColor(createdAt: string): string {
    const min = getMinutesElapsed(createdAt);
    if (min > 20) return 'text-red-600 bg-red-50 border-red-200';
    if (min > 10) return 'text-orange-600 bg-orange-50 border-orange-200';
    return 'text-green-600 bg-green-50 border-green-200';
}

onMounted(() => {
    loadKds();
    // Auto-recarga cada 10 segundos
    window.setInterval(() => {
        loadKds();
    }, 10000);
});
</script>

<template>
    <div class="min-h-screen bg-[#f2eff9] p-6 text-[#302f39]">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-kinetic-ink">KDS - Pantalla de Cocina</h1>
                <p class="text-sm text-gray-500 mt-1">Control de comandas de alimentos y bebidas en tiempo real.</p>
            </div>
            <button
                :disabled="loading"
                class="px-4 py-2 bg-[#3525cd] hover:bg-[#271aa3] text-white font-semibold rounded-xl shadow transition"
                @click="loadKds"
            >
                🔄 Recargar
            </button>
        </header>

        <!-- Alertas de estado -->
        <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl text-red-700 text-sm">
            ⚠️ {{ errorMsg }}
        </div>

        <div v-if="loading && items.length === 0" class="text-center py-12 text-gray-400">
            Cargando cola de comandas...
        </div>

        <div v-else-if="items.length === 0" class="h-[60vh] flex flex-col items-center justify-center text-gray-400">
            <span class="text-5xl mb-4">🍳</span>
            <p class="text-lg font-bold">¡Cocina al día!</p>
            <p class="text-xs mt-1">No hay comandas pendientes de preparación.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div
                v-for="item in items"
                :key="item.id"
                class="bg-white rounded-3xl p-5 border border-[#c7c4d8]/40 shadow-sm flex flex-col justify-between space-y-4"
            >
                <!-- Cabecera de comanda -->
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-lg font-black block text-[#302f39]">
                            {{ item.table_number }}
                        </span>
                        <span class="text-[10px] text-gray-400 font-semibold block">
                            Orden: {{ item.order_number }}
                        </span>
                    </div>

                    <!-- Tiempo Transcurrido -->
                    <span :class="['text-xs px-2.5 py-1 rounded-full font-bold border', getTimeColor(item.created_at)]">
                        ⏱️ {{ getMinutesElapsed(item.created_at) }} min
                    </span>
                </div>

                <!-- Detalle del Plato -->
                <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100 flex-1">
                    <div class="flex items-baseline gap-2">
                        <span class="text-xl font-black text-[#3525cd]"> {{ Number(item.quantity).toFixed(0) }}x </span>
                        <span class="text-base font-bold text-[#302f39]">
                            {{ item.product_name }}
                        </span>
                    </div>

                    <!-- Notas de preparación -->
                    <div
                        v-if="item.notes"
                        class="mt-2.5 pt-2 border-t border-dashed border-gray-200 text-xs text-orange-600 font-semibold"
                    >
                        ✏️ Nota: {{ item.notes }}
                    </div>
                </div>

                <!-- Botones de Transición KDS -->
                <div class="flex gap-2">
                    <button
                        v-if="item.status === 'pending'"
                        class="w-full min-h-12 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition shadow-sm text-sm"
                        @click="updateStatus(item, 'cooking')"
                    >
                        🍳 Empezar
                    </button>
                    <button
                        v-if="item.status === 'cooking'"
                        class="w-full min-h-12 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-sm text-sm"
                        @click="updateStatus(item, 'ready')"
                    >
                        🔔 Listo (Servir)
                    </button>
                    <button
                        v-if="item.status === 'ready'"
                        class="w-full min-h-12 bg-[#3525cd] hover:bg-[#271aa3] text-white font-bold rounded-xl transition shadow-sm text-sm"
                        @click="updateStatus(item, 'delivered')"
                    >
                        🚚 Entregar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
