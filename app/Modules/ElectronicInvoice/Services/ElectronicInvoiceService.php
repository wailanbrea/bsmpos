<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Services;

use App\Modules\ElectronicInvoice\Contracts\ElectronicInvoiceProviderInterface;
use App\Modules\ElectronicInvoice\Jobs\SendElectronicInvoiceJob;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoiceSetting;
use App\Modules\ElectronicInvoice\Providers\MockElectronicInvoiceProvider;
use App\Modules\ElectronicInvoice\Providers\NullElectronicInvoiceProvider;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Illuminate\Support\Facades\DB;

final class ElectronicInvoiceService
{
    public function __construct(private readonly ModuleManagerService $modules) {}

    /**
     * Procesa electrónicamente una factura recién emitida. Regla del master
     * prompt §19: un fallo electrónico JAMÁS rompe la factura interna — todo
     * error queda registrado y reintentables desde la pantalla e-CF.
     */
    public function processInvoice(Invoice $invoice): ?ElectronicInvoice
    {
        if (! $this->modules->isEnabled($invoice->company_id, 'electronic_invoice')) {
            return null;
        }

        $settings = ElectronicInvoiceSetting::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->first();

        if ($settings === null || ! $settings->is_active || $settings->provider_code === 'null') {
            return null;
        }

        $record = ElectronicInvoice::withoutGlobalScopes()->firstOrCreate(
            ['invoice_id' => $invoice->getKey()],
            [
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'provider_code' => $settings->provider_code,
                'environment' => $settings->environment,
                'status' => 'queued',
            ],
        );

        // Encolar transmisión
        SendElectronicInvoiceJob::dispatch((int) $record->getKey());
        $this->logAction($record, 'queued', null, null, null);

        return $record;
    }

    /** Reintenta la transmisión de un comprobante rechazado o con error. */
    public function retry(ElectronicInvoice $record): ElectronicInvoice
    {
        $record->update(['status' => 'queued']);
        SendElectronicInvoiceJob::dispatch((int) $record->getKey());
        $this->logAction($record, 'retry', null, null, null);

        return $record;
    }

    /** Transmisión asíncrona invocada por el Job (propaga ConnectionException para backoff). */
    public function transmitAsync(ElectronicInvoice $record): ElectronicInvoice
    {
        $invoice = $record->invoice()->withoutGlobalScopes()->firstOrFail();
        $provider = $this->resolveProvider($record->provider_code);

        $errors = $provider->validateBeforeSend($invoice);
        if ($errors !== []) {
            $record->update(['status' => 'rejected', 'last_error' => implode(' ', $errors)]);
            $this->logAction($record, 'send', null, ['errors' => $errors], 422);

            return $record;
        }

        $payload = $provider->generatePayload($invoice);

        // El provider puede lanzar ConnectionException, que se propagará al Job para aplicar backoff
        $result = $provider->send($invoice, $payload);

        DB::transaction(function () use ($record, $payload, $result): void {
            $record->update([
                'status' => $result['status'],
                'track_id' => $result['track_id'] ?? $record->track_id,
                'security_code' => $result['security_code'] ?? $record->security_code,
                'qr_data' => $result['qr_data'] ?? $record->qr_data,
                'accepted_at' => $result['status'] === 'accepted' ? now() : $record->accepted_at,
                'rejected_at' => $result['status'] === 'rejected' ? now() : null,
                'last_error' => $result['error'] ?? null,
            ]);

            $this->logAction($record, 'send', $payload, $result['response'] ?? null, $result['status'] === 'accepted' ? 200 : 422);
        });

        return $record->refresh();
    }

    public function resolveProvider(string $code): ElectronicInvoiceProviderInterface
    {
        return match ($code) {
            'mock' => new MockElectronicInvoiceProvider,
            default => new NullElectronicInvoiceProvider,
        };
    }

    /**
     * @param  array<string, mixed>|null  $request
     * @param  array<string, mixed>|null  $response
     */
    public function logAction(ElectronicInvoice $record, string $action, ?array $request, ?array $response, ?int $statusCode, ?string $error = null): void
    {
        $record->logs()->create([
            'action' => $action,
            'request_payload' => $request,
            'response_payload' => $response,
            'status_code' => $statusCode,
            'error_message' => $error,
            'created_at' => now(),
        ]);
    }
}
