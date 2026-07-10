<?php

declare(strict_types=1);

namespace App\Modules\Restaurant\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RestaurantArea extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'name',
    ];

    /** @return HasMany<RestaurantTable, $this> */
    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }
}
