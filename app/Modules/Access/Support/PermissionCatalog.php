<?php

declare(strict_types=1);

namespace App\Modules\Access\Support;

final class PermissionCatalog
{
    /** @return list<array{code: string, name: string, module_code: string, description: string}> */
    public static function defaults(): array
    {
        return [
            ['code' => 'company.manage', 'name' => 'Gestionar compañía', 'module_code' => 'company', 'description' => 'Editar la configuración de la compañía.'],
            ['code' => 'access.roles.view', 'name' => 'Ver roles', 'module_code' => 'access', 'description' => 'Consultar roles y sus permisos.'],
            ['code' => 'access.roles.manage', 'name' => 'Gestionar roles', 'module_code' => 'access', 'description' => 'Crear y modificar roles.'],
            ['code' => 'access.users.manage', 'name' => 'Gestionar usuarios', 'module_code' => 'access', 'description' => 'Invitar usuarios y asignar accesos.'],
            ['code' => 'audit.view', 'name' => 'Ver auditoría', 'module_code' => 'audit', 'description' => 'Consultar el historial de auditoría.'],
            ['code' => 'modules.view', 'name' => 'Ver módulos', 'module_code' => 'module_manager', 'description' => 'Consultar los módulos de la compañía.'],
            ['code' => 'modules.manage', 'name' => 'Gestionar módulos', 'module_code' => 'module_manager', 'description' => 'Activar o desactivar módulos y completar el onboarding.'],
            ['code' => 'settings.view', 'name' => 'Ver configuración', 'module_code' => 'setting', 'description' => 'Consultar impuestos, métodos de pago y secuencias.'],
            ['code' => 'settings.manage', 'name' => 'Gestionar configuración', 'module_code' => 'setting', 'description' => 'Editar impuestos, métodos de pago y secuencias NCF.'],
            ['code' => 'customers.view', 'name' => 'Ver clientes', 'module_code' => 'customer', 'description' => 'Consultar el directorio de clientes.'],
            ['code' => 'customers.manage', 'name' => 'Gestionar clientes', 'module_code' => 'customer', 'description' => 'Crear y editar clientes.'],
            ['code' => 'customers.credit', 'name' => 'Gestionar crédito', 'module_code' => 'customer', 'description' => 'Registrar cargos y abonos de crédito.'],
            ['code' => 'products.view', 'name' => 'Ver productos', 'module_code' => 'product', 'description' => 'Consultar productos y categorías.'],
            ['code' => 'products.manage', 'name' => 'Gestionar productos', 'module_code' => 'product', 'description' => 'Crear y editar productos y categorías.'],
            ['code' => 'services.view', 'name' => 'Ver servicios', 'module_code' => 'service', 'description' => 'Consultar servicios.'],
            ['code' => 'services.manage', 'name' => 'Gestionar servicios', 'module_code' => 'service', 'description' => 'Crear y editar servicios.'],

            // Empleados y citas (barbería / salón)
            ['code' => 'employees.view', 'name' => 'Ver empleados', 'module_code' => 'employee', 'description' => 'Consultar empleados y comisiones.'],
            ['code' => 'employees.manage', 'name' => 'Gestionar empleados', 'module_code' => 'employee', 'description' => 'Crear y editar empleados y sus comisiones.'],
            ['code' => 'appointments.view', 'name' => 'Ver citas', 'module_code' => 'appointment', 'description' => 'Consultar la agenda de citas.'],
            ['code' => 'appointments.manage', 'name' => 'Gestionar citas', 'module_code' => 'appointment', 'description' => 'Agendar citas y cambiar su estado.'],

            // Taller mecánico (vehículos y órdenes de trabajo)
            ['code' => 'vehicles.view', 'name' => 'Ver vehículos', 'module_code' => 'vehicle', 'description' => 'Consultar vehículos de clientes.'],
            ['code' => 'vehicles.manage', 'name' => 'Gestionar vehículos', 'module_code' => 'vehicle', 'description' => 'Registrar y editar vehículos.'],
            ['code' => 'work_orders.view', 'name' => 'Ver órdenes de trabajo', 'module_code' => 'work_order', 'description' => 'Consultar órdenes de taller.'],
            ['code' => 'work_orders.manage', 'name' => 'Gestionar órdenes de trabajo', 'module_code' => 'work_order', 'description' => 'Crear órdenes de taller y cambiar su estado.'],

            // Inventario, almacenes y compras
            ['code' => 'suppliers.view', 'name' => 'Ver proveedores', 'module_code' => 'supplier', 'description' => 'Consultar proveedores.'],
            ['code' => 'suppliers.manage', 'name' => 'Gestionar proveedores', 'module_code' => 'supplier', 'description' => 'Crear y editar proveedores.'],
            ['code' => 'warehouses.view', 'name' => 'Ver almacenes', 'module_code' => 'warehouse', 'description' => 'Consultar almacenes.'],
            ['code' => 'warehouses.manage', 'name' => 'Gestionar almacenes', 'module_code' => 'warehouse', 'description' => 'Crear y editar almacenes.'],
            ['code' => 'purchases.view', 'name' => 'Ver compras', 'module_code' => 'purchase', 'description' => 'Consultar compras.'],
            ['code' => 'purchases.manage', 'name' => 'Gestionar compras', 'module_code' => 'purchase', 'description' => 'Crear y editar compras.'],
            ['code' => 'inventory.view', 'name' => 'Ver existencias', 'module_code' => 'inventory', 'description' => 'Ver existencias de productos.'],
            ['code' => 'inventory.manage', 'name' => 'Ajustar inventario', 'module_code' => 'inventory', 'description' => 'Ajustar existencias e inventario.'],

            // POS
            ['code' => 'pos.view', 'name' => 'Ver ventas del POS', 'module_code' => 'pos', 'description' => 'Consultar órdenes y ventas.'],
            ['code' => 'pos.sell', 'name' => 'Realizar ventas en el POS', 'module_code' => 'pos', 'description' => 'Crear y cobrar órdenes en el POS táctil.'],

            // Facturación
            ['code' => 'invoices.view', 'name' => 'Ver facturas', 'module_code' => 'invoice', 'description' => 'Consultar el historial de facturas emitidas.'],
            ['code' => 'invoices.manage', 'name' => 'Gestionar facturas', 'module_code' => 'invoice', 'description' => 'Facturar, anular y emitir notas de crédito.'],

            // Facturación electrónica (e-CF)
            ['code' => 'einvoice.view', 'name' => 'Ver comprobantes electrónicos', 'module_code' => 'electronic_invoice', 'description' => 'Consultar comprobantes e-CF, estados y bitácora.'],
            ['code' => 'einvoice.manage', 'name' => 'Gestionar e-CF', 'module_code' => 'electronic_invoice', 'description' => 'Configurar el proveedor y reintentar transmisiones.'],
            ['code' => 'reports.view', 'name' => 'Ver reportes', 'module_code' => 'report', 'description' => 'Consultar reportes operativos y exportarlos.'],
        ];
    }
}
