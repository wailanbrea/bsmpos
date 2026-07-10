<?php

declare(strict_types=1);

namespace App\Modules\Setting\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $expires_at
 * @property int $start_number
 * @property int $end_number
 * @property int $current_number
 * @property int $alert_threshold
 */
final class NcfSequence extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'document_type_code',
        'series',
        'start_number',
        'end_number',
        'current_number',
        'expires_at',
        'alert_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_number' => 'integer',
            'end_number' => 'integer',
            'current_number' => 'integer',
            'expires_at' => 'immutable_date',
            'alert_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<DocumentType, $this> */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_code', 'code');
    }

    /** Cantidad de comprobantes aún disponibles en la secuencia. */
    public function remaining(): int
    {
        $consumed = max($this->current_number, $this->start_number - 1);

        return (int) max(0, $this->end_number - $consumed);
    }

    public function isExhausted(): bool
    {
        return $this->remaining() <= 0;
    }
}
