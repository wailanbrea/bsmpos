<?php

declare(strict_types=1);

namespace App\Modules\Setting\Support;

/**
 * Preconfiguración operativa por tipo de negocio: al completar el onboarding,
 * cada giro carga ajustes adaptados (propina legal, método de salida de
 * inventario, comprobante por defecto, impresión...). Solo claves declaradas
 * en SettingsSchema; el dueño puede cambiarlas luego en Configuración.
 */
final class BusinessTypeSettingsPresets
{
    /**
     * @return array<string, array<string, mixed>> Mapa grupo => valores.
     */
    public static function for(string $businessTypeCode): array
    {
        $base = self::defaults();
        $overrides = self::overrides()[$businessTypeCode] ?? [];

        foreach ($overrides as $group => $values) {
            $base[$group] = array_merge($base[$group] ?? [], $values);
        }

        return $base;
    }

    /** @return array<string, array<string, mixed>> */
    private static function defaults(): array
    {
        return [
            'pos' => [
                'default_document_type' => 'B02',
                'legal_tip_enabled' => false,
                'allow_sell_without_stock' => false,
            ],
            'inventory' => [
                'default_outgoing_method' => 'fifo',
                'low_stock_alerts' => true,
                'expired_sale_policy' => 'block',
                'near_expiration_days' => 30,
            ],
            'printing' => [
                'ticket_width_mm' => '80',
                'auto_print_on_sale' => true,
            ],
        ];
    }

    /**
     * Ajustes que se apartan del default por giro.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function overrides(): array
    {
        $foodService = [
            // Restaurantes/bares aplican la propina legal del 10% (Ley 54-32) y
            // venden platos preparados sin stock unitario en el POS.
            'pos' => ['legal_tip_enabled' => true, 'allow_sell_without_stock' => true],
            'inventory' => ['default_outgoing_method' => 'fefo', 'near_expiration_days' => 7],
        ];

        $perishableRetail = [
            // Alimentos con vencimiento: FEFO y bloqueo estricto de vencidos.
            'inventory' => ['default_outgoing_method' => 'fefo', 'expired_sale_policy' => 'block', 'near_expiration_days' => 30],
        ];

        $serviceBusiness = [
            // Negocios de servicios: sin control de stock en el flujo de venta.
            'pos' => ['allow_sell_without_stock' => true],
            'inventory' => ['low_stock_alerts' => false],
        ];

        return [
            'restaurant' => $foodService,
            'cafeteria' => $foodService,
            'food_truck' => [
                'pos' => ['legal_tip_enabled' => false, 'allow_sell_without_stock' => true],
                'inventory' => ['default_outgoing_method' => 'fefo', 'near_expiration_days' => 7],
                'printing' => ['ticket_width_mm' => '58'],
            ],
            'minimarket' => $perishableRetail,
            'supermarket' => $perishableRetail,
            'warehouse_business' => $perishableRetail,
            'distributor' => [
                'inventory' => ['default_outgoing_method' => 'fefo', 'near_expiration_days' => 60],
            ],
            'general_store' => [
                'inventory' => ['default_outgoing_method' => 'fifo'],
            ],
            'hardware_store' => [
                'inventory' => ['default_outgoing_method' => 'fifo', 'expired_sale_policy' => 'warn'],
            ],
            'auto_parts' => [
                'inventory' => ['default_outgoing_method' => 'fifo', 'expired_sale_policy' => 'warn'],
            ],
            'barbershop' => $serviceBusiness,
            'professional_services' => array_merge_recursive($serviceBusiness, [
                'printing' => ['auto_print_on_sale' => false],
            ]),
            'mechanic' => [
                'pos' => ['allow_sell_without_stock' => true],
                'inventory' => ['default_outgoing_method' => 'fifo'],
            ],
            'custom' => [],
        ];
    }
}
