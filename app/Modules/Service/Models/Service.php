<?php

declare(strict_types=1);

namespace App\Modules\Service\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Service extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'tax_id',
        'name',
        'price',
        'duration_minutes',
        'available_pos',
        'available_appointments',
        'requires_employee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'available_pos' => 'boolean',
            'available_appointments' => 'boolean',
            'requires_employee' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
