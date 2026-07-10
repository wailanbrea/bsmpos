# 03 — Documentación de API

> Se completa a medida que se implementen endpoints. Aquí quedan las convenciones y el mapa previsto.

## Convenciones

- Base: `/api/v1/`
- Auth: Laravel Sanctum (token Bearer). Headers de contexto: `X-Company-Id`, `X-Branch-Id` (validados contra el usuario).
- Respuesta estándar `{ success, data, message, meta }` / error `{ success:false, error:{ code, message, details } }`.
- Paginación: `?page=&per_page=` → `meta.pagination`. Filtros con query params documentados por endpoint. Orden: `?sort=-created_at`.
- Idempotencia: header `Idempotency-Key` obligatorio en `POST /orders/*/pay`, `POST /invoices`.
- Los tipos de comprobante fiscales usan códigos canónicos (`B01`, `B02`, `B04`, `E31`, …). La emisión de factura acepta temporalmente `01` y `02` por compatibilidad, pero responde y persiste el código canónico.
- Errores por módulo inactivo: HTTP 403, `error.code = MODULE_DISABLED`.
- Rate limiting: global + estricto en `/auth/login`.
- Versionado: rompimientos → `/api/v2`.

## Mapa de endpoints previsto (por fase)

| Módulo | Prefijo | Estado |
|---|---|---|
| Auth | `/auth/login`, `/auth/logout`, `/auth/me`, `/auth/select-company`, `/auth/select-branch` | Pendiente |
| Companies | `/companies`, `/onboarding` | Pendiente |
| Branches | `/branches`, `/branches/{publicId}` | Implementado (Fase 1) |
| Users/Roles | `/users`, `/users/{publicId}/access`, `/roles`, `/roles/{publicId}`, `/permissions` | Roles/permisos implementados; usuarios parciales (Fase 1) |
| Modules | `/modules`, `/modules/{code}/enable|disable`, `/business-types`, `/onboarding` | Implementado (Fase 2) |
| Settings | `/settings/fiscal`, `/settings/{group}`, `/taxes`, `/payment-methods`, `/ncf-sequences`, `/exchange-rates` | Implementado (Fase 3) |
| Customers | `/customers` (search, paginado), `/customers/{id}`, `/customers/{id}/credit` | Implementado (Fase 4) |
| Products | `/categories`, `/products`, `/products/{id}/variants|modifiers|images` | Pendiente |
| Services | `/service-categories`, `/services` | Pendiente |
| Inventory | `/warehouses`, `/stock`, `/batches`, `/movements`, `/transfers`, `/adjustments`, `/kardex/{productId}`, `/alerts` | Pendiente |
| Purchases | `/suppliers`, `/purchases` | Pendiente |
| POS | `/orders`, `/orders/{id}/items|send-kitchen|pay` | Pendiente |
| Cash | `/cash-registers`, `/cash-sessions`, `/cash-sessions/{id}/movements|close` | Pendiente |
| Invoices | `/invoices`, `/invoices/{id}/pdf|ticket|cancel`, `/quotes`, `/credit-notes` | Pendiente |
| e-CF | `/electronic-invoices`, `/electronic-invoices/{id}/retry|logs`, `/electronic-invoices/settings` | Implementado con providers Null/Mock; provider DGII/PSFE pendiente |
| Reports | `/reports/sales|cash|inventory|taxes|dgii-606|dgii-607|dgii-608` | Ventas/caja, CSV y desgloses implementados; DGII 608 TXT implementado, 606/607 pendientes |

## Reportes operativos (Fase 13)

Requieren `auth:sanctum`, contexto de compañía y `reports.view`. Los filtros opcionales son `from`, `to` (fechas inclusivas) y `branch_id` (ULID de la compañía activa).

### `GET /api/v1/reports/sales`
Devuelve facturas pagadas del período, resumen de subtotal/descuentos/impuestos/propina/total y detalle por factura. Excluye anuladas y datos de otras empresas.

### `GET /api/v1/reports/sales/export.csv`
Exporta las mismas filas de ventas filtradas en CSV UTF-8.

### `GET /api/v1/reports/sales/by-product`
Agrupa las líneas de facturas pagadas por producto (ULID, nombre, SKU, cantidad y total). No incluye facturas anuladas ni líneas de otros tenants.

### `GET /api/v1/reports/sales/by-category`
Agrupa las líneas de facturas pagadas por categoría (ULID, nombre, cantidad y total). Los productos sin categoría se consolidan como `Sin categoría`.

### `GET /api/v1/reports/sales/by-payment-method`
Agrupa los cobros asociados a órdenes con factura pagada por método y moneda. El total se calcula en moneda base neto de devuelta.

### `GET /api/v1/reports/sales/by-cashier`
Agrupa facturas pagadas por el usuario que las emitió, incluyendo cantidad de facturas y total.

### `GET /api/v1/reports/sales/by-customer`
Agrupa facturas pagadas por cliente, incluyendo cantidad de facturas y total.

### `GET /api/v1/reports/sales/taxes`
Agrupa líneas de facturas pagadas por impuesto, con base gravable y monto del impuesto. Las líneas sin impuesto se muestran explícitamente como `sin_impuesto`.

### `GET /api/v1/reports/sales/discounts`
Lista facturas pagadas con descuento y devuelve el total agregado de descuentos, preservado como DECIMAL.

### `GET /api/v1/reports/cash`
Devuelve sesiones de caja cerradas del período con total esperado, contado y diferencia.

### `GET /api/v1/reports/dgii/608?period=YYYY-MM`
Genera y descarga el TXT delimitado por `|` de NCF anulados del mes, con encabezado `608|RNC/Cédula|AAAAMM|cantidad`. Solo incluye comprobantes tradicionales con NCF completo y requiere que cada anulación tenga fecha y código de motivo DGII (1–10); ante datos incompletos responde `422 VALIDATION_FAILED` en lugar de producir un archivo fiscal inválido.

### `GET /api/v1/reports/dgii/606?period=YYYY-MM`
Genera el TXT delimitado por `|` de compras y gastos confirmados del mes. Requiere el snapshot `purchase_fiscal_data` completo por cada compra; un registro faltante o identificador/NCF inválido retorna `422 VALIDATION_FAILED`.

### `GET /api/v1/reports/dgii/607?period=YYYY-MM`
Genera el TXT delimitado por `|` de ventas tradicionales pagadas. Reporta B02 desde RD$50,000 cuando tiene identificación del cliente; distribuye pagos por método y rechaza facturas que requieren identificación sin datos fiscales.

### `PUT /api/v1/purchases/{publicId}/fiscal-data`
Registra o actualiza el snapshot fiscal de una compra para 606. Requiere `purchases.manage` y recibe identificación del proveedor, NCF, clasificación de gasto, montos de bienes/servicios, ITBIS, retenciones e indicador de forma de pago. Calcula `total_billed` e `itbis_advance`; rechaza un ITBIS llevado al costo superior al facturado.
| Audit | `/audit-logs`, `/audit-logs/{publicId}` | Implementado (Fase 1) |

## Módulos y onboarding (Fase 2)

Todas requieren `auth:sanctum` + contexto de compañía (`X-Company-Id`). Consultar requiere `modules.view`; activar/desactivar y onboarding requieren `modules.manage`. `GET /business-types` solo requiere compañía.

### `GET /api/v1/modules`
Lista el catálogo de módulos con `is_core` e `is_enabled` para la compañía, el arreglo `enabled` (incluye núcleo) y `business_type` (bool si ya se eligió tipo de negocio).

### `POST /api/v1/modules/{code}/enable` · `POST /api/v1/modules/{code}/disable`
Activa/desactiva un módulo opcional; body opcional `reason`. Errores: `CONFLICT` 409 con `details.requires` (dependencias faltantes) o `details.dependent` (al desactivar un módulo del que otro depende), `FORBIDDEN` 403 (plan no lo permite), núcleo no desactivable. Devuelve el nuevo arreglo `enabled`.

### `GET /api/v1/business-types[?business_type=<code>]`
Devuelve `business_types`. Con `business_type` añade `modules` (catálogo con `enabled_by_default`/`is_recommended` según el preset).

### `POST /api/v1/onboarding`
Body: `business_type` (requerido) y `modules` (opcional, extra a activar). Aplica el preset (habilita defaults ordenados por dependencias), fija el tipo de negocio de la compañía y activa los módulos extra. Devuelve `enabled` y `business_type`.

Además, el onboarding precarga los ajustes operativos por giro (propina, inventario, comprobante e impresión).

## Facturación electrónica (Fase 10)

Requiere `auth:sanctum`, compañía activa y módulo `electronic_invoice` habilitado. Consulta: `einvoice.view`; configuración y reintento: `einvoice.manage`.

### `GET /api/v1/electronic-invoices` · `GET /api/v1/electronic-invoices/{publicId}/logs`

Lista comprobantes e-CF de la compañía y sus intentos, sin exponer credenciales.

### `GET/PUT /api/v1/electronic-invoices/settings`

Consulta o actualiza `provider_code` (`null|mock`), ambiente (`test|cert|prod`) y activación.

### `POST /api/v1/electronic-invoices/{publicId}/retry`

Reintenta un registro rechazado o en error; nunca altera la factura interna si el provider falla.

## Configuración fiscal (Fase 3)

`auth:sanctum` + `X-Company-Id`. Lectura con `settings.view`; escritura con `settings.manage`.

### `GET /api/v1/settings/fiscal`
Devuelve `taxes`, `payment_methods` (de la compañía), `document_types` (catálogo global NCF/e-CF) y la moneda base.

### `POST /api/v1/taxes` · `PATCH /api/v1/taxes/{publicId}`
Alta/edición de impuestos: `name`, `code` (único por compañía, `[a-z0-9_]`), `rate` (0–100), `type` (percentage|fixed), `scope` (product|service|both), `is_inclusive`, `is_retention`. La edición admite además `is_active`.

### `POST /api/v1/payment-methods`
Alta de método de pago: `name`, `code` (único por compañía), `requires_reference`.

### `GET/POST /api/v1/ncf-sequences`
Lista o crea secuencias NCF/e-CF: `document_type_code` (del catálogo), `start_number`, `end_number` (> start), `expires_at`, `alert_threshold`. La reserva de números en venta/factura la hace `NcfSequenceService::reserve()` con `lockForUpdate` (ver [06_ELECTRONIC_INVOICING.md](06_ELECTRONIC_INVOICING.md)).

### `GET/PUT /api/v1/settings/{group}`
Configuración por grupo (`pos`, `inventory`, `invoice`, `printing`, `security`, `backup`). GET devuelve `schema` (tipo/opciones/default por clave) y `values` efectivos. PUT recibe `{ values: {...} }`; solo se aceptan claves del esquema y los `enum` se validan. Grupo inexistente → 404.

### `GET/POST /api/v1/exchange-rates`
Histórico de tasas de cambio. POST recibe `currency_code` (USD/EUR/DOP), `rate` (>0) y `effective_date` (upsert por moneda+fecha). GET lista las 50 más recientes.

## Clientes (Fase 4)

`auth:sanctum` + `X-Company-Id` + `module:customer`. Lectura `customers.view`, escritura `customers.manage`, crédito `customers.credit`.

### `GET /api/v1/customers?search=&page=`
Lista clientes de la compañía (genérico primero), filtrando por nombre/RNC/teléfono; paginado (`meta.pagination`).

### `POST /api/v1/customers` · `PATCH /api/v1/customers/{publicId}`
Alta/edición: `kind` (person|company|generic), `name`, `tax_id_type` (rnc|cedula|passport|nif|none), `tax_id` (validado con dígito verificador y único por compañía), contacto, `credit_limit`, `credit_days`.

### `POST /api/v1/customers/{publicId}/credit`
Registra `type` (charge|payment|adjustment) y `amount` (>0). `charge` respeta el límite de crédito; `payment` no puede dejar balance negativo. Devuelve el cliente actualizado.

## Roles y permisos (Fase 1)

Las consultas de catálogo y roles requieren `access.roles.view`; crear, actualizar o desactivar exige `access.roles.manage`. Los permisos se identifican por `code`, nunca por su ID interno.

### `GET /api/v1/permissions`

Expone el catálogo global de permisos con código, nombre, módulo y descripción.

### `GET/POST /api/v1/roles`

Lista los roles de la compañía activa o crea uno personalizado. El alta recibe `code`, `name`, `description` opcional y `permission_codes` (array, que puede estar vacío).

### `PATCH /api/v1/roles/{publicId}`

Actualiza nombre, descripción y reemplaza los permisos mediante `permission_codes`. El código del rol es inmutable. Los roles del sistema no se modifican.

### `DELETE /api/v1/roles/{publicId}`

Desactiva un rol personalizado mediante soft delete. Rechaza roles del sistema y cualquier rol todavía asignado a usuarios (`409 CONFLICT`).

## Usuarios por compañía (Fase 1)

Los endpoints requieren `auth:sanctum`, `X-Company-Id` válido y `access.users.manage`. Administran únicamente cuentas ya registradas; no envían invitaciones ni crean contraseñas.

### `GET /api/v1/users`

Lista los miembros de la compañía activa con sus sucursales, rol(es) y sucursal predeterminada.

### `POST /api/v1/users`

Concede acceso a una cuenta activa existente identificada por `email`. Requiere al menos una sucursal (`branch_ids`), permite roles de la compañía (`role_ids`) y una `default_branch_id` incluida en la selección. Devuelve `422` si alguna sucursal o rol pertenece a otro tenant.

### `PATCH /api/v1/users/{publicId}/access`

Reemplaza las sucursales, rol(es) y sucursal predeterminada del miembro para la compañía activa sin tocar sus accesos en otras compañías. No permite modificar al propietario (`409 CONFLICT`).

## Sucursales (Fase 1)

Los tres endpoints requieren `auth:sanctum`, `X-Company-Id` válido y `company.manage`. Cada consulta y mutación se limita a la compañía del contexto; los IDs expuestos son ULID.

### `GET /api/v1/branches`

Lista las sucursales de la compañía activa, con la principal primero.

### `POST /api/v1/branches`

Crea una sucursal activa. Acepta `name`, `code`, `phone` y `address`; el código se normaliza a mayúsculas y es único por compañía. El administrador que la crea queda asignado a ella.

### `PATCH /api/v1/branches/{publicId}`

Actualiza nombre, código, contacto, dirección o estado. No permite mover una sucursal entre compañías ni desactivar la sucursal principal (`409 CONFLICT`).

## Auditoría (Fase 1)

Ambos endpoints requieren `auth:sanctum`, `X-Company-Id` válido y el permiso `audit.view`. La consulta está siempre limitada a la compañía seleccionada; no expone el identificador interno ni valores de auditoría sensibles.

### `GET /api/v1/audit-logs`

Lista eventos de la compañía, ordenados del más reciente al más antiguo.

- Filtros opcionales: `action` (valor exacto), `module` (prefijo de acción), `user_id` (ULID del usuario), `from` y `to` (`YYYY-MM-DD`), `per_page` (1–100).
- Respuesta: colección de `id` (ULID), `action`, `module`, responsable, datos antes/después redactados, IP y fecha, más `meta.pagination`.
- Errores: `422 VALIDATION_FAILED` para filtros inválidos; `403 PERMISSION_DENIED` sin permiso; `403 TENANT_ACCESS_DENIED` sin acceso a la compañía.

### `GET /api/v1/audit-logs/{publicId}`

Devuelve un evento de la compañía seleccionada. Un ULID ajeno o inexistente responde `404` para no filtrar datos de otro tenant.

Cada endpoint implementado debe documentarse aquí con: método, ruta, permisos/módulo requerido, request body, respuesta ejemplo y errores.
