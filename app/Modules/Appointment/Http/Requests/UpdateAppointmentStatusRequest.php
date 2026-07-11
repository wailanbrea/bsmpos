<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use App\Modules\Appointment\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Appointment::STATUSES)],
        ];
    }
}
