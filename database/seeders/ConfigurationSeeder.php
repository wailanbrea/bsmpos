<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Setting\Support\FiscalCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos base globales (no ligados a una compañía): monedas, tipos de
 * comprobante DGII y el catálogo de permisos. Los impuestos, unidades y
 * métodos de pago son por-compañía y se provisionan al crear cada empresa.
 */
final class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('currencies')->upsert(
            array_map(static fn (array $currency): array => [...$currency, 'created_at' => $now, 'updated_at' => $now], FiscalCatalog::currencies()),
            ['code'],
            ['name', 'symbol', 'decimals', 'updated_at'],
        );

        DB::table('document_types')->upsert(
            array_map(static fn (array $type): array => [...$type, 'created_at' => $now, 'updated_at' => $now], FiscalCatalog::documentTypes()),
            ['code'],
            ['name', 'is_electronic', 'is_fiscal', 'requires_customer_tax_id', 'sort_order', 'updated_at'],
        );

        // Catálogo global de permisos (tabla `permissions` con clave única `code`).
        // Ya se hacía perezosamente al crear una compañía; sembrarlo aquí deja el
        // catálogo disponible desde un `migrate:fresh --seed` limpio.
        DB::table('permissions')->upsert(
            array_map(static fn (array $permission): array => [...$permission, 'created_at' => $now, 'updated_at' => $now], PermissionCatalog::defaults()),
            ['code'],
            ['name', 'module_code', 'description', 'updated_at'],
        );
    }
}
