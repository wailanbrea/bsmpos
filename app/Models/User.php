<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Concerns\Auditable;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Access\Models\Role;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, HasPublicUlid, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsToMany<Company, $this> */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['is_owner', 'default_branch_id'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Branch, $this> */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('company_id')
            ->withTimestamps();
    }

    public function belongsToCompany(int|string $companyId): bool
    {
        return $this->is_active !== false && $this->companies()->whereKey($companyId)->exists();
    }

    public function hasCompanyPermission(int|string $companyId, string $permission): bool
    {
        if (! $this->belongsToCompany($companyId)) {
            return false;
        }

        if ($this->companies()->whereKey($companyId)->wherePivot('is_owner', true)->exists()) {
            return true;
        }

        return $this->roles()
            ->withoutGlobalScopes()
            ->wherePivot('company_id', $companyId)
            ->whereHas('permissions', fn ($query) => $query->where('code', $permission))
            ->exists();
    }
}
