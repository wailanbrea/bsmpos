<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanyUserAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'ulid', 'distinct'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['required', 'ulid', 'distinct'],
            'default_branch_id' => ['nullable', 'ulid'],
        ];
    }
}
