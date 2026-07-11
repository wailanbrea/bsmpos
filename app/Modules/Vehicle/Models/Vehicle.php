<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Vehicle extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'brand',
        'model',
        'year',
        'plate',
        'vin',
        'color',
        'mileage',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
