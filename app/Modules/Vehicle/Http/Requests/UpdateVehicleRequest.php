<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'brand' => ['sometimes', 'required', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'plate' => ['nullable', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:40'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
