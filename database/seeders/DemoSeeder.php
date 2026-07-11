<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\ModuleManager\Actions\CompleteOnboardingAction;
use App\Modules\Product\Models\Product;
use App\Modules\Setting\Models\ExchangeRate;
use App\Modules\Setting\Models\NcfSequence;
use App\Modules\Setting\Models\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos demo deterministas para pruebas E2E (Playwright) y para levantar un
 * entorno de demostración con un login conocido. NO usar en producción.
 *
 * Requiere que ModuleSystemSeeder + ConfigurationSeeder ya se hayan ejecutado
 * (los ejecuta DatabaseSeeder con `migrate:fresh --seed`).
 *
 * Credenciales demo:
 *   correo:      demo@omnipos.test
 *   contraseña:  Password123!
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@omnipos.test';

    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        if (User::query()->where('email', self::EMAIL)->exists()) {
            return;
        }

        $owner = User::query()->create([
            'name' => 'Dueño Demo',
            'email' => self::EMAIL,
            'email_verified_at' => now(),
            'password' => Hash::make(self::PASSWORD),
            'is_active' => true,
        ]);

        $company = app(CreateCompanyAction::class)->execute($owner, [
            'name' => 'OmniPOS Demo',
            'legal_name' => 'OmniPOS Demo SRL',
            'branch_name' => 'Principal',
            'branch_code' => 'PRINCIPAL',
        ]);

        $branch = $company->branches()->firstOrFail();
        $context = app(CurrentCompany::class);
        $context->setCompany($company);
        $context->setBranch($branch);

        // Onboarding completo con giro "minimarket": deja business_type fijado
        // (para que el guard de router no redirija a onboarding), enciende POS,
        // productos e inventario, y aplica los presets operativos del giro.
        app(CompleteOnboardingAction::class)->execute(
            company: $company,
            businessTypeCode: 'minimarket',
            extraModules: ['report'],
            actor: $owner,
            taxId: '131793916',
            currencyCode: 'DOP',
            defaultTaxRate: 18.0,
            cashRegisterName: 'Caja Demo',
        );

        // Inventario y secuencia fiscal deterministas para el recorrido E2E
        // de venta real: el navegador abre la caja, vende y emite B02.
        $tax = Tax::query()
            ->where('company_id', $company->getKey())
            ->where('code', 'ITBIS18')
            ->firstOrFail();

        $product = Product::query()->create([
            'company_id' => $company->getKey(),
            'tax_id' => $tax->getKey(),
            'name' => 'Café Santo Domingo 8 oz',
            'sku' => 'E2E-CAFE-001',
            'price' => 100.00,
            'cost' => 55.00,
            'track_inventory' => true,
            'is_active' => true,
            'available_pos' => true,
        ]);

        $warehouse = Warehouse::query()
            ->where('company_id', $company->getKey())
            ->where('branch_id', $branch->getKey())
            ->sole();

        app(InventoryService::class)->addStock($warehouse, $product, 25.0, 55.0, user: $owner);

        $mixedPaymentProduct = Product::query()->create([
            'company_id' => $company->getKey(),
            'tax_id' => $tax->getKey(),
            'name' => 'Chocolate Barra 100 g',
            'sku' => 'E2E-CHOCO-001',
            'price' => 100.00,
            'cost' => 50.00,
            'track_inventory' => true,
            'is_active' => true,
            'available_pos' => true,
        ]);

        app(InventoryService::class)->addStock($warehouse, $mixedPaymentProduct, 25.0, 50.0, user: $owner);

        // Producto inventariable adrede SIN existencias: el recorrido E2E valida
        // que el POS bloquea la venta cuando no hay stock (regla dura §addendum).
        Product::query()->create([
            'company_id' => $company->getKey(),
            'tax_id' => $tax->getKey(),
            'name' => 'Botellón de Agua 20 L',
            'sku' => 'E2E-AGUA-001',
            'price' => 100.00,
            'cost' => 40.00,
            'track_inventory' => true,
            'is_active' => true,
            'available_pos' => true,
        ]);

        ExchangeRate::query()->create([
            'company_id' => $company->getKey(),
            'currency_code' => 'USD',
            'rate' => 60.00,
            'effective_date' => today(),
            'created_by_user_id' => $owner->getKey(),
        ]);

        NcfSequence::query()->create([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
            'document_type_code' => 'B02',
            'series' => 'B',
            'start_number' => 1,
            'end_number' => 100,
            'current_number' => 0,
            'expires_at' => now()->addYear(),
            'alert_threshold' => 10,
            'is_active' => true,
        ]);
    }
}
