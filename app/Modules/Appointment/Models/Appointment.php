<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Customer\Models\Customer;
use App\Modules\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $scheduled_at
 * @property Carbon|null $reminder_at
 * @property string $status
 */
final class Appointment extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid, SoftDeletes;

    /** Estados válidos del ciclo de vida de una cita (§22 master prompt). */
    public const STATUSES = ['pendiente', 'confirmada', 'en_proceso', 'completada', 'cancelada', 'no_asistio'];

    /** Transiciones permitidas por estado actual. */
    public const TRANSITIONS = [
        'pendiente' => ['confirmada', 'en_proceso', 'cancelada', 'no_asistio'],
        'confirmada' => ['en_proceso', 'cancelada', 'no_asistio'],
        'en_proceso' => ['completada', 'cancelada'],
        'completada' => [],
        'cancelada' => [],
        'no_asistio' => [],
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'employee_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'total',
        'reminder_at',
        'invoice_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_at' => 'datetime',
            'duration_minutes' => 'integer',
            'total' => 'decimal:2',
        ];
    }

    /** @return HasMany<AppointmentServiceLine, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(AppointmentServiceLine::class);
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
