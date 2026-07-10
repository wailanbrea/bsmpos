import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchModules } from '../services';
import type { SystemModule } from '../types';

export const useModuleStore = defineStore('modules', () => {
    const modules = ref<SystemModule[]>([]);
    const enabled = ref<string[]>([]);
    const hasBusinessType = ref(false);
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
        loaded.value = false;
        companyId.value = null;
    }

    return {
        modules,
        enabled,
        hasBusinessType,
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
