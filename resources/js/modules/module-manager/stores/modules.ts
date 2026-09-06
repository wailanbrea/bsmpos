import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchModules } from '../services';
import type { SystemModule } from '../types';

export const useModuleStore = defineStore('modules', () => {
    const modules = ref<SystemModule[]>([]);
    const enabled = ref<string[]>([]);
    const hasBusinessType = ref(false);
    const businessTypeCode = ref<string | null>(null);
    const loaded = ref(false);
    const loading = ref(false);
    const companyId = ref<string | null>(null);

    const enabledSet = computed(() => new Set(enabled.value));

    function canUse(code: string): boolean {
        return enabledSet.value.has(code);
    }

    function isCore(code: string): boolean {
        return modules.value.find((module) => module.code === code)?.is_core ?? false;
    }

    const isRestaurant = computed(() => {
        return (
            businessTypeCode.value === 'restaurant' ||
            businessTypeCode.value === 'cafeteria' ||
            businessTypeCode.value === 'food_truck' ||
            canUse('restaurant')
        );
    });

    const isWorkshop = computed(() => {
        return (
            businessTypeCode.value === 'mechanic' ||
            businessTypeCode.value === 'auto_parts' ||
            canUse('work_order') ||
            canUse('vehicle')
        );
    });

    const isBarbershop = computed(() => {
        return (
            businessTypeCode.value === 'barbershop' ||
            (canUse('appointment') && canUse('employee') && !isWorkshop.value)
        );
    });

    const isRetail = computed(() => {
        return !isRestaurant.value && !isWorkshop.value && !isBarbershop.value;
    });

    function ensureCompany(activeCompanyId: string | null): void {
        if (activeCompanyId !== companyId.value) {
            companyId.value = activeCompanyId;
            loaded.value = false;
        }
    }

    async function loadModules(force = false): Promise<void> {
        if (loaded.value && !force) {
            return;
        }

        loading.value = true;

        try {
            const data = await fetchModules();
            modules.value = data.modules;
            enabled.value = data.enabled;
            hasBusinessType.value = data.business_type;
            businessTypeCode.value = data.business_type_code ?? null;
            loaded.value = true;
        } finally {
            loading.value = false;
        }
    }

    function setEnabled(codes: string[]): void {
        enabled.value = codes;
        modules.value = modules.value.map((module) => ({
            ...module,
            is_enabled: module.is_core || codes.includes(module.code),
        }));
    }

    function reset(): void {
        modules.value = [];
        enabled.value = [];
        hasBusinessType.value = false;
        businessTypeCode.value = null;
        loaded.value = false;
        companyId.value = null;
    }

    return {
        modules,
        enabled,
        hasBusinessType,
        businessTypeCode,
        isRestaurant,
        isWorkshop,
        isBarbershop,
        isRetail,
        loaded,
        loading,
        companyId,
        canUse,
        isCore,
        ensureCompany,
        loadModules,
        setEnabled,
        reset,
    };
});
