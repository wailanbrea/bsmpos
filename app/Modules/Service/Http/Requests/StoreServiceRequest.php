<?php

declare(strict_types=1);

namespace App\Modules\Service\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreServiceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'available_pos' => ['boolean'],
            'available_appointments' => ['boolean'],
            'requires_employee' => ['boolean'],
        ];
    }
}
