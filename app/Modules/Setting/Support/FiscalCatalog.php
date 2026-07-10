<?php

declare(strict_types=1);

namespace App\Modules\Setting\Support;

/**
 * Datos fiscales base de República Dominicana. Catálogos globales (monedas,
 * tipos de comprobante) y valores por defecto que se provisionan por compañía
 * (impuestos, métodos de pago). No inventa tasas fuera de las vigentes.
 */
final class FiscalCatalog
{
    /** @return list<array{code: string, name: string, symbol: string, decimals: int}> */
    public static function currencies(): array
    {
        return [
            ['code' => 'DOP', 'name' => 'Peso dominicano', 'symbol' => 'RD$', 'decimals' => 2],
            ['code' => 'USD', 'name' => 'Dólar estadounidense', 'symbol' => 'US$', 'decimals' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimals' => 2],
        ];
    }

    /**
     * Tipos de comprobante DGII (NCF tradicional serie B y e-CF serie E).
     *
     * @return list<array{code: string, name: string, is_electronic: bool, is_fiscal: bool, requires_customer_tax_id: bool}>
     */
    public static function documentTypes(): array
    {
        $types = [
            ['B01', 'Crédito Fiscal', false, true, true],
            ['B02', 'Consumo', false, true, false],
            ['B03', 'Nota de Débito', false, true, true],
            ['B04', 'Nota de Crédito', false, true, true],
            ['B14', 'Regímenes Especiales', false, true, true],
            ['B15', 'Gubernamental', false, true, true],
            ['E31', 'Factura de Crédito Fiscal Electrónica', true, true, true],
            ['E32', 'Factura de Consumo Electrónica', true, true, false],
            ['E33', 'Nota de Débito Electrónica', true, true, true],
            ['E34', 'Nota de Crédito Electrónica', true, true, true],
            ['E44', 'Régimen Especial Electrónico', true, true, true],
            ['E45', 'Gubernamental Electrónico', true, true, true],
        ];

        return array_map(static function (array $type, int $index): array {
            return [
                'code' => $type[0],
                'name' => $type[1],
                'is_electronic' => $type[2],
                'is_fiscal' => $type[3],
                'requires_customer_tax_id' => $type[4],
                'sort_order' => ($index + 1) * 10,
            ];
        }, $types, array_keys($types));
    }

    /**
     * Impuestos y cargos por defecto de una compañía RD.
     *
     * @return list<array{name: string, code: string, rate: string, type: string, scope: string, is_retention: bool}>
     */
    public static function defaultTaxes(): array
    {
        return [
            ['name' => 'ITBIS 18%', 'code' => 'itbis_18', 'rate' => '18.0000', 'type' => 'percentage', 'scope' => 'both', 'is_retention' => false],
            ['name' => 'ITBIS 16%', 'code' => 'itbis_16', 'rate' => '16.0000', 'type' => 'percentage', 'scope' => 'both', 'is_retention' => false],
            ['name' => 'Exento', 'code' => 'exento', 'rate' => '0.0000', 'type' => 'percentage', 'scope' => 'both', 'is_retention' => false],
            ['name' => 'Propina Legal 10%', 'code' => 'propina_10', 'rate' => '10.0000', 'type' => 'percentage', 'scope' => 'service', 'is_retention' => false],
        ];
    }

    /**
     * Métodos de pago por defecto.
     *
     * @return list<array{name: string, code: string, requires_reference: bool}>
     */
    public static function defaultPaymentMethods(): array
    {
        return [
            ['name' => 'Efectivo', 'code' => 'cash', 'requires_reference' => false],
            ['name' => 'Tarjeta', 'code' => 'card', 'requires_reference' => true],
            ['name' => 'Transferencia', 'code' => 'transfer', 'requires_reference' => true],
            ['name' => 'Crédito', 'code' => 'credit', 'requires_reference' => false],
        ];
    }
}
