<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Models;

use App\Modules\Service\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de servicio de una cita. Mapea la tabla `appointment_services`;
 * se llama *Line* para no colisionar con el modelo de dominio Service.
 */
final class AppointmentServiceLine extends Model
{
    protected $table = 'appointment_services';

    protected $fillable = [
        'appointment_id',
        'service_id',
        'name',
        'price',
        'tax_id',
        'tax_rate',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tax_rate' => 'decimal:3',
            'duration_minutes' => 'integer',
        ];
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
