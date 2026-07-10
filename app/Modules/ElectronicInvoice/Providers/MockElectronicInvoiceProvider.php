<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Providers;

use App\Modules\ElectronicInvoice\Contracts\ElectronicInvoiceProviderInterface;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

/**
 * Proveedor simulado para demo y pruebas: acepta todo comprobante con datos
 * completos y genera track/QR ficticios. No implementa normativa DGII; el
 * proveedor real se conecta luego implementando la misma interface.
 */
final class MockElectronicInvoiceProvider implements ElectronicInvoiceProviderInterface
{
    public function code(): string
    {
        return 'mock';
    }

    public function generatePayload(Invoice $invoice): array
    {
        return [
            'ncf' => $invoice->ncf,
            'document_type' => $invoice->document_type_code,
            'issued_at' => $invoice->created_at?->toIso8601String(),
            'customer' => [
                'name' => $invoice->customer?->name,
                'tax_id' => $invoice->customer?->tax_id,
            ],
            'totals' => [
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount_total,
                'tax' => $invoice->tax_total,
                'tip' => $invoice->tip_total,
                'total' => $invoice->total,
            ],
            'items' => $invoice->items->map(fn ($item): array => [
                'description' => $item->product?->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
            ])->all(),
        ];
    }

    public function validateBeforeSend(Invoice $invoice): array
    {
        $errors = [];

        if ($invoice->ncf === null || $invoice->ncf === '') {
            $errors[] = 'La factura no tiene NCF asignado.';
        }

        if ($invoice->status === 'canceled') {
            $errors[] = 'No se puede transmitir una factura anulada.';
        }

        return $errors;
    }

    public function send(Invoice $invoice, array $payload): array
    {
        if ($invoice->ncf === 'B0200000099') {
            throw new ConnectionException('Error de comunicación simulado con el WebService de la DGII.');
        }

        $trackId = 'MOCK-'.Str::upper(Str::random(12));
        $securityCode = Str::upper(Str::random(6));

        return [
            'status' => 'accepted',
            'track_id' => $trackId,
            'security_code' => $securityCode,
            'qr_data' => "https://ecf.dgii.gov.do/consulta?ncf={$invoice->ncf}&code={$securityCode}",
            'response' => ['message' => 'Comprobante aceptado (simulado).', 'track_id' => $trackId],
        ];
    }

    public function checkStatus(ElectronicInvoice $electronicInvoice): array
    {
        return [
            'status' => $electronicInvoice->status === 'pending' ? 'accepted' : $electronicInvoice->status,
            'response' => ['message' => 'Estado consultado (simulado).'],
        ];
    }

    public function cancel(ElectronicInvoice $electronicInvoice): array
    {
        return [
            'status' => 'canceled',
            'response' => ['message' => 'Comprobante anulado (simulado).'],
        ];
    }
}
