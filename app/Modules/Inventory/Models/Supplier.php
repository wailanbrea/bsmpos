<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;

final class Supplier extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'name',
        'tax_id',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
