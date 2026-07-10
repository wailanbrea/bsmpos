<?php

declare(strict_types=1);

namespace App\Modules\Restaurant\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\POS\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RestaurantTable extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'restaurant_area_id',
        'table_number',
        'seating_capacity',
        'status',
        'active_order_id',
    ];

    protected function casts(): array
    {
        return [
            'seating_capacity' => 'integer',
        ];
    }

    /** @return BelongsTo<RestaurantArea, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(RestaurantArea::class, 'restaurant_area_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function activeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'active_order_id');
    }
}
