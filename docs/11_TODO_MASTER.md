# 11 — TODO MASTER

> Fuente de verdad del avance. Marcar `[x]` solo con pruebas verdes y documentación actualizada. Fases del master prompt §28 + adiciones del addendum.

**Estado global: FASES 1–14 completas (candidato v1). Seguridad (2FA), suite E2E Playwright (16 escenarios) y CI GitHub Actions operativos. Pendientes: backlog post-v1 (verticales barbería/taller, provider e-CF real, E2E por-vertical) y un seeder base global de Fase 0 (hoy los catálogos se provisionan por compañía).**

**Verificación 2026-07-10:** baseline de calidad restaurada tras correcciones de tipado en POS, inventario, facturación, impresión y restaurante. Pest, Pint, Larastan, vue-tsc, Vitest, ESLint, Prettier y build PWA están verdes.

## Conocimiento del proyecto
- [x] Fase 1 completada: compañías, sucursal principal, membresías, contexto tenant, RBAC, sesiones API, Policies y auditoría base.
- [x] Tests de aislamiento tenant: listado filtrado y denegación de empresa/sucursal ajena.
- [x] RBAC inicial: catálogo de permisos, roles por compañía, rol propietario y middleware `permission:<código>`.
- [x] Sesiones API: registro, login, logout y `me` con Sanctum, rate limits, cuentas inactivas y auditoría.
- [x] UI de acceso y contexto: login, registro, creación inicial de compañía y selección persistente de compañía/sucursal.
- [x] Auditoría mínima: consulta por tenant con permiso, filtros, valores sensibles redactados y pantalla de bitácora.
- [x] Gestión de sucursales: listado, creación, actualización, asignación del administrador y protección de sucursal principal.
- [x] Gestión de acceso de usuarios existentes: membresías, sucursales, roles y sucursal predeterminada aislados por compañía.
- [x] CRUD seguro de roles personalizados and catálogo de permisos por código; roles del sistema y asignados protegidos.
- [x] UI inicial de roles y permisos conectada al API, con estados operativos y protección visual de roles del sistema.
- [x] UI de usuarios por compañía: asignación de cuentas existentes a sucursales y roles, sucursal predeterminada y protección visual del propietario.
- [x] UI de sucursales: listado, creación y actualización con estado activo y protección visual de la sucursal principal.
- [x] Grafo Graphify actualizado con el código de Fases 2–13 + módulo e-CF mediante extracción AST incremental (2390 nodos, 4490 relaciones). La capa semántica de docs/imágenes no se re-extrajo estas sesiones (se conservan sus nodos previos); refrescar con un `/graphify` completo cuando convenga. El extractor aún omite manifiestos `module.json` sin símbolos AST.

## FASE 0 — Base
- [x] Inicializar repositorio Git local (`main`) y vincular el remoto autorizado `origin`
- [x] Crear Laravel 12.63 con PHP 8.3.32 aislado y definir `.env.example` base para MySQL/es-DO/`America/Santo_Domingo`
- [x] Crear proyecto Laravel 12 + configurar MySQL + .env(.example)
- [x] Configurar Sanctum, Pest 3.8, Pint y Larastan 3.10 (herramientas frontend pendientes)
- [x] Registrar descubrimiento inicial de módulos backend mediante `ModuleServiceProvider` y manifiestos `module.json` (estructura frontend pendiente)
- [x] Sanctum, Vue 3 + TS, Tailwind (preset Kinetic Enterprise), Pinia, Router, Axios, PWA
- [x] ESLint, Prettier, Vitest y verificación Playwright inicial (Larastan/Pest/Pint ya configurados)
- [x] Pint + Larastan + Pest; ESLint + Prettier + Vitest; Playwright configurado
- [x] Estructura modular backend (`app/Core`, `app/Modules/*` + ModuleServiceProvider) y frontend
- [x] API response estándar + manejo de excepciones API + códigos de error
- [x] Auditable, soporte Money/DECIMAL y ULID público (BelongsToCompany se implementa con el contexto tenant en Fase 1)
- [ ] Seeders base (monedas, unidades, impuestos RD, document_types NCF/e-CF, permisos)
- [x] CI GitHub Actions (`.github/workflows/ci.yml`: calidad backend, frontend y E2E)

## FASE 1 — Núcleo SaaS
- [x] Empresas, sucursales, usuarios, roles, permisos (tablas + CRUD + policies)
- [x] Login / logout / me; selección empresa y sucursal; middleware company/branch
- [x] Tests de aislamiento de tenant
- [x] Auditoría base (audit_logs + trait + pantalla mínima)

## FASE 2 — Módulos y onboarding
- [x] business_types, system_modules, dependencies, presets por tipo, company/branch_modules, planes + suscripciones (tablas + catálogo en código + seeder)
- [x] ModuleManagerService + middleware `module:` + cache por compañía (dependencias, plan, aislamiento tenant probados)
- [x] useModuleStore + guard `requiresModule` + redirección a onboarding + menú dinámico
- [x] Onboarding (tipo de negocio + activación de módulos con preset); verificado en navegador real con Playwright
- [x] Presets operativos por giro al completar onboarding (propina, inventario, comprobante e impresión), probados
- [x] UI guiada para completar datos fiscales, moneda, impuestos y caja dentro del onboarding

## FASE 3 — Configuración
- [x] Monedas (catálogo global DOP/USD/EUR + monedas por compañía + tabla de tasas) e impuestos (ITBIS 18/16/exento, propina 10%) y métodos de pago provisionados por compañía al crearla
- [x] Tipos de comprobante DGII (B01/B02/B03/B04/B14/B15 + E31/E32/E33/E34/E44/E45) y secuencias NCF/e-NCF con `NcfSequenceService::reserve()` (lockForUpdate, vencimiento, agotamiento, alertas) — probado
- [x] Códigos fiscales canónicos y NCF completo: emisión POS, impresión y migración de registros históricos normalizados a `B01/B02/...`.
- [x] API de configuración fiscal (`/settings/fiscal`, `/taxes`, `/payment-methods`, `/ncf-sequences`) + pantalla de Configuración; verificado en navegador
- [x] Tasas de cambio editables con registro histórico (API `/exchange-rates` + tabla y alta en UI); verificado en navegador
- [x] Configuración por grupos POS/inventario/facturación/impresión/seguridad/backup (tabla `settings` clave-valor, `SettingsSchema` con tipos y defaults, `SettingsService`, API `GET/PUT /settings/{group}`, tarjetas de preferencias en UI renderizadas desde el esquema); persistencia verificada en navegador

## FASE 4 — Clientes
- [x] CRUD de clientes (persona/empresa/genérico) con datos fiscales, teléfono/WhatsApp/email, límite y días de crédito
- [x] Validación de RNC (módulo 11) y cédula (Luhn) con dígito verificador (`DominicanTaxId` + regla `ValidTaxId`)
- [x] Cliente genérico "Consumidor Final" provisionado al crear la compañía
- [x] Crédito: cargos/abonos atómicos con `lockForUpdate`, control de límite y balance (`CustomerCreditService`)
- [x] API (`/customers` con búsqueda y paginación, `/customers/{id}`, `/customers/{id}/credit`) + Policy + permisos `customers.view/manage/credit`
- [x] Frontend: directorio con búsqueda y alta/edición; verificado en navegador (validación RNC, genérico, búsqueda)
- [ ] Pendiente: direcciones y contactos múltiples desde UI; pantalla de perfil con historial de facturas (tras Fase 9)

## FASE 5 — Productos y servicios
- [x] Categorías, productos, variantes, modificadores, combos, imágenes, unidades, servicios
- [x] product_inventory_settings

## FASE 6 — Inventario avanzado
- [x] Almacenes, ubicaciones, proveedores, compras (entrada con lote/vencimiento)
- [x] Lotes, movimientos, ajustes, transferencias, merma, kardex
- [x] FEFO / FIFO / costo promedio; bloqueo/advertencia vencidos; alertas
- [x] Reportes de inventario

## FASE 7 — POS Touch
- [x] Órdenes + carrito persistente (IndexedDB) + validaciones stock/lote/vencimiento/caja
- [x] Totales DECIMAL, descuentos, impuestos, propina; cotización; idempotencia

## FASE 8 — Caja y pagos
- [x] Apertura/fondo/movimientos/cierre/arqueo/diferencia/historial
- [x] Efectivo, tarjeta, transferencia, crédito, mixto, multimoneda (tasa), devuelta automática

## FASE 9 — Facturación
- [x] Factura estándar/fiscal/consumidor final/crédito fiscal, cotización, recibo, precuenta
- [x] Notas de crédito/débito con afectación de NCF e inventario
- [x] PDF + ticket; anulación con permiso y reverso; reimpresión auditada

## FASE 10 — Facturación electrónica
- [x] Interface + Null/Mock providers, settings cifrados, tablas, evento de emisión, estados, reintento, colas asíncronas de reintento con backoff exponencial, contingencia e-CF y pantalla de control e-CF y onboarding fiscal completados y validados.


## FASE 11 — Impresión
- [x] Plantillas 58/80/88mm + A4 + comanda + precuenta + cierre; TicketBuilder ESC/POS-ready; config por terminal

## FASE 12 — Módulos por negocio
- [x] Restaurante: mesas/áreas, KDS, comandas, delivery, menú digital
- [ ] Barbería: citas, agenda, empleados, comisiones
- [ ] Taller: vehículos, órdenes de trabajo, cotizaciones, fotos

## FASE 13 — Reportes
- [x] Ventas y caja por período exportable CSV; desgloses por producto/categoría/método de pago/cajero/cliente/impuestos/descuentos; reporte de anulaciones (`/reports/annulments`) con resumen
- [x] **Formatos DGII:** 606, 607 y 608 TXT implementados y cubiertos en casos base; falta confirmar tratamiento de e-NCF y resumen B02 en OFV (pendiente contra doc oficial)
- [x] Pantalla de Reportes: 7 pestañas (ventas/producto/categoría/método/impuestos/caja/anulaciones), filtros por fecha, KPIs, descarga CSV + DGII 606/607/608 e impresión (`print:hidden`); verificado en navegador con datos reales
- [ ] Export a Excel nativo (por ahora CSV, abrible en Excel) y PDF con plantilla — pendiente post-v1

## FASE 14 — Seguridad, pruebas y deploy
- [x] 2FA TOTP (RFC 6238 sin dependencias, `TotpService`): enable/confirm/disable + reto en login (`TWO_FACTOR_REQUIRED`/`INVALID`), secreto cifrado y auditado; probado (Pest) y verificado en servidor en vivo
- [x] Rate limiting en `/auth/login` y `/auth/register`; Policies por recurso ya presentes; auditoría de eventos sensibles (incl. 2FA)
- [x] `.env.example` completo (locale es-DO, colas en `database`) + guía de despliegue de producción (`docs/10`: Nginx/PHP-FPM/Redis, worker de colas para e-CF, backups, checklist)
- [x] Suite **Playwright E2E** dedicada (runner + CI): 16 escenarios sobre la app real cubren todos los flujos críticos transversales — acceso/2FA, navegación y gates de módulo, POS fiscal (caja→venta→efectivo/cambio→B02→ticket→inventario), pago mixto tarjeta DOP + efectivo USD, cierre de caja/arqueo, activación/desactivación de módulos con protección del núcleo y bloqueo de venta sin stock. Los flujos por-vertical restantes (restaurante, compras/FEFO, vencimientos, e-CF Mock) quedan en backlog: hoy cubiertos por pruebas backend (Pest) + verificación manual con Playwright MCP; se automatizarán al madurar cada vertical (varios son post-v1).
- [x] UI de 2FA en `/seguridad`: asistente activar (clave manual agrupada + enlace `otpauth://`) → confirmar con código → desactivar con código; campo de código 2FA en el login que aparece ante `TWO_FACTOR_REQUIRED`. Verificado en navegador (activar → reto en login → acceso con código válido). (QR gráfico opcional a futuro; hoy clave manual, soportada por toda app autenticadora)
- [x] CI GitHub Actions: Pint, Larastan, Pest, typecheck, ESLint, Prettier, Vitest, build y Playwright Chromium con artefactos de fallo.

## Backlog (post-v1)
- [ ] Cuentas por cobrar/pagar completas, fidelización, reservas, garantías, gastos, notificaciones WhatsApp, panel super-admin SaaS, provider e-CF real (DGII directo o PSFE), agente local de impresión, app Android Kotlin
