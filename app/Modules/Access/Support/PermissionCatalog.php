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
        ];
    }
}
