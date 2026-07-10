<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Providers;

use App\Modules\ElectronicInvoice\Contracts\ElectronicInvoiceProviderInterface;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\Invoice\Models\Invoice;

/**
 * Proveedor nulo: la empresa no emite electrónicamente. Todo comprobante se
 * marca not_required y la facturación tradicional sigue intacta.
 */
final class NullElectronicInvoiceProvider implements ElectronicInvoiceProviderInterface
{
    public function code(): string
    {
        return 'null';
    }

    public function generatePayload(Invoice $invoice): array
    {
        return [];
    }

    public function validateBeforeSend(Invoice $invoice): array
    {
        return [];
    }

    public function send(Invoice $invoice, array $payload): array
    {
        return ['status' => 'not_required'];
    }

    public function checkStatus(ElectronicInvoice $electronicInvoice): array
    {
        return ['status' => $electronicInvoice->status];
    }

    public function cancel(ElectronicInvoice $electronicInvoice): array
    {
        return ['status' => 'canceled'];
    }
}
