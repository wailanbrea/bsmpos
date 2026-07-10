<?php

declare(strict_types=1);

namespace App\Modules\Customer\Rules;

use App\Modules\Customer\Support\DominicanTaxId;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida el identificador fiscal según su tipo (rnc/cedula con dígito
 * verificador; otros tipos solo requieren un valor no vacío).
 */
final class ValidTaxId implements ValidationRule
{
    public function __construct(private readonly ?string $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! DominicanTaxId::isValid((string) $this->type, $value)) {
            $fail('El :attribute no es un identificador fiscal válido.');
        }
    }
}
