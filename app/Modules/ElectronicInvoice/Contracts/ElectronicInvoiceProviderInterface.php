<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Contracts;

use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\Invoice\Models\Invoice;

/**
 * Contrato único para todo proveedor de facturación electrónica (DGII directo
 * o PSFE certificado). Regla del master prompt §19: la lógica electrónica vive
 * SOLO detrás de esta interface; el resto del sistema no conoce al proveedor.
 */
interface ElectronicInvoiceProviderInterface
{
    /** Código identificador del proveedor (mock, dgii, psfe_x...). */
    public function code(): string;

    /** @return array<string, mixed> Payload del comprobante según el proveedor. */
    public function generatePayload(Invoice $invoice): array;

    /**
     * Valida requisitos antes de enviar (RNC, secuencia, certificado...).
     *
     * @return list<string> Lista de errores; vacía si es válido.
     */
    public function validateBeforeSend(Invoice $invoice): array;

    /**
     * Envía el comprobante y devuelve el resultado del intento.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, track_id?: string|null, security_code?: string|null, qr_data?: string|null, error?: string|null, response?: array<string, mixed>}
     */
    public function send(Invoice $invoice, array $payload): array;

    /**
     * Consulta el estado actual ante el proveedor.
     *
     * @return array{status: string, error?: string|null, response?: array<string, mixed>}
     */
    public function checkStatus(ElectronicInvoice $electronicInvoice): array;

    /**
     * Solicita la anulación del comprobante electrónico.
     *
     * @return array{status: string, error?: string|null, response?: array<string, mixed>}
     */
    public function cancel(ElectronicInvoice $electronicInvoice): array;
}
