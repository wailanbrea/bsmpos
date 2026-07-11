<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->company()->getKey();

        return [
            'customer_id' => ['nullable', 'string', Rule::exists('customers', 'public_id')->where('company_id', $companyId)],
            'employee_id' => ['nullable', 'string', Rule::exists('employees', 'public_id')->where('company_id', $companyId)],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'reminder_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'string', Rule::exists('services', 'public_id')->where('company_id', $companyId)],
        ];
    }
}
