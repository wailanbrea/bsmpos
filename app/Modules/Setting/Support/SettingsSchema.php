<?php

declare(strict_types=1);

namespace App\Modules\Setting\Support;

/**
 * Esquema de configuración por grupos: define claves válidas, su tipo y valor
 * por defecto. Es la fuente de verdad; la API solo acepta claves declaradas.
 */
final class SettingsSchema
{
    /**
     * @return array<string, array<string, array{type: string, default: mixed, options?: list<string>}>>
     */
    public static function all(): array
    {
        return [
            'pos' => [
                'allow_sell_without_stock' => ['type' => 'bool', 'default' => false],
                'default_document_type' => ['type' => 'enum', 'default' => 'B02', 'options' => ['B02', 'B01', 'E32', 'E31']],
                'require_customer_for_credit_fiscal' => ['type' => 'bool', 'default' => true],
                'legal_tip_enabled' => ['type' => 'bool', 'default' => false],
                'legal_tip_rate' => ['type' => 'decimal', 'default' => '10.00'],
            ],
            'inventory' => [
                'default_outgoing_method' => ['type' => 'enum', 'default' => 'fefo', 'options' => ['manual', 'fifo', 'fefo', 'average']],
                'low_stock_alerts' => ['type' => 'bool', 'default' => true],
                'expired_sale_policy' => ['type' => 'enum', 'default' => 'block', 'options' => ['block', 'warn', 'authorize']],
                'near_expiration_days' => ['type' => 'int', 'default' => 30],
            ],
            'invoice' => [
                'price_includes_tax' => ['type' => 'bool', 'default' => false],
                'default_currency' => ['type' => 'enum', 'default' => 'DOP', 'options' => ['DOP', 'USD', 'EUR']],
                'ncf_alert_threshold' => ['type' => 'int', 'default' => 50],
            ],
            'printing' => [
                'ticket_width_mm' => ['type' => 'enum', 'default' => '80', 'options' => ['58', '80', '88']],
                'print_copies' => ['type' => 'int', 'default' => 1],
                'auto_print_on_sale' => ['type' => 'bool', 'default' => true],
            ],
            'security' => [
                'session_timeout_minutes' => ['type' => 'int', 'default' => 120],
                'require_2fa_admins' => ['type' => 'bool', 'default' => false],
                'cashier_pin_required' => ['type' => 'bool', 'default' => false],
            ],
            'backup' => [
                'enabled' => ['type' => 'bool', 'default' => true],
                'frequency' => ['type' => 'enum', 'default' => 'daily', 'options' => ['daily', 'weekly']],
                'retention_days' => ['type' => 'int', 'default' => 30],
            ],
        ];
    }

    /** @return list<string> */
    public static function groups(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, array{type: string, default: mixed, options?: list<string>}>|null */
    public static function group(string $group): ?array
    {
        return self::all()[$group] ?? null;
    }
}
