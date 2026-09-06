<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from '../../auth/stores/session';
import { useModuleStore } from '../../module-manager/stores/modules';
import RestaurantDashboard from '../components/RestaurantDashboard.vue';
import WorkshopDashboard from '../components/WorkshopDashboard.vue';
import BarbershopDashboard from '../components/BarbershopDashboard.vue';
import RetailDashboard from '../components/RetailDashboard.vue';

const { t } = useI18n();
const session = useSessionStore();
const modules = useModuleStore();

const operatorName = computed(() => session.user?.name ?? 'Carlos Mendez');
const companyName = computed(() => session.company?.name ?? 'BSM-POS');
const currentPeriod = ref<'today' | 'week' | 'month'>('today');

const verticalTitle = computed(() => {
    if (modules.isRestaurant) return 'Restaurante & Gastronomía';
    if (modules.isWorkshop) return 'Taller Mecánico & Servicios';
    if (modules.isBarbershop) return 'Barbería & Salón de Belleza';
    return 'Retail & Comercio General';
});

const verticalIcon = computed(() => {
    if (modules.isRestaurant) return 'restaurant';
    if (modules.isWorkshop) return 'car_repair';
    if (modules.isBarbershop) return 'content_cut';
    return 'storefront';
});

onMounted(() => {
    if (session.isAuthenticated && session.hasContext) {
        void modules.loadModules();
    }
});
</script>

<template>
    <div class="flex flex-col w-full pb-16">
        <!-- TÍTULO ACCESIBLE PARA E2E Y LECTORES DE PANTALLA -->
        <h1 class="sr-only">{{ t('dashboard.title') }}</h1>

        <!-- SMART GREETING & PERIOD HEADER -->
        <div class="px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="text-[11px] uppercase tracking-wider font-semibold text-[#4648d4] px-2 py-0.5 rounded bg-[#e5eeff] flex items-center">
                        <span class="material-symbols-outlined text-[14px] mr-1">{{ verticalIcon }}</span>
                        {{ verticalTitle }}
                    </span>
                    <span class="text-xs text-[#767586]">•</span>
                    <span class="text-xs text-[#5f5e61] font-medium">{{ companyName }}</span>
                </div>
                <h2 class="text-2xl md:text-3xl font-bold font-geist text-[#0b1c30] tracking-tight">
                    {{ t('dashboard.greeting', { name: operatorName }) }}
                </h2>
            </div>

            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                <div class="flex bg-[#e5eeff] p-1 rounded-lg">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                        :class="currentPeriod === 'today' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61] hover:text-[#0b1c30]'"
                        @click="currentPeriod = 'today'"
                    >
                        {{ t('common.today') }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                        :class="currentPeriod === 'week' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61] hover:text-[#0b1c30]'"
                        @click="currentPeriod = 'week'"
                    >
                        {{ t('common.thisWeek') }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors cursor-pointer"
                        :class="currentPeriod === 'month' ? 'bg-white text-[#0b1c30] shadow-xs' : 'text-[#5f5e61] hover:text-[#0b1c30]'"
                        @click="currentPeriod = 'month'"
                    >
                        {{ t('common.thisMonth') }}
                    </button>
                </div>

                <RouterLink
                    to="/reportes"
                    class="bg-[#e5eeff] hover:bg-[#dce9ff] text-[#0b1c30] px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center transition-all shadow-2xs"
                >
                    <span class="material-symbols-outlined mr-1.5 text-[18px]">download</span>
                    <span>{{ t('common.exportReport') }}</span>
                </RouterLink>
            </div>
        </div>

        <!-- DYNAMIC DEDICATED VERTICAL CONTENT -->
        <div class="px-6">
            <RestaurantDashboard v-if="modules.isRestaurant" />
            <WorkshopDashboard v-else-if="modules.isWorkshop" />
            <BarbershopDashboard v-else-if="modules.isBarbershop" />
            <RetailDashboard v-else />
        </div>
    </div>
</template>
