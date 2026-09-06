<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
    deleteNotification,
    fetchNotifications,
    markAllNotificationsRead,
    markNotificationRead,
    type NotificationItem,
} from '../modules/notifications/services';

const router = useRouter();

const isOpen = ref(false);
const loading = ref(false);
const notifications = ref<NotificationItem[]>([]);
const unreadCount = ref(0);

// Polling interval (30s)
let pollInterval: ReturnType<typeof setInterval> | null = null;

const hasUnread = computed(() => unreadCount.value > 0);
const badgeLabel = computed(() => (unreadCount.value > 99 ? '99+' : String(unreadCount.value)));

async function load(): Promise<void> {
    loading.value = true;
    try {
        const res = await fetchNotifications(20);
        notifications.value = res.data as NotificationItem[];
        unreadCount.value = res.meta.unread_count;
    } catch {
        // silent — no interrumpir la UI
    } finally {
        loading.value = false;
    }
}

async function markRead(item: NotificationItem): Promise<void> {
    if (!item.is_read) {
        await markNotificationRead(item.id);
        item.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
    if (item.action_url) {
        isOpen.value = false;
        void router.push(item.action_url);
    }
}

async function markAll(): Promise<void> {
    await markAllNotificationsRead();
    notifications.value.forEach((n) => (n.is_read = true));
    unreadCount.value = 0;
}

async function remove(item: NotificationItem): Promise<void> {
    await deleteNotification(item.id);
    notifications.value = notifications.value.filter((n) => n.id !== item.id);
    if (!item.is_read) unreadCount.value = Math.max(0, unreadCount.value - 1);
}

function toggle(): void {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        void load();
    }
}

function closeOnOutside(e: MouseEvent): void {
    const el = document.getElementById('notification-dropdown-root');
    if (el && !el.contains(e.target as Node)) {
        isOpen.value = false;
    }
}

/** Icono Material Symbol por categoría */
function categoryIcon(category: string): string {
    const map: Record<string, string> = {
        inventory: 'inventory_2',
        fiscal: 'receipt_long',
        appointment: 'calendar_month',
        work_order: 'build',
        cash: 'point_of_sale',
        system: 'info',
    };
    return map[category] ?? 'notifications';
}

/** Color de badge por tipo */
function typeColor(type: string): string {
    const map: Record<string, string> = {
        info: 'bg-blue-100 text-blue-600',
        warning: 'bg-amber-100 text-amber-600',
        success: 'bg-emerald-100 text-emerald-600',
        error: 'bg-red-100 text-red-600',
    };
    return map[type] ?? 'bg-zinc-100 text-zinc-500';
}

onMounted(() => {
    void load();
    document.addEventListener('click', closeOnOutside);
    pollInterval = setInterval(() => {
        void load();
    }, 30_000);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeOnOutside);
    if (pollInterval !== null) clearInterval(pollInterval);
});
</script>

<template>
    <div id="notification-dropdown-root" class="relative">
        <!-- CAMPANA CON BADGE -->
        <button
            type="button"
            class="relative p-2 rounded-lg text-[#464554] hover:bg-[#dce9ff]/60 transition-colors focus:outline-none focus:ring-2 focus:ring-[#4648d4]/40"
            aria-label="Notificaciones"
            :aria-expanded="isOpen"
            @click="toggle"
        >
            <span class="material-symbols-outlined text-[20px]">notifications</span>
            <!-- Badge contador -->
            <transition
                enter-active-class="transition-transform duration-200 ease-out"
                enter-from-class="scale-0"
                enter-to-class="scale-100"
                leave-active-class="transition-transform duration-150 ease-in"
                leave-from-class="scale-100"
                leave-to-class="scale-0"
            >
                <span
                    v-if="hasUnread"
                    class="absolute top-1 right-1 min-w-[16px] h-4 flex items-center justify-center rounded-full bg-[#ba1a1a] text-white text-[9px] font-bold leading-none px-0.5 shadow-sm"
                >
                    {{ badgeLabel }}
                </span>
            </transition>
        </button>

        <!-- PANEL FLOTANTE -->
        <transition
            enter-active-class="transition-all duration-200 ease-out origin-top-right"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in origin-top-right"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-[360px] max-h-[520px] flex flex-col bg-white border border-[#e2e8f0] rounded-2xl shadow-2xl z-50 overflow-hidden"
            >
                <!-- Cabecera del panel -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-[#f0f0f7] bg-[#fafbff]">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-[#4648d4]">notifications</span>
                        <span class="text-sm font-semibold text-[#0b1c30]">Notificaciones</span>
                        <span
                            v-if="hasUnread"
                            class="bg-[#4648d4] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none"
                        >
                            {{ unreadCount }} sin leer
                        </span>
                    </div>
                    <button
                        v-if="hasUnread"
                        type="button"
                        class="text-[11px] text-[#4648d4] font-medium hover:underline transition"
                        @click="markAll"
                    >
                        Marcar todas
                    </button>
                </div>

                <!-- Lista de notificaciones -->
                <div class="overflow-y-auto flex-1">
                    <!-- Estado cargando -->
                    <div v-if="loading && notifications.length === 0" class="flex flex-col items-center py-12 text-zinc-400">
                        <span class="material-symbols-outlined text-[36px] animate-spin">progress_activity</span>
                        <p class="text-xs mt-2">Cargando...</p>
                    </div>

                    <!-- Sin notificaciones -->
                    <div
                        v-else-if="notifications.length === 0"
                        class="flex flex-col items-center py-12 text-zinc-400"
                    >
                        <span class="material-symbols-outlined text-[40px] mb-2">notifications_off</span>
                        <p class="text-sm font-medium">Sin notificaciones</p>
                        <p class="text-xs mt-1 text-zinc-400">Todo está en orden 🎉</p>
                    </div>

                    <!-- Ítems -->
                    <template v-else>
                        <div
                            v-for="item in notifications"
                            :key="item.id"
                            class="group relative flex gap-3 px-4 py-3 border-b border-[#f4f4f9] last:border-0 hover:bg-[#f8f9ff] transition-colors cursor-pointer"
                            :class="{ 'bg-[#f0f4ff]': !item.is_read }"
                            @click="markRead(item)"
                        >
                            <!-- Punto de no leído -->
                            <div
                                v-if="!item.is_read"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-[#4648d4]"
                            />

                            <!-- Icono de categoría -->
                            <div
                                class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center mt-0.5"
                                :class="typeColor(item.type)"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ categoryIcon(item.category) }}</span>
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-[#0b1c30] leading-tight line-clamp-1">
                                    {{ item.title }}
                                </p>
                                <p class="text-[11px] text-zinc-500 mt-0.5 line-clamp-2 leading-tight">
                                    {{ item.message }}
                                </p>
                                <p class="text-[10px] text-zinc-400 mt-1 font-medium">
                                    {{ item.created_ago }}
                                </p>
                            </div>

                            <!-- Botón eliminar (visible al hover) -->
                            <button
                                type="button"
                                class="shrink-0 p-1 rounded-lg text-zinc-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all self-start mt-0.5"
                                aria-label="Eliminar notificación"
                                @click.stop="remove(item)"
                            >
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Pie del panel -->
                <div class="px-4 py-2.5 border-t border-[#f0f0f7] bg-[#fafbff] text-center">
                    <router-link
                        to="/configuracion"
                        class="text-[11px] text-[#4648d4] font-medium hover:underline"
                        @click="isOpen = false"
                    >
                        Ver configuración del sistema →
                    </router-link>
                </div>
            </div>
        </transition>
    </div>
</template>