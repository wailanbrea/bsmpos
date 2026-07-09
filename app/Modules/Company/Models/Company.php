<?php

declare(strict_types=1);

namespace App\Modules\Company\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\HasPublicUlid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Company extends Model
{
    use Auditable, HasPublicUlid, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id_type',
        'tax_id',
        'phone',
        'whatsapp',
        'email',
        'address',
        'timezone',
        'currency_code',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'suspended_at' => 'immutable_datetime',
            'trial_ends_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<Branch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_owner', 'default_branch_id'])
            ->withTimestamps();
    }
}
