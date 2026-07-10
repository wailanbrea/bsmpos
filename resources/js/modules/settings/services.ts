import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { FiscalSettings, NcfSequence, Tax } from './types';

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
}): Promise<Tax> {
    const response = await api.post<ApiEnvelope<Tax>>('/taxes', payload);
    return response.data.data;
}

export async function createNcfSequence(payload: {
    document_type_code: string;
    start_number: number;
    end_number: number;
    expires_at: string | null;
}): Promise<NcfSequence> {
    const response = await api.post<ApiEnvelope<NcfSequence>>('/ncf-sequences', payload);
    return response.data.data;
}
