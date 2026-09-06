<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Appointment\Actions\CreateAppointmentAction;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Employee\Models\Employee;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\ModuleManager\Actions\CompleteOnboardingAction;
use App\Modules\Product\Models\Product;
use App\Modules\Restaurant\Models\RestaurantArea;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\NcfSequence;
use App\Modules\Setting\Models\Tax;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\WorkOrder\Actions\CreateWorkOrderAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demos por tipo de negocio para explorar cada vertical con un login propio.
 * Idempotente: cada demo se salta si su usuario ya existe. NO usar en producción.
 *
 * Credenciales (contraseña común: Password123!):
 *   Restaurante:  demo.restaurante@omnipos.test  → Restaurante El Fogón
 *   Barbería:     demo.barberia@omnipos.test     → Barbería La Navaja
 *   Taller:       demo.taller@omnipos.test       → Taller AutoMax
 *   Cafetería:    demo.cafeteria@omnipos.test    → Cafetería Aroma
 *   Supermercado: demo.super@omnipos.test        → Supermercado La Económica
 *   Ferretería:   demo.ferreteria@omnipos.test   → Ferretería El Tornillo
 *   Distribuidora:demo.distribuidora@omnipos.test→ Distribuidora del Cibao
 *   Serv. Prof.:  demo.servicios@omnipos.test    → Consultores Pro
 *   Minimarket:   demo@omnipos.test              → OmniPOS Demo (DemoSeeder)
 */
class DemoVerticalsSeeder extends Seeder
{
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $this->restaurant();
        $this->barbershop();
        $this->workshop();
        $this->cafeteria();
        $this->supermarket();
        $this->hardwareStore();
        $this->distributor();
        $this->professionalServices();
    }

    /** @return array{0: User, 1: Company, 2: Branch}|null */
    private function bootstrapCompany(
        string $email,
        string $ownerName,
        string $companyName,
        string $legalName,
        string $businessType,
        string $rnc,
        string $cashRegisterName,
    ): ?array {
        if (User::query()->where('email', $email)->exists()) {
            return null;
        }

        $owner = User::query()->create([
            'name' => $ownerName,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(self::PASSWORD),
            'is_active' => true,
        ]);

        $company = app(CreateCompanyAction::class)->execute($owner, [
            'name' => $companyName,
            'legal_name' => $legalName,
            'branch_name' => 'Principal',
            'branch_code' => 'PRINCIPAL',
        ]);

        $branch = $company->branches()->firstOrFail();
        $context = app(CurrentCompany::class);
        $context->setCompany($company);
        $context->setBranch($branch);

        app(CompleteOnboardingAction::class)->execute(
            company: $company,
            businessTypeCode: $businessType,
            extraModules: [],
            actor: $owner,
            taxId: $rnc,
            currencyCode: 'DOP',
            defaultTaxRate: 18.0,
            cashRegisterName: $cashRegisterName,
        );

        NcfSequence::query()->create([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
            'document_type_code' => 'B02',
            'series' => 'B',
            'start_number' => 1,
            'end_number' => 500,
            'current_number' => 0,
            'expires_at' => now()->addYear(),
            'alert_threshold' => 20,
            'is_active' => true,
        ]);

        return [$owner, $company, $branch];
    }

    private function itbis(Company $company): Tax
    {
        return Tax::query()
            ->where('company_id', $company->getKey())
            ->where('code', 'ITBIS18')
            ->firstOrFail();
    }

    /** Restaurante: menú POS sin inventario + áreas y mesas para el mapa de salón. */
    private function restaurant(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.restaurante@omnipos.test',
            ownerName: 'Dueña Restaurante',
            companyName: 'Restaurante El Fogón',
            legalName: 'El Fogón Criollo SRL',
            businessType: 'restaurant',
            rnc: '131000012',
            cashRegisterName: 'Caja Salón',
        );

        if ($bootstrap === null) {
            return;
        }

        [, $company] = $bootstrap;
        $tax = $this->itbis($company);

        foreach ([
            // Entradas & Picaderas
            ['Tostones Rellenos de Mariscos', 'REST-ENT-001', 450.00, 190.00],
            ['Chicharrón de Pollo Criollo', 'REST-ENT-002', 380.00, 150.00],
            ['Croquetas de Jamón Serrano (6 uds)', 'REST-ENT-003', 320.00, 120.00],
            ['Empanaditas Criollas Variadas (6 uds)', 'REST-ENT-004', 290.00, 110.00],
            ['Yuca Frita con Mojo de Ajo', 'REST-ENT-005', 220.00, 70.00],
            // Platos Criollos & Carnes
            ['Mofongo Especial de Chicharrón', 'REST-MOF-001', 550.00, 220.00],
            ['Chivo Liniero Guisado con Arroz y Habichuelas', 'REST-CHV-001', 620.00, 260.00],
            ['Pollo Guisado a la Leña con Moro de Guandules', 'REST-POLLO-001', 380.00, 140.00],
            ['Churrasco a la Parrilla 10 oz con Papas Salteadas', 'REST-CRN-001', 890.00, 420.00],
            ['Costillas BBQ al Fogón', 'REST-CRN-002', 680.00, 290.00],
            ['Hamburguesa Clásica El Fogón', 'REST-BURGER-001', 390.00, 160.00],
            // Pescados & Mariscos
            ['Chillo Boca Chica Frito con Tostones', 'REST-MAR-001', 750.00, 340.00],
            ['Salmón a la Plancha en Salsa de Maracuyá', 'REST-MAR-002', 820.00, 390.00],
            ['Camarones al Ajillo con Tostones', 'REST-MAR-003', 690.00, 310.00],
            // Pizzas & Pastas
            ['Pizza Margarita Tradicional', 'REST-PIZZA-001', 480.00, 190.00],
            ['Pizza Cuatro Quesos', 'REST-PIZZA-002', 550.00, 220.00],
            ['Fettuccine Alfredo con Pollo', 'REST-PAS-001', 460.00, 180.00],
            // Bebidas & Cocteles
            ['Cerveza Presidente Regular', 'REST-BEB-001', 220.00, 125.00],
            ['Cerveza Presidente Light', 'REST-BEB-002', 220.00, 125.00],
            ['Jugo Natural de Chinola 16 oz', 'REST-BEB-003', 150.00, 50.00],
            ['Jugo Natural de Fresa 16 oz', 'REST-BEB-004', 160.00, 55.00],
            ['Limonada con Menta Fresca', 'REST-BEB-005', 140.00, 40.00],
            ['Refresco Coca-Cola 20 oz', 'REST-BEB-006', 85.00, 45.00],
            ['Agua Mineral con Gas 500 ml', 'REST-BEB-007', 95.00, 40.00],
            ['Mojito Clásico Dominicano', 'REST-BEB-008', 350.00, 130.00],
            // Postres
            ['Tres Leches Casero', 'REST-POS-001', 220.00, 80.00],
            ['Flan de Caramelo Tradicional', 'REST-POS-002', 180.00, 60.00],
            ['Dulce de Leche Cortada', 'REST-POS-003', 160.00, 50.00],
        ] as [$name, $sku, $price, $cost]) {
            Product::query()->firstOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'sku' => $sku,
                ],
                [
                    'tax_id' => $tax->getKey(),
                    'name' => $name,
                    'price' => $price,
                    'cost' => $cost,
                    'track_inventory' => false,
                    'is_active' => true,
                    'available_pos' => true,
                ],
            );
        }

        foreach (['Salón' => 4, 'Terraza' => 2] as $areaName => $tableCount) {
            $area = RestaurantArea::query()->create([
                'company_id' => $company->getKey(),
                'name' => $areaName,
            ]);

            for ($i = 1; $i <= $tableCount; $i++) {
                $area->tables()->create([
                    'company_id' => $company->getKey(),
                    'table_number' => substr($areaName, 0, 1).$i,
                    'seating_capacity' => 4,
                    'status' => 'available',
                ]);
            }
        }
    }

    /** Barbería: servicios + barberos con comisión + citas de ejemplo en la agenda. */
    private function barbershop(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.barberia@omnipos.test',
            ownerName: 'Dueño Barbería',
            companyName: 'Barbería La Navaja',
            legalName: 'La Navaja Dorada SRL',
            businessType: 'barbershop',
            rnc: '131000029',
            cashRegisterName: 'Caja Barbería',
        );

        if ($bootstrap === null) {
            return;
        }

        [, $company, $branch] = $bootstrap;
        $tax = $this->itbis($company);

        $services = [];
        foreach ([
            ['Corte de cabello', 400.00, 30],
            ['Arreglo de barba', 250.00, 20],
            ['Tinte completo', 1200.00, 60],
        ] as [$name, $price, $minutes]) {
            $services[$name] = Service::query()->create([
                'company_id' => $company->getKey(),
                'tax_id' => $tax->getKey(),
                'name' => $name,
                'price' => $price,
                'duration_minutes' => $minutes,
                'available_pos' => true,
                'available_appointments' => true,
                'requires_employee' => true,
                'is_active' => true,
            ]);
        }

        $carlos = Employee::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Carlos Barbero',
            'position' => 'Barbero',
            'commission_rate' => 50,
            'is_active' => true,
        ]);

        $luis = Employee::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Luis Estilista',
            'position' => 'Estilista',
            'commission_rate' => 45,
            'is_active' => true,
        ]);

        $cliente = Customer::query()->create([
            'company_id' => $company->getKey(),
            'kind' => 'persona',
            'name' => 'Juan Pérez',
            'phone' => '809-555-0142',
            'is_active' => true,
        ]);

        $action = app(CreateAppointmentAction::class);

        // Cita de hoy confirmada (Corte + Barba con Carlos) y de mañana pendiente.
        $hoy = $action->execute($company, (int) $branch->getKey(), [
            'customer_id' => $cliente->public_id,
            'employee_id' => $carlos->public_id,
            'scheduled_at' => now()->setTime(10, 0)->toDateTimeString(),
            'services' => [
                ['service_id' => $services['Corte de cabello']->public_id],
                ['service_id' => $services['Arreglo de barba']->public_id],
            ],
            'notes' => 'Cliente frecuente, prefiere máquina #2.',
        ]);
        $hoy->update(['status' => 'confirmada']);

        $action->execute($company, (int) $branch->getKey(), [
            'customer_id' => $cliente->public_id,
            'employee_id' => $luis->public_id,
            'scheduled_at' => now()->addDay()->setTime(15, 0)->toDateTimeString(),
            'services' => [
                ['service_id' => $services['Tinte completo']->public_id],
            ],
        ]);
    }

    /** Taller: servicios, repuesto con stock, vehículo del cliente y orden en curso. */
    private function workshop(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.taller@omnipos.test',
            ownerName: 'Dueño Taller',
            companyName: 'Taller AutoMax',
            legalName: 'AutoMax Servicios SRL',
            businessType: 'mechanic',
            rnc: '131000037',
            cashRegisterName: 'Caja Taller',
        );

        if ($bootstrap === null) {
            return;
        }

        [$owner, $company, $branch] = $bootstrap;
        $tax = $this->itbis($company);

        $services = [];
        foreach ([
            ['Cambio de aceite', 800.00, 45],
            ['Alineación y balanceo', 1500.00, 60],
        ] as [$name, $price, $minutes]) {
            $services[$name] = Service::query()->create([
                'company_id' => $company->getKey(),
                'tax_id' => $tax->getKey(),
                'name' => $name,
                'price' => $price,
                'duration_minutes' => $minutes,
                'available_pos' => false,
                'available_appointments' => false,
                'requires_employee' => true,
                'is_active' => true,
            ]);
        }

        $filtro = Product::query()->create([
            'company_id' => $company->getKey(),
            'tax_id' => $tax->getKey(),
            'name' => 'Filtro de aceite',
            'sku' => 'TALLER-FILTRO-001',
            'price' => 350.00,
            'cost' => 200.00,
            'track_inventory' => true,
            'is_active' => true,
            'available_pos' => true,
        ]);

        $warehouse = Warehouse::query()
            ->where('company_id', $company->getKey())
            ->where('branch_id', $branch->getKey())
            ->sole();
        app(InventoryService::class)->addStock($warehouse, $filtro, 10.0, 200.0, user: $owner);

        $mecanico = Employee::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Pedro Mecánico',
            'position' => 'Mecánico',
            'commission_rate' => 30,
            'is_active' => true,
        ]);

        $cliente = Customer::query()->create([
            'company_id' => $company->getKey(),
            'kind' => 'persona',
            'name' => 'María Gómez',
            'phone' => '829-555-0173',
            'is_active' => true,
        ]);

        $corolla = Vehicle::query()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $cliente->getKey(),
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2019,
            'plate' => 'A123456',
            'color' => 'Gris',
            'mileage' => 78500,
            'is_active' => true,
        ]);

        $orden = app(CreateWorkOrderAction::class)->execute($company, (int) $branch->getKey(), [
            'vehicle_id' => $corolla->public_id,
            'customer_id' => $cliente->public_id,
            'employee_id' => $mecanico->public_id,
            'diagnosis' => 'Ruido en motor al encender; revisar correa y cambio de aceite.',
            'labor_amount' => '500',
            'services' => [
                ['service_id' => $services['Cambio de aceite']->public_id],
            ],
            'parts' => [
                ['product_id' => $filtro->public_id, 'name' => 'Filtro de aceite', 'quantity' => 1, 'price' => 350],
            ],
        ]);
        $orden->update(['status' => 'diagnosticando']);
    }

    /**
     * Helper: siembra productos POS con stock opcional en el almacén principal.
     *
     * @param  list<array{0: string, 1: string, 2: float, 3: float, 4: bool, 5?: float, 6?: string|null, 7?: string|null}>  $rows
     */
    private function seedProducts(User $owner, Company $company, Branch $branch, Tax $tax, array $rows): void
    {
        $warehouse = Warehouse::query()
            ->where('company_id', $company->getKey())
            ->where('branch_id', $branch->getKey())
            ->sole();
        $inventory = app(InventoryService::class);

        foreach ($rows as $row) {
            [$name, $sku, $price, $cost, $track] = $row;
            $product = Product::query()->create([
                'company_id' => $company->getKey(),
                'tax_id' => $tax->getKey(),
                'name' => $name,
                'sku' => $sku,
                'price' => $price,
                'cost' => $cost,
                'track_inventory' => $track,
                'is_active' => true,
                'available_pos' => true,
            ]);

            if ($track) {
                $qty = $row[5] ?? 30.0;
                $batch = $row[6] ?? null;
                $expires = $row[7] ?? null;
                $inventory->addStock($warehouse, $product, $qty, (float) $cost, $batch, $expires, user: $owner);
            }
        }
    }

    /** Cafetería: barra rápida para llevar, pocos productos con stock. */
    private function cafeteria(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.cafeteria@omnipos.test',
            ownerName: 'Dueña Cafetería',
            companyName: 'Cafetería Aroma',
            legalName: 'Aroma Café SRL',
            businessType: 'cafeteria',
            rnc: '131000101',
            cashRegisterName: 'Caja Barra',
        );

        if ($bootstrap === null) {
            return;
        }

        [$owner, $company, $branch] = $bootstrap;
        $tax = $this->itbis($company);

        $this->seedProducts($owner, $company, $branch, $tax, [
            ['Café Americano 12 oz', 'CAFE-AMER-001', 90.00, 25.00, false],
            ['Cappuccino', 'CAFE-CAPP-001', 150.00, 45.00, false],
            ['Sándwich de jamón y queso', 'CAFE-SAND-001', 220.00, 90.00, true, 20.0],
            ['Croissant', 'CAFE-CROI-001', 120.00, 45.00, true, 25.0],
        ]);
    }

    /** Supermercado: inventario avanzado con lotes y vencimientos (FEFO). */
    private function supermarket(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.super@omnipos.test',
            ownerName: 'Gerente Supermercado',
            companyName: 'Supermercado La Económica',
            legalName: 'La Económica Comercial SRL',
            businessType: 'supermarket',
            rnc: '131000118',
            cashRegisterName: 'Caja 1',
        );

        if ($bootstrap === null) {
            return;
        }

        [$owner, $company, $branch] = $bootstrap;
        $tax = $this->itbis($company);

        $this->seedProducts($owner, $company, $branch, $tax, [
            ['Leche entera 1 L', 'SUPER-LECHE-001', 95.00, 60.00, true, 40.0, 'LOTE-LECHE-A', '2026-08-15'],
            ['Arroz selecto 5 lb', 'SUPER-ARROZ-001', 280.00, 190.00, true, 60.0, 'LOTE-ARROZ-A', null],
            ['Aceite vegetal 1 L', 'SUPER-ACEITE-001', 210.00, 150.00, true, 35.0, 'LOTE-ACEITE-A', '2027-01-31'],
            ['Pan de sándwich', 'SUPER-PAN-001', 130.00, 70.00, true, 25.0, 'LOTE-PAN-A', '2026-07-20'],
            ['Refresco 2 L', 'SUPER-REFRE-001', 140.00, 85.00, true, 50.0, null, null],
        ]);
    }

    /** Ferretería: repuestos y materiales con inventario y proveedores. */
    private function hardwareStore(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.ferreteria@omnipos.test',
            ownerName: 'Dueño Ferretería',
            companyName: 'Ferretería El Tornillo',
            legalName: 'El Tornillo Materiales SRL',
            businessType: 'hardware_store',
            rnc: '131000126',
            cashRegisterName: 'Caja Mostrador',
        );

        if ($bootstrap === null) {
            return;
        }

        [$owner, $company, $branch] = $bootstrap;
        $tax = $this->itbis($company);

        $this->seedProducts($owner, $company, $branch, $tax, [
            ['Saco de cemento 42.5 kg', 'FERR-CEM-001', 520.00, 420.00, true, 80.0],
            ['Galón de pintura blanca', 'FERR-PINT-001', 780.00, 560.00, true, 30.0],
            ['Martillo de uña 16 oz', 'FERR-MART-001', 450.00, 280.00, true, 15.0],
            ['Caja de tornillos 1"', 'FERR-TORN-001', 180.00, 95.00, true, 100.0],
            ['Tubo PVC 1/2" (3 m)', 'FERR-PVC-001', 140.00, 80.00, true, 60.0],
        ]);
    }

    /** Distribuidora: ventas al por mayor y cliente con crédito. */
    private function distributor(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.distribuidora@omnipos.test',
            ownerName: 'Gerente Distribuidora',
            companyName: 'Distribuidora del Cibao',
            legalName: 'Distribuidora del Cibao SRL',
            businessType: 'distributor',
            rnc: '131000134',
            cashRegisterName: 'Caja Despacho',
        );

        if ($bootstrap === null) {
            return;
        }

        [$owner, $company, $branch] = $bootstrap;
        $tax = $this->itbis($company);

        $this->seedProducts($owner, $company, $branch, $tax, [
            ['Caja de agua 24 uds', 'DIST-AGUA-001', 180.00, 120.00, true, 200.0],
            ['Fardo de papel higiénico', 'DIST-PAPEL-001', 650.00, 480.00, true, 90.0],
            ['Caja de galletas 12 uds', 'DIST-GALL-001', 420.00, 300.00, true, 120.0],
        ]);

        // Cliente colmado con crédito para mostrar ventas a crédito.
        Customer::query()->create([
            'company_id' => $company->getKey(),
            'kind' => 'empresa',
            'name' => 'Colmado La Esquina',
            'tax_id_type' => 'RNC',
            'tax_id' => '131000142',
            'phone' => '809-555-0188',
            'credit_limit' => 50000.00,
            'credit_days' => 30,
            'is_active' => true,
        ]);
    }

    /** Servicios profesionales: catálogo de servicios y cotizaciones. */
    private function professionalServices(): void
    {
        $bootstrap = $this->bootstrapCompany(
            email: 'demo.servicios@omnipos.test',
            ownerName: 'Socia Consultora',
            companyName: 'Consultores Pro',
            legalName: 'Consultores Pro SRL',
            businessType: 'professional_services',
            rnc: '131000152',
            cashRegisterName: 'Caja Oficina',
        );

        if ($bootstrap === null) {
            return;
        }

        [, $company] = $bootstrap;
        $tax = $this->itbis($company);

        foreach ([
            ['Consultoría por hora', 2500.00, 60],
            ['Auditoría de procesos', 15000.00, 480],
            ['Asesoría fiscal mensual', 8000.00, 0],
        ] as [$name, $price, $minutes]) {
            Service::query()->create([
                'company_id' => $company->getKey(),
                'tax_id' => $tax->getKey(),
                'name' => $name,
                'price' => $price,
                'duration_minutes' => $minutes,
                'available_pos' => false,
                'available_appointments' => false,
                'requires_employee' => false,
                'is_active' => true,
            ]);
        }

        Customer::query()->create([
            'company_id' => $company->getKey(),
            'kind' => 'empresa',
            'name' => 'Inversiones del Este SRL',
            'tax_id_type' => 'RNC',
            'tax_id' => '131000169',
            'phone' => '809-555-0155',
            'is_active' => true,
        ]);
    }
}
