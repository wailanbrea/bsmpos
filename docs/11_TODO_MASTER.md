# 11 — TODO MASTER

> Fuente de verdad del avance. Marcar `[x]` solo con pruebas verdes y documentación actualizada. Fases del master prompt §28 + adiciones del addendum.

**Estado global: FASES 1–4 completas (Fase 3 cerrada). Siguiente: FASE 5 (productos y servicios).**

## Conocimiento del proyecto
- [x] Fase 1 completada: compañías, sucursal principal, membresías, contexto tenant, RBAC, sesiones API, Policies y auditoría base.
- [x] Tests de aislamiento tenant: listado filtrado y denegación de empresa/sucursal ajena.
- [x] RBAC inicial: catálogo de permisos, roles por compañía, rol propietario y middleware `permission:<código>`.
- [x] Sesiones API: registro, login, logout y `me` con Sanctum, rate limits, cuentas inactivas y auditoría.
- [x] UI de acceso y contexto: login, registro, creación inicial de compañía y selección persistente de compañía/sucursal.
- [x] Auditoría mínima: consulta por tenant con permiso, filtros, valores sensibles redactados y pantalla de bitácora.
- [x] Gestión de sucursales: listado, creación, actualización, asignación del administrador y protección de sucursal principal.
- [x] Gestión de acceso de usuarios existentes: membresías, sucursales, roles y sucursal predeterminada aislados por compañía.
- [x] CRUD seguro de roles personalizados y catálogo de permisos por código; roles del sistema y asignados protegidos.
- [x] UI inicial de roles y permisos conectada al API, con estados operativos y protección visual de roles del sistema.
- [x] UI de usuarios por compañía: asignación de cuentas existentes a sucursales y roles, sucursal predeterminada y protección visual del propietario.
- [x] UI de sucursales: listado, creación y actualización con estado activo y protección visual de la sucursal principal.
- [x] Grafo Graphify actualizado con el código de Fases 2, 3, 3-cierre y 4 mediante extracción AST incremental (1492 nodos, 2418 relaciones). La capa semántica de docs/imágenes no se re-extrajo estas sesiones (se conservan sus nodos previos); refrescar con un `/graphify` completo cuando convenga. El extractor aún omite manifiestos `module.json` sin símbolos AST.

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
- [ ] CI GitHub Actions (si hay repo git — **crear repo git**)

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
- [ ] Pendiente Fase 3: pasos de datos fiscales y configuración inicial del onboarding (moneda, impuestos, caja)

## FASE 3 — Configuración
- [x] Monedas (catálogo global DOP/USD/EUR + monedas por compañía + tabla de tasas) e impuestos (ITBIS 18/16/exento, propina 10%) y métodos de pago provisionados por compañía al crearla
- [x] Tipos de comprobante DGII (B01/B02/B03/B04/B14/B15 + E31/E32/E33/E34/E44/E45) y secuencias NCF/e-NCF con `NcfSequenceService::reserve()` (lockForUpdate, vencimiento, agotamiento, alertas) — probado
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
- [ ] Categorías, productos, variantes, modificadores, combos, imágenes, unidades, servicios
- [ ] product_inventory_settings

## FASE 6 — Inventario avanzado
- [ ] Almacenes, ubicaciones, proveedores, compras (entrada con lote/vencimiento)
- [ ] Lotes, movimientos, ajustes, transferencias, merma, kardex
- [ ] FEFO / FIFO / costo promedio; bloqueo/advertencia vencidos; alertas
- [ ] Reportes de inventario

## FASE 7 — POS Touch
- [ ] Órdenes + carrito persistente (IndexedDB) + validaciones stock/lote/vencimiento/caja
- [ ] Totales DECIMAL, descuentos, impuestos, propina; cotización; idempotencia

## FASE 8 — Caja y pagos
- [ ] Apertura/fondo/movimientos/cierre/arqueo/diferencia/historial
- [ ] Efectivo, tarjeta, transferencia, crédito, mixto, multimoneda (tasa), devuelta automática

## FASE 9 — Facturación
- [ ] Factura estándar/fiscal/consumidor final/crédito fiscal, cotización, recibo, precuenta
- [ ] Notas de crédito/débito con afectación de NCF e inventario
- [ ] PDF + ticket; anulación con permiso y reverso; reimpresión auditada

## FASE 10 — Facturación electrónica
- [ ] Interface + Null/Mock providers + settings cifrados + tablas + jobs + estados + reintentos + contingencia + pantallas

## FASE 11 — Impresión
- [ ] Plantillas 58/80/88mm + A4 + comanda + precuenta + cierre; TicketBuilder ESC/POS-ready; config por terminal

## FASE 12 — Módulos por negocio
- [ ] Restaurante: mesas/áreas, KDS, comandas, delivery, menú digital
- [ ] Barbería: citas, agenda, empleados, comisiones
- [ ] Taller: vehículos, órdenes de trabajo, cotizaciones, fotos

## FASE 13 — Reportes
- [ ] Ventas (fecha/producto/categoría/cajero/método/cliente), caja, facturas, e-CF, impuestos, descuentos, anulaciones
- [ ] **Formatos DGII 606 (compras) / 607 (ventas) / 608 (anulados)** exportables
- [ ] Export PDF/Excel + imprimir en todos

## FASE 14 — Seguridad, pruebas y deploy
- [ ] Policies completas, rate limiting, 2FA, auditoría completa
- [ ] Suite Pest completa + Vitest + **Playwright E2E (10 flujos de 09_TESTING.md)**
- [ ] .env.example, guía deploy, optimización producción

## Backlog (post-v1)
- [ ] Cuentas por cobrar/pagar completas, fidelización, reservas, garantías, gastos, notificaciones WhatsApp, panel super-admin SaaS, provider e-CF real (DGII directo o PSFE), agente local de impresión, app Android Kotlin
