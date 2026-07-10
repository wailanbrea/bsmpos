import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';

export interface ReportFilters {
    from: string;
    to: string;
}

export interface ReportResult {
    summary?: Record<string, string | number>;
    rows: Array<Record<string, unknown>>;
}

/** Reportes tabulares con resumen opcional (ventas, caja, desgloses, anulaciones). */
export async function fetchReport(path: string, filters: ReportFilters): Promise<ReportResult> {
    const response = await api.get<ApiEnvelope<ReportResult>>(`/reports/${path}`, {
        params: { from: filters.from, to: filters.to },
    });
    return response.data.data;
}

/** Descarga un archivo (CSV de ventas o TXT DGII) forzando el navegador a guardarlo. */
export async function downloadReport(path: string, params: Record<string, string>, filename: string): Promise<void> {
    const response = await api.get(`/reports/${path}`, { params, responseType: 'blob' });
    const url = window.URL.createObjectURL(response.data as Blob);
    const link = window.document.createElement('a');
    link.href = url;
    link.download = filename;
    window.document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}
