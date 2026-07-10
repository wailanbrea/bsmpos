<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $balance
 * @property string $credit_limit
 */
final class Customer extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'kind',
        'name',
        'tax_id_type',
        'tax_id',
        'phone',
        'whatsapp',
        'email',
        'address',
        'is_generic',
        'credit_limit',
        'credit_days',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_generic' => 'boolean',
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
            'balance' => 'decimal:2',
        ];
    }

    /** @return HasMany<CustomerAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /** @return HasMany<CustomerContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    /** @return HasMany<CustomerCreditMovement, $this> */
    public function creditMovements(): HasMany
    {
        return $this->hasMany(CustomerCreditMovement::class);
    }

    public function availableCredit(): string
    {
        return bcsub((string) $this->credit_limit, (string) $this->balance, 2);
    }
}
