<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Support;

/**
 * Fuente de verdad del catálogo de módulos, sus dependencias, los tipos de
 * negocio y sus presets. El seeder proyecta estos datos a las tablas; el
 * código nunca deriva el catálogo de la base de datos.
 */
final class ModuleCatalog
{
    /**
     * @return list<array{code: string, name: string, description: string, category: string, is_core: bool, sort_order: int}>
     */
    public static function modules(): array
    {
        $modules = [
            // Núcleo (no desactivable)
            ['auth', 'Autenticación', 'Acceso de usuarios y sesiones.', 'core', true],
            ['company', 'Compañías', 'Empresas y sucursales.', 'core', true],
            ['user_access', 'Usuarios y permisos', 'Roles, permisos y accesos.', 'core', true],
            ['module_manager', 'Gestor de módulos', 'Activación de módulos por empresa.', 'core', true],
            ['setting', 'Configuración', 'Ajustes de la empresa.', 'core', true],
            ['customer', 'Clientes', 'Directorio de clientes.', 'core', true],
            ['payment', 'Pagos', 'Registro de pagos.', 'core', true],
            ['cash_register', 'Caja', 'Apertura, cierre y arqueo de caja.', 'core', true],
            ['invoice', 'Facturación', 'Facturas, cotizaciones y notas.', 'core', true],
            ['electronic_invoice', 'Facturación electrónica', 'Comprobantes fiscales electrónicos (e-CF).', 'core', true],
            ['report', 'Reportes', 'Reportes de ventas, caja e inventario.', 'core', true],
            ['audit', 'Auditoría', 'Bitácora de acciones sensibles.', 'core', true],
            // Opcionales
            ['pos', 'POS Touch', 'Punto de venta táctil.', 'sales', false],
            ['product', 'Productos', 'Catálogo de productos.', 'catalog', false],
            ['service', 'Servicios', 'Catálogo de servicios.', 'catalog', false],
            ['inventory', 'Inventario', 'Control de existencias.', 'inventory', false],
            ['advanced_inventory', 'Inventario avanzado', 'Lotes, vencimientos y FEFO/FIFO.', 'inventory', false],
            ['barcode', 'Código de barras', 'Lectura y generación de códigos.', 'catalog', false],
            ['supplier', 'Proveedores', 'Directorio de proveedores.', 'purchasing', false],
            ['purchase', 'Compras', 'Órdenes de compra y entradas.', 'purchasing', false],
            ['warehouse', 'Almacenes', 'Almacenes y ubicaciones.', 'inventory', false],
            ['serial_numbers', 'Números de serie', 'Trazabilidad por serie.', 'inventory', false],
            ['table_management', 'Mesas', 'Áreas y mesas de restaurante.', 'restaurant', false],
            ['kitchen', 'Cocina / KDS', 'Pantalla de cocina y comandas.', 'restaurant', false],
            ['restaurant', 'Restaurante y Cocina', 'Gestión de áreas, mesas y comandas KDS.', 'restaurant', false],
            ['delivery', 'Delivery', 'Pedidos a domicilio.', 'restaurant', false],
            ['digital_menu', 'Menú digital', 'Menú y ofertas en pantalla.', 'restaurant', false],
            ['recipe', 'Recetas', 'Insumos y consumo por venta.', 'restaurant', false],
            ['appointment', 'Citas', 'Agenda de citas.', 'services', false],
            ['vehicle', 'Vehículos', 'Vehículos de clientes.', 'workshop', false],
            ['work_order', 'Órdenes de trabajo', 'Órdenes de taller.', 'workshop', false],
            ['quotation', 'Cotizaciones', 'Cotizaciones convertibles a factura.', 'sales', false],
            ['accounts_receivable', 'Cuentas por cobrar', 'Crédito y abonos de clientes.', 'finance', false],
            ['accounts_payable', 'Cuentas por pagar', 'Deudas con proveedores.', 'finance', false],
            ['printer', 'Impresión', 'Tickets y formatos de impresión.', 'operations', false],
            ['loyalty', 'Fidelización', 'Puntos y recompensas.', 'sales', false],
            ['reservation', 'Reservas', 'Reservas de mesas.', 'restaurant', false],
            ['warranty', 'Garantías', 'Garantías de venta.', 'sales', false],
            ['expense', 'Gastos', 'Gastos operativos (606).', 'finance', false],
            ['employee', 'Empleados', 'Empleados y comisiones.', 'hr', false],
            ['notification', 'Notificaciones', 'Correo y WhatsApp.', 'operations', false],
        ];

        $sort = 0;

        return array_map(static function (array $module) use (&$sort): array {
            return [
                'code' => $module[0],
                'name' => $module[1],
                'description' => $module[2],
                'category' => $module[3],
                'is_core' => $module[4],
                'sort_order' => $sort += 10,
            ];
        }, $modules);
    }

    /**
     * Dependencias módulo => módulos requeridos.
     *
     * @return array<string, list<string>>
     */
    public static function dependencies(): array
    {
        return [
            'pos' => ['invoice'],
            'inventory' => ['product'],
            'advanced_inventory' => ['inventory'],
            'barcode' => ['product'],
            'purchase' => ['supplier', 'product'],
            'warehouse' => ['inventory'],
            'serial_numbers' => ['inventory'],
            'table_management' => ['pos'],
            'kitchen' => ['pos'],
            'delivery' => ['pos'],
            'digital_menu' => ['product'],
            'recipe' => ['product', 'inventory'],
            'restaurant' => ['pos', 'product'],
            'appointment' => ['service'],
            'vehicle' => ['customer'],
            'work_order' => ['vehicle', 'service'],
            'quotation' => ['invoice'],
            'accounts_receivable' => ['invoice', 'customer'],
            'accounts_payable' => ['purchase'],
            'reservation' => ['table_management'],
            'warranty' => ['invoice'],
        ];
    }

    /**
     * Tipos de negocio (§7 del master prompt).
     *
     * @return list<array{code: string, name: string, description: string, icon: string}>
     */
    public static function businessTypes(): array
    {
        $types = [
            ['restaurant', 'Restaurante', 'Servicio en mesa, cocina y delivery.', 'restaurant'],
            ['cafeteria', 'Cafetería', 'Barra rápida y para llevar.', 'cafe'],
            ['food_truck', 'Food Truck', 'Venta móvil de comida.', 'truck'],
            ['minimarket', 'Colmado / Minimarket', 'Venta al detalle con inventario.', 'store'],
            ['supermarket', 'Supermercado', 'Inventario avanzado y lotes.', 'cart'],
            ['warehouse_business', 'Bodega / Almacén', 'Almacenamiento y transferencias.', 'warehouse'],
            ['distributor', 'Distribuidora', 'Ventas a crédito y rutas.', 'delivery'],
            ['general_store', 'Tienda general', 'Venta al detalle.', 'bag'],
            ['hardware_store', 'Ferretería', 'Repuestos y materiales.', 'tools'],
            ['barbershop', 'Barbería / Salón', 'Servicios y citas por empleado.', 'scissors'],
            ['mechanic', 'Taller mecánico', 'Vehículos y órdenes de trabajo.', 'wrench'],
            ['auto_parts', 'Repuestos', 'Piezas con series y garantías.', 'gear'],
            ['professional_services', 'Servicios profesionales', 'Servicios y cotizaciones.', 'briefcase'],
            ['custom', 'Negocio personalizado', 'Configuración manual de módulos.', 'sliders'],
        ];

        return array_map(static fn (array $type): array => [
            'code' => $type[0],
            'name' => $type[1],
            'description' => $type[2],
            'icon' => $type[3],
        ], $types);
    }

    /**
     * Presets por tipo de negocio (§9). Cada entrada define módulos habilitados
     * por defecto y módulos recomendados (§9 del master prompt).
     *
     * @return array<string, array{default: list<string>, recommended: list<string>}>
     */
    public static function businessTypePresets(): array
    {
        $base = ['customer', 'payment', 'cash_register', 'invoice', 'report', 'printer'];

        return [
            'restaurant' => [
                'default' => [...$base, 'pos', 'product', 'restaurant'],
                'recommended' => ['delivery', 'digital_menu', 'inventory', 'recipe', 'service', 'electronic_invoice'],
            ],
            'cafeteria' => [
                'default' => [...$base, 'pos', 'product'],
                'recommended' => ['inventory', 'kitchen', 'electronic_invoice'],
            ],
            'food_truck' => [
                'default' => [...$base, 'pos', 'product'],
                'recommended' => ['inventory', 'electronic_invoice'],
            ],
            'minimarket' => [
                'default' => [...$base, 'pos', 'product', 'inventory', 'barcode'],
                'recommended' => ['supplier', 'purchase', 'advanced_inventory', 'electronic_invoice'],
            ],
            'supermarket' => [
                'default' => [...$base, 'pos', 'product', 'inventory', 'advanced_inventory', 'barcode', 'supplier', 'purchase', 'warehouse'],
                'recommended' => ['electronic_invoice', 'loyalty'],
            ],
            'warehouse_business' => [
                'default' => [...$base, 'product', 'inventory', 'advanced_inventory', 'warehouse', 'supplier', 'purchase', 'barcode'],
                'recommended' => ['pos', 'electronic_invoice'],
            ],
            'distributor' => [
                'default' => [...$base, 'product', 'inventory', 'advanced_inventory', 'supplier', 'purchase', 'warehouse', 'accounts_receivable', 'accounts_payable'],
                'recommended' => ['pos', 'delivery', 'electronic_invoice'],
            ],
            'general_store' => [
                'default' => [...$base, 'pos', 'product', 'inventory', 'barcode'],
                'recommended' => ['supplier', 'purchase', 'service', 'electronic_invoice'],
            ],
            'hardware_store' => [
                'default' => [...$base, 'pos', 'product', 'inventory', 'barcode', 'supplier', 'purchase'],
                'recommended' => ['service', 'serial_numbers', 'electronic_invoice'],
            ],
            'barbershop' => [
                'default' => [...$base, 'pos', 'service', 'appointment', 'employee'],
                'recommended' => ['product', 'inventory', 'loyalty', 'electronic_invoice'],
            ],
            'mechanic' => [
                'default' => [...$base, 'service', 'product', 'vehicle', 'work_order', 'quotation', 'inventory'],
                'recommended' => ['pos', 'supplier', 'purchase', 'electronic_invoice'],
            ],
            'auto_parts' => [
                'default' => [...$base, 'pos', 'product', 'inventory', 'barcode', 'serial_numbers', 'supplier', 'purchase'],
                'recommended' => ['warranty', 'service', 'electronic_invoice'],
            ],
            'professional_services' => [
                'default' => [...$base, 'service', 'quotation'],
                'recommended' => ['appointment', 'accounts_receivable', 'electronic_invoice'],
            ],
            'custom' => [
                'default' => $base,
                'recommended' => ['pos', 'product', 'inventory', 'service', 'electronic_invoice'],
            ],
        ];
    }
}
