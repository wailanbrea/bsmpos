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

## Regla de trabajo

Cada módulo cierra con sus pruebas verdes + lint + verificación en navegador real (skill `depurar-web` / Playwright MCP) antes de pasar al siguiente. Los comandos y resultados se reportan en el formato obligatorio (§31 del master prompt).
