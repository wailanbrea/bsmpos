<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductModifier extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'product_id',
        'name',
        'required',
        'multiselect',
        'min_options',
        'max_options',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'multiselect' => 'boolean',
            'min_options' => 'integer',
            'max_options' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<ProductModifierOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ProductModifierOption::class, 'product_modifier_id');
    }
}
