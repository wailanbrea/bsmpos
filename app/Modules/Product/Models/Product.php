<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Setting\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Product extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'unit_id',
        'tax_id',
        'name',
        'sku',
        'barcode',
        'brand',
        'price',
        'cost',
        'image_path',
        'track_inventory',
        'warranty_months',
        'warranty_terms',
        'is_active',
        'available_pos',
        'available_delivery',
        'available_digital_menu',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'track_inventory' => 'boolean',
            'warranty_months' => 'integer',
            'is_active' => 'boolean',
            'available_pos' => 'boolean',
            'available_delivery' => 'boolean',
            'available_digital_menu' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Tax, $this> */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /** @return HasOne<ProductInventorySetting, $this> */
    public function inventorySetting(): HasOne
    {
        return $this->hasOne(ProductInventorySetting::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<ProductModifier, $this> */
    public function modifiers(): HasMany
    {
        return $this->hasMany(ProductModifier::class);
    }

    /** @return HasMany<ProductCombo, $this> */
    public function combos(): HasMany
    {
        return $this->hasMany(ProductCombo::class, 'parent_product_id');
    }
}
