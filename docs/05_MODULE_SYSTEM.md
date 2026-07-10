# 05 — Sistema de Módulos

## Catálogo de módulos

### Core (no desactivables)
| Código | Nombre |
|---|---|
| auth | Autenticación |
| company | Empresas |
| branch | Sucursales |
| user_access | Usuarios/Roles/Permisos |
| module_manager | Gestor de módulos |
| setting | Configuración |
| customer | Clientes |
| payment | Pagos |
| cash_register | Caja |
| invoice | Facturación |
| report | Reportes básicos |
| audit | Auditoría |

### Opcionales
| Código | Nombre | Depende de |
|---|---|---|
| pos | POS Touch | invoice |
| product | Productos | — |
| service | Servicios | — |
| inventory | Inventario simple | product |
| advanced_inventory | Inventario avanzado (lotes/vencimientos/FEFO) | inventory |
| barcode | Código de barras | product |
| purchase | Compras | product, supplier |
| supplier | Proveedores | — |
| warehouse | Almacenes/ubicaciones | inventory |
| serial_numbers | Números de serie | inventory |
| table_management | Mesas | pos |
| kitchen | Cocina/KDS | pos |
| delivery | Delivery | pos |
| digital_menu | Menú digital | product |
| recipe | Recetas/insumos | product, inventory |
| appointment | Citas/agenda | service |
| vehicle | Vehículos | customer |
| work_order | Órdenes de trabajo | vehicle, service |
| quotation | Cotizaciones | invoice |
| accounts_receivable | Cuentas por cobrar | invoice, customer |
| accounts_payable | Cuentas por pagar | purchase |
| printer | Impresión | — |
| loyalty | Fidelización | customer |
| reservation | Reservas | table_management |
| warranty | Garantías | invoice |
| electronic_invoice | Facturación electrónica | invoice |
| expense | Gastos | — |
| employee | Empleados/comisiones | — |
| notification | Notificaciones (email/WhatsApp) | — |

## Mapa de dependencias (regla)

`advanced_inventory → inventory → product` · `kitchen/table_management/delivery → pos → invoice` · `work_order → vehicle + service` · `recipe → product + inventory` · `purchase → supplier + product`. Activar un módulo valida (y ofrece activar) sus dependencias; desactivar uno con dependientes activos se bloquea con mensaje claro.

## Tablas

Ver master prompt §8 y [02_DATABASE_SCHEMA.md](02_DATABASE_SCHEMA.md#2-sistema-de-módulos-y-planes): `business_types`, `system_modules`, `module_dependencies`, `business_type_modules`, `company_modules`, `branch_modules`, `subscription_plans`, `plan_modules`, `module_audit_logs`, `company_subscriptions`.

## ModuleManagerService

`isEnabled(companyId, code)` · `isEnabledForBranch(branchId, code)` · `enableModule` · `disableModule` (nunca borra datos) · `getEnabledModules` · `getAvailableModulesForBusinessType` · `validateDependencies` · `validatePlanAllowsModule` · `applyBusinessTypePreset` · `auditModuleChange`. Resultados cacheados por empresa (invalidación al toggle).

## Middleware y frontend

- Backend: `EnsureModuleEnabled` → `module:pos` → 403 `MODULE_DISABLED` si inactivo.
- Frontend: `useModuleStore` (enabledModules, availableModules, canUse, loadModules, isCore) + guard `requiresModule` + menú dinámico.

## Matriz por tipo de negocio

La matriz de presets (restaurante, barbería, taller, tienda/ferretería, supermercado, bodega, distribuidora, servicios profesionales…) está en el master prompt §9 y se implementa como **seeder** de `business_type_modules` (enabled_by_default / is_recommended). 14 tipos iniciales (§7).
