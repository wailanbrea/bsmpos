<?php

declare(strict_types=1);

namespace App\Modules\Setting\Services;

use App\Modules\Setting\DTOs\ReservedNcf;
use App\Modules\Setting\Exceptions\NcfSequenceException;
use App\Modules\Setting\Models\DocumentType;
use App\Modules\Setting\Models\NcfSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class NcfSequenceService
{
    /**
     * Reserva el siguiente NCF/e-NCF para un tipo de comprobante, de forma
     * atómica y segura ante concurrencia de terminales (lockForUpdate).
     * Debe invocarse dentro de la transacción de la venta/factura.
     */
    public function reserve(int|string $companyId, string $documentTypeCode, int|string|null $branchId = null): ReservedNcf
    {
        return DB::transaction(function () use ($companyId, $documentTypeCode, $branchId): ReservedNcf {
            $query = NcfSequence::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('document_type_code', $documentTypeCode)
                ->where('is_active', true)
                ->orderByRaw('branch_id IS NULL') // sucursal específica antes que la genérica
                ->orderBy('id')
                ->lockForUpdate();

            if ($branchId !== null) {
                $query->where(function ($builder) use ($branchId): void {
                    $builder->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            } else {
                $query->whereNull('branch_id');
            }

            $sequence = $query->get()->first(fn (NcfSequence $candidate): bool => ! $candidate->isExhausted());

            if ($sequence === null) {
                // Distinguir "no hay secuencia" de "todas agotadas" para un mensaje claro.
                $exists = NcfSequence::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('document_type_code', $documentTypeCode)
                    ->where('is_active', true)
                    ->exists();

                throw $exists
                    ? NcfSequenceException::exhausted($documentTypeCode)
                    : NcfSequenceException::unavailable($documentTypeCode);
            }

            if ($sequence->expires_at !== null && $sequence->expires_at->isPast()) {
                throw NcfSequenceException::expired($documentTypeCode);
            }

            $next = max($sequence->current_number + 1, $sequence->start_number);
            $sequence->current_number = $next;
            $sequence->save();

            return new ReservedNcf(
                ncf: $this->format($documentTypeCode, $next),
                documentTypeCode: $documentTypeCode,
                number: $next,
                sequenceId: (int) $sequence->getKey(),
                remaining: $sequence->remaining(),
                expiresAt: $sequence->expires_at?->toDateString(),
            );
        });
    }

    /**
     * Formatea el NCF: e-NCF con 10 dígitos, NCF tradicional con 8.
     * El código ya incluye serie y tipo (B01, E31...).
     */
    public function format(string $documentTypeCode, int $number): string
    {
        $documentType = DocumentType::query()->find($documentTypeCode);
        $width = $documentType?->is_electronic ? 10 : 8;

        return $documentTypeCode.str_pad((string) $number, $width, '0', STR_PAD_LEFT);
    }

    /** Secuencias que alcanzaron su umbral de alerta o están vencidas. */
    public function lowSequences(int|string $companyId): int
    {
        return NcfSequence::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (NcfSequence $sequence): bool => $sequence->remaining() <= $sequence->alert_threshold
                || ($sequence->expires_at !== null && $sequence->expires_at->isBefore(Carbon::now()->addDays(30))))
            ->count();
    }
}
