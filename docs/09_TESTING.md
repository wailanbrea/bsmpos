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
2. [x] Venta rápida POS: producto → apertura de caja → cobrar efectivo → devuelta → ticket B02 → inventario. Automatizado con `pos-sale.spec.ts`.
3. [x] Venta con pago mixto y multimoneda: tarjeta DOP + efectivo USD, tasa visible, referencia de tarjeta, factura e inventario. Automatizado con `pos-sale.spec.ts`.
4. Flujo restaurante: mesa → orden → cocina → precuenta → pago.
5. Inventario: compra con lote y vencimiento → venta FEFO → kardex refleja movimientos.
6. Producto vencido: bloqueo/advertencia según configuración.
7. Cierre de caja con arqueo y diferencia.
8. e-CF mock: emitir → aceptada; forzar rechazo → corregir → reintentar.
9. Activar/desactivar módulo → menú y rutas reaccionan (403 y ocultamiento).
10. Sin stock: venta bloqueada si inventario activo.

### Runner base implementado

`npm run test:e2e` ejecuta Chromium contra una SQLite exclusiva que se recrea en cada corrida. Sus 18 escenarios actuales cubren acceso, credenciales inválidas, selección de contexto, cierre de sesión, navegación y gates de módulo, configuración fiscal, POS accesible, e-CF desactivado, el ciclo completo de 2FA TOTP, venta POS fiscal con caja/efectivo/cambio/B02/ticket/inventario, pago mixto tarjeta DOP + efectivo USD, la activación/desactivación de un módulo opcional con protección del núcleo, el bloqueo de venta de un producto sin stock con su mensaje de error en el POS, el vertical Barbería (agendar cita con total ITBIS y transición de estado) y el vertical Taller (registrar vehículo, crear orden de trabajo con servicio + mano de obra y transición de estado). El runner no reutiliza servidores por defecto, para no mezclar bases E2E de ejecuciones previas; `PLAYWRIGHT_REUSE_SERVER=true` es solo para depuración local.

Los flujos de negocio restantes enumerados arriba siguen siendo cobertura pendiente del runner. La suite base no los sustituye.

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

## Implementado en Fase 13: reportes base

- Feature tests de ventas: incluye solo facturas pagadas del tenant, excluye anuladas y registros externos, verifica exportación CSV y los desgloses por producto, categoría, método de pago, cajero y cliente.
- Feature tests de impuestos y descuentos: agrega impuesto por línea y total de descuentos sin convertir dinero a `float`.
- Feature tests del Formato 608: exportación TXT mensual delimitada por pipe y rechazo seguro de anulaciones sin motivo fiscal.
- Feature tests del Formato 607: distribución de pagos mixtos por método y exclusión de facturas B02 bajo el umbral de detalle.

## Implementado en Fase 10: e-CF y onboarding operativo

- Feature tests del módulo e-CF: módulo inactivo, provider Mock, settings inactivos, reintento y permisos.
- Feature tests de presets por giro: restaurante, ferretería, supermercado, barbería y food truck.

## Implementado en Fase 1: roles y permisos

- Feature tests de catálogo por código, actualización de permisos, bloqueo del rol propietario y desactivación segura de roles sin usuarios.

## Regla de trabajo

Cada módulo cierra con sus pruebas verdes + lint + verificación en navegador real (skill `depurar-web` / Playwright MCP) antes de pasar al siguiente. Los comandos y resultados se reportan en el formato obligatorio (§31 del master prompt).
