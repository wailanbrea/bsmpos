<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProvisionCompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'ulid', 'distinct'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['required', 'ulid', 'distinct'],
            'default_branch_id' => ['nullable', 'ulid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower((string) $this->input('email'))]);
        }
    }
}
