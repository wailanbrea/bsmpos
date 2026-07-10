<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use App\Modules\Company\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->company()->getKey();
        $branchId = Branch::query()
            ->where('company_id', $companyId)
            ->where('public_id', (string) $this->route('publicId'))
            ->value('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('branches', 'code')->where('company_id', $companyId)->ignore($branchId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper((string) $this->input('code'))]);
        }
    }
}
