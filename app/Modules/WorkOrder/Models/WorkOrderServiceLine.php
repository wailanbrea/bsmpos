<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Línea de servicio (mano de obra tarifada) de una orden. Tabla `work_order_services`. */
final class WorkOrderServiceLine extends Model
{
    protected $table = 'work_order_services';

    protected $fillable = ['work_order_id', 'service_id', 'name', 'price', 'tax_id', 'tax_rate'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tax_rate' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<WorkOrder, $this> */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
