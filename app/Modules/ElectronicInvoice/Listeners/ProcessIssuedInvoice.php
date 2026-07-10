<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Listeners;

use App\Modules\ElectronicInvoice\Services\ElectronicInvoiceService;
use App\Modules\Invoice\Events\InvoiceIssued;

/**
 * Puente entre facturación y e-CF: el módulo Invoice solo emite el evento;
 * este listener (del módulo ElectronicInvoice) decide si procesa.
 */
final class ProcessIssuedInvoice
{
    public function __construct(private readonly ElectronicInvoiceService $service) {}

    public function handle(InvoiceIssued $event): void
    {
        $this->service->processInvoice($event->invoice);
    }
}
