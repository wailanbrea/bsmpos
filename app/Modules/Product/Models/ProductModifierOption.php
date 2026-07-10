<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductModifierOption extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'product_modifier_id',
        'name',
        'price',
        'cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<ProductModifier, $this> */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(ProductModifier::class, 'product_modifier_id');
    }
}
