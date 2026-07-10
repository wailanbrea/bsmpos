<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Jobs;

use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\ElectronicInvoice\Services\ElectronicInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendElectronicInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly int $electronicInvoiceId
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        // Incremento exponencial de reintentos: 5s, 30s, 60s, 300s, 900s
        return [5, 30, 60, 300, 900];
    }

    public function handle(ElectronicInvoiceService $service): void
    {
        $record = ElectronicInvoice::withoutGlobalScopes()->find($this->electronicInvoiceId);

        if ($record === null) {
            return;
        }

        // Si ya fue aceptado o rechazado permanentemente, no hacer nada
        if (in_array($record->status, ['accepted', 'rejected'], true)) {
            return;
        }

        // Incrementar intentos en base de datos si la columna existe (o localmente)
        $record->increment('attempts');

        try {
            $service->transmitAsync($record);
        } catch (ConnectionException $exception) {
            // Error de conexión temporal: marcar en contingencia y relanzar para reintento de cola con backoff
            $record->update([
                'status' => 'contingency',
                'last_error' => $exception->getMessage(),
            ]);

            $service->logAction($record, 'send_attempt_failed', null, null, 503, $exception->getMessage());

            Log::warning("Fallo de comunicación en e-CF [ID: {$record->id}]. Entrando en modo contingencia: ".$exception->getMessage());

            // Relanzar la excepción para que el motor de colas aplique el backoff
            throw $exception;
        } catch (Throwable $exception) {
            // Error fatal/estructurado: detener reintentos
            $record->update([
                'status' => 'rejected',
                'last_error' => $exception->getMessage(),
            ]);

            $service->logAction($record, 'send_rejected', null, null, 422, $exception->getMessage());

            Log::error("Comprobante e-CF [ID: {$record->id}] rechazado permanentemente: ".$exception->getMessage());
        }
    }
}
