# 03 — Documentación de API

> Se completa a medida que se implementen endpoints. Aquí quedan las convenciones y el mapa previsto.

## Convenciones

- Base: `/api/v1/`
- Auth: Laravel Sanctum (token Bearer). Headers de contexto: `X-Company-Id`, `X-Branch-Id` (validados contra el usuario).
- Respuesta estándar `{ success, data, message, meta }` / error `{ success:false, error:{ code, message, details } }`.
- Paginación: `?page=&per_page=` → `meta.pagination`. Filtros con query params documentados por endpoint. Orden: `?sort=-created_at`.
- Idempotencia: header `Idempotency-Key` obligatorio en `POST /orders/*/pay`, `POST /invoices`.
- Errores por módulo inactivo: HTTP 403, `error.code = MODULE_DISABLED`.
- Rate limiting: global + estricto en `/auth/login`.
- Versionado: rompimientos → `/api/v2`.

## Mapa de endpoints previsto (por fase)

| Módulo | Prefijo | Estado |
|---|---|---|
| Auth | `/auth/login`, `/auth/logout`, `/auth/me`, `/auth/select-company`, `/auth/select-branch` | Pendiente |
| Companies | `/companies`, `/onboarding` | Pendiente |
| Branches | `/branches` | Pendiente |
| Users/Roles | `/users`, `/roles`, `/permissions` | Pendiente |
| Modules | `/modules`, `/modules/{code}/enable|disable`, `/business-types` | Pendiente |
| Settings | `/settings/{group}` | Pendiente |
| Customers | `/customers`, `/customers/{id}/history|credit|addresses` | Pendiente |
| Products | `/categories`, `/products`, `/products/{id}/variants|modifiers|images` | Pendiente |
| Services | `/service-categories`, `/services` | Pendiente |
| Inventory | `/warehouses`, `/stock`, `/batches`, `/movements`, `/transfers`, `/adjustments`, `/kardex/{productId}`, `/alerts` | Pendiente |
| Purchases | `/suppliers`, `/purchases` | Pendiente |
| POS | `/orders`, `/orders/{id}/items|send-kitchen|pay` | Pendiente |
| Cash | `/cash-registers`, `/cash-sessions`, `/cash-sessions/{id}/movements|close` | Pendiente |
| Invoices | `/invoices`, `/invoices/{id}/pdf|ticket|cancel`, `/quotes`, `/credit-notes` | Pendiente |
| e-CF | `/electronic-invoices`, `/electronic-invoices/{id}/retry|logs`, `/electronic-invoice-settings` | Pendiente |
| Reports | `/reports/sales|cash|inventory|taxes|dgii-606|dgii-607` | Pendiente |
| Audit | `/audit-logs` | Pendiente |

Cada endpoint implementado debe documentarse aquí con: método, ruta, permisos/módulo requerido, request body, respuesta ejemplo y errores.
