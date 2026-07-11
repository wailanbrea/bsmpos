<?php

declare(strict_types=1);

namespace App\Modules\Employee\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Employee extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'position',
        'phone',
        'email',
        'commission_rate',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
