import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';

export interface TwoFactorStatus {
    enabled: boolean;
}

export interface TwoFactorSetup {
    secret: string;
    otpauth_uri: string;
}

export async function fetchTwoFactorStatus(): Promise<TwoFactorStatus> {
    const response = await api.get<ApiEnvelope<TwoFactorStatus>>('/auth/2fa');
    return response.data.data;
}

export async function beginTwoFactor(): Promise<TwoFactorSetup> {
    const response = await api.post<ApiEnvelope<TwoFactorSetup>>('/auth/2fa/enable');
    return response.data.data;
}

export async function confirmTwoFactor(code: string): Promise<TwoFactorStatus> {
    const response = await api.post<ApiEnvelope<TwoFactorStatus>>('/auth/2fa/confirm', { code });
    return response.data.data;
}

export async function disableTwoFactor(code: string): Promise<TwoFactorStatus> {
    const response = await api.post<ApiEnvelope<TwoFactorStatus>>('/auth/2fa/disable', { code });
    return response.data.data;
}
