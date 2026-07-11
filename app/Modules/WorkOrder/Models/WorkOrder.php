<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Customer\Models\Customer;
use App\Modules\Employee\Models\Employee;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $status
 */
final class WorkOrder extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    /** Ciclo de vida de una orden de taller (§23 master prompt). */
    public const STATUSES = [
        'recibida', 'diagnosticando', 'cotizada', 'aprobada', 'en_proceso', 'lista', 'entregada', 'cancelada',
    ];

    /** Transiciones permitidas por estado actual. */
    public const TRANSITIONS = [
        'recibida' => ['diagnosticando', 'cancelada'],
        'diagnosticando' => ['cotizada', 'cancelada'],
        'cotizada' => ['aprobada', 'cancelada'],
        'aprobada' => ['en_proceso', 'cancelada'],
        'en_proceso' => ['lista', 'cancelada'],
        'lista' => ['entregada'],
        'entregada' => [],
        'cancelada' => [],
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'vehicle_id',
        'employee_id',
        'diagnosis',
        'status',
        'labor_amount',
        'total',
        'invoice_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'labor_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return HasMany<WorkOrderServiceLine, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(WorkOrderServiceLine::class);
    }

    /** @return HasMany<WorkOrderPart, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
