<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Company\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CashRegister extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'is_active',
        'printer_paper_width',
        'printer_name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<CashSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }
}
