import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { BusinessTypesResponse, ModuleIndexResponse } from './types';

export async function fetchModules(): Promise<ModuleIndexResponse> {
    const response = await api.get<ApiEnvelope<ModuleIndexResponse>>('/modules');
    return response.data.data;
}

export async function enableModule(code: string): Promise<string[]> {
    const response = await api.post<ApiEnvelope<{ enabled: string[] }>>(`/modules/${code}/enable`);
    return response.data.data.enabled;
}

export async function disableModule(code: string): Promise<string[]> {
    const response = await api.post<ApiEnvelope<{ enabled: string[] }>>(`/modules/${code}/disable`);
    return response.data.data.enabled;
}

export async function fetchBusinessTypes(businessType?: string): Promise<BusinessTypesResponse> {
    const response = await api.get<ApiEnvelope<BusinessTypesResponse>>('/business-types', {
        params: businessType ? { business_type: businessType } : undefined,
    });
    return response.data.data;
}

export async function completeOnboarding(businessType: string, modules: string[]): Promise<string[]> {
    const response = await api.post<ApiEnvelope<{ enabled: string[] }>>('/onboarding', {
        business_type: businessType,
        modules,
    });
    return response.data.data.enabled;
}
