<?php

declare(strict_types=1);

namespace App\Modules\Customer\Support;

/**
 * Validación de identificadores fiscales dominicanos:
 * - RNC: 9 dígitos con dígito verificador módulo 11 (pesos 7,9,8,6,5,4,3,2).
 * - Cédula: 11 dígitos con verificación tipo Luhn (pesos 1,2 alternos).
 */
final class DominicanTaxId
{
    public static function isValidRnc(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) !== 9) {
            return false;
        }

        $weights = [7, 9, 8, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $digits[$i] * $weights[$i];
        }

        $remainder = $sum % 11;
        $check = match ($remainder) {
            0 => 2,
            1 => 1,
            default => 11 - $remainder,
        };

        return $check === (int) $digits[8];
    }

    public static function isValidCedula(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) !== 11) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $weight = ($i % 2 === 0) ? 1 : 2;
            $product = (int) $digits[$i] * $weight;
            $sum += $product > 9 ? $product - 9 : $product;
        }

        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $digits[10];
    }

    public static function isValid(string $type, string $value): bool
    {
        return match ($type) {
            'rnc' => self::isValidRnc($value),
            'cedula' => self::isValidCedula($value),
            default => $value !== '',
        };
    }
}
