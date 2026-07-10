<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTaxRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', Rule::unique('taxes', 'code')->where('company_id', $companyId)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'scope' => ['required', Rule::in(['product', 'service', 'both'])],
            'is_inclusive' => ['boolean'],
            'is_retention' => ['boolean'],
        ];
    }
}
