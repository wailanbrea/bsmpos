<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkOrderRequest extends FormRequest
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
            'vehicle_id' => ['required', 'string', Rule::exists('vehicles', 'public_id')->where('company_id', $companyId)],
            'customer_id' => ['nullable', 'string', Rule::exists('customers', 'public_id')->where('company_id', $companyId)],
            'employee_id' => ['nullable', 'string', Rule::exists('employees', 'public_id')->where('company_id', $companyId)],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'labor_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'services' => ['nullable', 'array'],
            'services.*.service_id' => ['required', 'string', Rule::exists('services', 'public_id')->where('company_id', $companyId)],
            'parts' => ['nullable', 'array'],
            'parts.*.product_id' => ['nullable', 'string', Rule::exists('products', 'public_id')->where('company_id', $companyId)],
            'parts.*.name' => ['required', 'string', 'max:150'],
            'parts.*.quantity' => ['required', 'numeric', 'min:0'],
            'parts.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
