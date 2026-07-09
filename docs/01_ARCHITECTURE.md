# 01 — Arquitectura

## Patrón: Monolito Modular

Un solo despliegue Laravel + Vue, con fronteras de módulo estrictas:

- **Backend:** `app/Modules/<Modulo>/` — cada módulo contiene sus propios Controllers, Requests, Resources, Models, Services, Actions, Policies, Events, Jobs y Tests.
- **Frontend:** `resources/js/modules/<modulo>/` — pages, components, services, store, types, routes.
- **Comunicación entre módulos:** por Services/Actions públicos o Events/Listeners. Prohibido que un módulo consulte modelos internos de otro directamente (usar el service del módulo dueño).
- **Controladores delgados**: validación en Form Requests, lógica en Services/Actions, respuesta con API Resources.

## Multi-tenancy

- **Una base de datos**, tenant por columna `company_id` (y `branch_id` cuando aplique).
- Global scope `BelongsToCompany` en todos los modelos multiempresa + middleware `SetCurrentCompany`/`SetCurrentBranch` que resuelven el contexto desde el token/headers.
- **Prohibido** el acceso cruzado entre empresas; toda query pasa por el scope y las Policies lo re-verifican.

## Capas de protección de una ruta

```
Route::middleware(['auth:sanctum', 'company', 'branch', 'module:pos', 'permission:pos.sell'])
```

1. `auth:sanctum` — autenticación.
2. `company` — empresa activa seleccionada y usuario pertenece a ella.
3. `branch` — sucursal activa válida para el usuario.
4. `module:<code>` — módulo activo para la empresa/sucursal/plan (403 si no).
5. `permission:<code>` — permiso del rol.
6. Policies por modelo como última línea.

## Estructura backend

```
app/
  Core/                     # Soporte transversal (ApiResponse, BaseModel, Money, traits, middleware globales)
  Modules/
    Auth/  Company/  Branch/  UserAccess/  ModuleManager/  Setting/
    Customer/  Product/  Service/
    Inventory/  AdvancedInventory/  Warehouse/  Purchase/  Supplier/
    POS/  Order/  Invoice/  ElectronicInvoice/  Payment/  CashRegister/
    TableManagement/  Delivery/  Kitchen/  DigitalMenu/
    Appointment/  Vehicle/  WorkOrder/
    Report/  Printer/  Audit/
```

Cada módulo (según aplique):

```
app/Modules/<Nombre>/
  Actions/  DTOs/  Enums/  Events/  Exceptions/
  Http/{Controllers,Requests,Resources}/
  Jobs/  Listeners/  Models/  Policies/  Services/  Support/  Tests/
  routes.php          # rutas del módulo, registradas por un ModuleServiceProvider
  module.json         # metadatos (code, name, category, is_core, dependencias)
```

## Convenciones críticas

- **Dinero:** `DECIMAL(14,2)` (cantidades de inventario `DECIMAL(14,4)`). Prohibido float/double. Cálculos con enteros de centavos o `brick/money`; redondeo half-up a 2 decimales.
- **IDs:** autoincrement interno + **ULID público** (`public_id`) en entidades expuestas por API.
- **Borrado:** soft deletes en entidades de negocio; nunca borrado físico de documentos fiscales (solo anulación).
- **Fechas:** almacenar UTC, mostrar `America/Santo_Domingo`; zona horaria configurable por empresa.
- **Numeraciones** (facturas, NCF, órdenes): generadas dentro de transacción con `lockForUpdate()` sobre la fila de secuencia — nunca `MAX(+1)`.
- **Idempotencia:** endpoints de creación de venta/factura/pago aceptan `Idempotency-Key` para evitar duplicados por reintentos del POS.
- **Auditoría:** trait `Auditable` + eventos en acciones sensibles (precios, anulaciones, caja, módulos, fiscal).

## Respuesta API estándar

```json
{ "success": true, "data": {...}, "message": null, "meta": { "pagination": {...} } }
{ "success": false, "error": { "code": "MODULE_DISABLED", "message": "...", "details": {...} } }
```

Errores con códigos estables (enum `ErrorCode`) para que el frontend traduzca/reaccione.

## Eventos de dominio clave

`OrderPaid`, `InvoiceIssued`, `InvoiceCanceled`, `StockDepleted`, `BatchNearExpiration`, `CashSessionClosed`, `ModuleToggled`, `ElectronicInvoiceAccepted/Rejected` — los módulos opcionales se enganchan por listeners sin acoplar el núcleo.

## Decisiones registradas

Ver [13_DECISIONS.md](13_DECISIONS.md).
