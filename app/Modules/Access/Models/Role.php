<?php

declare(strict_types=1);

namespace App\Modules\Access\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\HasPublicUlid;
use App\Core\Models\CompanyModel;
use App\Models\User;
use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Role extends CompanyModel
{
    use Auditable, HasPublicUlid, SoftDeletes;

    protected $fillable = ['company_id', 'code', 'name', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('company_id')
            ->withTimestamps();
    }
}
