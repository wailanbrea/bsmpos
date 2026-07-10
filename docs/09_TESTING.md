# 09 — Estrategia de Pruebas

## Herramientas

| Nivel | Herramienta |
|---|---|
| Backend unit/feature | **Pest** (elegido sobre PHPUnit — ver DECISIONS) |
| Análisis estático | Larastan (nivel ≥6), Pint |
| Frontend unit | Vitest + Vue Test Utils |
| E2E navegador | **Playwright** (flujos críticos) — además de la verificación manual con Playwright MCP durante el desarrollo |
| Lint front | ESLint + Prettier + vue-tsc |

## Cobertura obligatoria backend (master prompt §29)

Auth, empresas/sucursales (incl. **tests de aislamiento de tenant**), módulos (activar/desactivar, dependencias, plan), permisos, clientes, productos/servicios, inventario (lotes, vencimientos, **FEFO/FIFO con casos de empate y lotes parciales**), POS (totales con DECIMAL, descuentos, impuestos), pagos (mixto que cuadra, devuelta, multimoneda con tasa), caja (apertura/cierre/diferencia), facturas (secuencias NCF concurrentes — test de race condition, anulación con reverso de inventario, notas de crédito), facturación electrónica con Mock (aceptada/rechazada/reintento/contingencia), auditoría, idempotencia de venta.

## E2E Playwright (Fase 14, sobre app real)

1. Onboarding completo: crear empresa → tipo de negocio → módulos recomendados → configuración inicial.
2. Venta rápida POS: producto → cobrar efectivo → devuelta → ticket.
3. Venta con pago mixto y multimoneda.
4. Flujo restaurante: mesa → orden → cocina → precuenta → pago.
5. Inventario: compra con lote y vencimiento → venta FEFO → kardex refleja movimientos.
6. Producto vencido: bloqueo/advertencia según configuración.
7. Cierre de caja con arqueo y diferencia.
8. e-CF mock: emitir → aceptada; forzar rechazo → corregir → reintentar.
9. Activar/desactivar módulo → menú y rutas reaccionan (403 y ocultamiento).
10. Sin stock: venta bloqueada si inventario activo.

## Implementado en Fase 1: auditoría

- Feature tests de listado aislado por compañía, detalle por ULID, redacción de datos sensibles y validación de filtros.

## Implementado en Fase 1: usuarios por compañía

- Feature tests de provisión de cuenta existente, sincronización aislada entre compañías, roles/sucursales ajenos y bloqueo de mutación del propietario.
- Prueba Vitest de la pantalla de usuarios: carga del contrato y validación local de sucursal obligatoria antes de enviar la asignación.
- Playwright CLI verificó la ruta `/usuarios` con sesión, contexto tenant y propietario no editable; tras el endurecimiento, el rol del sistema no se ofrece como asignable.

## Implementado en Fase 1: sucursales

- Prueba Vitest de la pantalla de sucursales: la principal se presenta como tal y no permite desactivación desde el formulario.
- Playwright CLI verificó la creación de una sucursal secundaria y la edición de la principal con el control de activación bloqueado, sin errores de consola.

## Implementado en Fase 1: Policies de recursos

- Prueba de autorización por Policy: permisos de sucursal, roles, usuarios y auditoría se conceden únicamente en la compañía del recurso; no se filtran hacia otra empresa y una cuenta inactiva se deniega.

## Implementado en Fase 1: roles y permisos

- Feature tests de catálogo por código, actualización de permisos, bloqueo del rol propietario y desactivación segura de roles sin usuarios.

## Regla de trabajo

Cada módulo cierra con sus pruebas verdes + lint + verificación en navegador real (skill `depurar-web` / Playwright MCP) antes de pasar al siguiente. Los comandos y resultados se reportan en el formato obligatorio (§31 del master prompt).
