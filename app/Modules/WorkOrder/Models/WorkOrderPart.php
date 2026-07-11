<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Repuesto/pieza de una orden de taller. Tabla `work_order_parts`. */
final class WorkOrderPart extends Model
{
    protected $table = 'work_order_parts';

    protected $fillable = ['work_order_id', 'product_id', 'name', 'quantity', 'price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'price' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<WorkOrder, $this> */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
