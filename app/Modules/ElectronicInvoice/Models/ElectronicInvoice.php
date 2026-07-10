<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ElectronicInvoice extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'branch_id',
        'invoice_id',
        'provider_code',
        'environment',
        'status',
        'attempts',
        'track_id',
        'external_id',
        'qr_data',
        'security_code',
        'xml_path',
        'pdf_path',
        'accepted_at',
        'rejected_at',
        'canceled_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'accepted_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
            'canceled_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return HasMany<ElectronicInvoiceLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(ElectronicInvoiceLog::class);
    }
}
