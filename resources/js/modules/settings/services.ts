import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { CompanyProfile, ExchangeRate, FiscalSettings, NcfSequence, PaymentMethod, SettingsGroup, Tax } from './types';

export async function fetchCompanyProfile(): Promise<CompanyProfile> {
    const response = await api.get<ApiEnvelope<CompanyProfile>>('/company/profile');
    return response.data.data;
}

export async function updateCompanyProfile(payload: Partial<CompanyProfile>): Promise<CompanyProfile> {
    const response = await api.patch<ApiEnvelope<CompanyProfile>>('/company/profile', payload);
    return response.data.data;
}

export async function fetchFiscalSettings(): Promise<FiscalSettings> {
    const response = await api.get<ApiEnvelope<FiscalSettings>>('/settings/fiscal');
    return response.data.data;
}

export async function fetchNcfSequences(): Promise<NcfSequence[]> {
    const response = await api.get<ApiEnvelope<NcfSequence[]>>('/ncf-sequences');
    return response.data.data;
}

export async function createTax(payload: {
    name: string;
    code: string;
    rate: number;
    type: string;
    scope: string;
    is_inclusive?: boolean;
    is_retention?: boolean;
}): Promise<Tax> {
    const response = await api.post<ApiEnvelope<Tax>>('/taxes', payload);
    return response.data.data;
}

export async function updateTax(
    publicId: string,
    payload: {
        name?: string;
        rate?: number;
        type?: string;
        scope?: string;
        is_inclusive?: boolean;
        is_retention?: boolean;
        is_active?: boolean;
    },
): Promise<Tax> {
    const response = await api.patch<ApiEnvelope<Tax>>(`/taxes/${publicId}`, payload);
    return response.data.data;
}

export async function createPaymentMethod(payload: {
    name: string;
    code: string;
    requires_reference?: boolean;
}): Promise<PaymentMethod> {
    const response = await api.post<ApiEnvelope<PaymentMethod>>('/payment-methods', payload);
    return response.data.data;
}

export async function updatePaymentMethod(
    publicId: string,
    payload: {
        name?: string;
        requires_reference?: boolean;
        is_active?: boolean;
    },
): Promise<PaymentMethod> {
    const response = await api.patch<ApiEnvelope<PaymentMethod>>(`/payment-methods/${publicId}`, payload);
    return response.data.data;
}

export async function createNcfSequence(payload: {
    document_type_code: string;
    start_number: number;
    end_number: number;
    expires_at: string | null;
    alert_threshold?: number;
}): Promise<NcfSequence> {
    const response = await api.post<ApiEnvelope<NcfSequence>>('/ncf-sequences', payload);
    return response.data.data;
}

export async function updateNcfSequence(
    id: number,
    payload: {
        end_number?: number;
        alert_threshold?: number;
        expires_at?: string | null;
        is_active?: boolean;
    },
): Promise<NcfSequence> {
    const response = await api.patch<ApiEnvelope<NcfSequence>>(`/ncf-sequences/${id}`, payload);
    return response.data.data;
}

export async function fetchSettingsGroup(group: string): Promise<SettingsGroup> {
    const response = await api.get<ApiEnvelope<SettingsGroup>>(`/settings/${group}`);
    return response.data.data;
}

export async function saveSettingsGroup(group: string, values: Record<string, unknown>): Promise<SettingsGroup> {
    const response = await api.put<ApiEnvelope<SettingsGroup>>(`/settings/${group}`, { values });
    return response.data.data;
}

export async function fetchExchangeRates(): Promise<ExchangeRate[]> {
    const response = await api.get<ApiEnvelope<ExchangeRate[]>>('/exchange-rates');
    return response.data.data;
}

export async function createExchangeRate(payload: {
    currency_code: string;
    rate: number;
    effective_date: string;
}): Promise<ExchangeRate> {
    const response = await api.post<ApiEnvelope<ExchangeRate>>('/exchange-rates', payload);
    return response.data.data;
}