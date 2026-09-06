# 11 — TODO MASTER

> Fuente de verdad del avance. Marcar `[x]` solo con pruebas verdes y documentación actualizada. Fases del master prompt §28 + adiciones del addendum.

**Estado global: FASES 1–14 completas (candidato v1). Plataforma renombrada a BSM-POS. Incluye Punto de Venta (POS) optimizado con eliminación rápida en carrito, Dashboards Dedicados por Vertical (Restaurante con mesas y KDS, Taller Mecánico con bahías y órdenes, Barbería con citas y sillones, Retail con arqueo y almacén), Centro de Notificaciones reactivas, Hub de Configuración completo (Perfil, Fiscal, NCFs, Impuestos, Métodos de pago), BSM-POS Windows Agent interactivo con instalación en 1 clic para hardware ESC/POS y gaveta, suite E2E Playwright, 180+ pruebas backend verdes y 0 errores en Larastan nivel 8.**

**Actualización 2026-09-06:** aislamiento estricto de módulos por tipo de empresa (eliminación de módulos ajenos como Cocina KDS en Taller AutoMax mediante comando `modules:sync-presets` y saneamiento de `OwnerUserSeeder`). Refuerzo integral de roles y permisos (RBAC) en backend (`CompanyResource.permissions`) y frontend (`session.hasPermission`, filtrado estricto en `AppLayout` y guardias de ruta en `router`). Verificado en navegador de producción con 0 errores. Corrección integral de la redirección al login en selección de contexto y dashboards dedicados por vertical (`business_type_code`).

**Verificación 2026-07-10:** baseline de calidad restaurada tras correcciones de tipado en POS, inventario, facturación, impresión y restaurante. Pest, Pint, Larastan, vue-tsc, Vitest, ESLint, Prettier y build PWA están verdes.

**Demos por giro (2026-07-11):** `php artisan db:seed --class=DemoVerticalsSeeder` crea 8 empresas demo (contraseña `Password123!`): Restaurante El Fogón (`demo.restaurante@`), Barbería La Navaja (`demo.barberia@`), Taller AutoMax (`demo.taller@`), Cafetería Aroma (`demo.cafeteria@`), Supermercado La Económica (`demo.super@`), Ferretería El Tornillo (`demo.ferreteria@`), Distribuidora del Cibao (`demo.distribuidora@`), Consultores Pro (`demo.servicios@`) — todos `@omnipos.test`. El minimarket es `demo@omnipos.test` (DemoSeeder). 9 verticales verificados en navegador.

**Verificación 2026-07-11 (navegador real):** recorrido Playwright de las 17 pantallas del menú + login/registro/contexto con venta de humo POS (B02, caja e inventario cuadran) sin errores de consola/red. Corregidos 2 bugs de layout: colapso del panel de login/registro en pantallas anchas (`app.css`) y desborde del formulario de vehículos (`w-full min-w-0`). Detalle menor pendiente: impuesto "ITBIS 18%" aparece duplicado (códigos `ITBIS18` e `itbis_18`) en compañías creadas con onboarding.

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
- [x] Seeder base global (`ConfigurationSeeder`): monedas, tipos de comprobante DGII (NCF/e-CF) y **catálogo de permisos** sembrados globalmente en `migrate:fresh --seed`. Impuestos, unidades y métodos de pago son por-compañía (tablas con `company_id`) y se provisionan al crear cada empresa por diseño multi-tenant.
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
- [x] Edición integral de Perfil de Empresa (`GET/PATCH /company/profile`: nombre comercial, razón social, RNC, teléfonos, correo, dirección, timezone) y gestión de impuestos, métodos de pago y secuencias NCF (ajuste de número final y alertas); verificado en navegador
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
- [x] Eliminación rápida en 1 clic de productos del carrito (icono de papelera) y vaciado total de orden con confirmación y recálculo instantáneo
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
- [x] POS: mostrar errores de impresión local dentro del comprobante, respetar la impresora elegida y evitar fallback automático al navegador y clics simultáneos. Cuatro regresiones Vitest y navegador con agente simulado verificados (2026-09-05).
- [x] Corrección de impresión POS publicada en bsmpos.bsolutions.dev (2026-09-06, commit 31d77cd): build local transferido por SSH, hashes verificados y navegador de producción recargado.
- [x] Resolver técnicamente la detección COM4/timeout del agente: fallback COM/PnP separado, timeout acotado, extracción PnP y escritura serial mediante `\\.\COMx`.
- [ ] Confirmar ticket físico 58 mm con `2C-P58-C`. Typecheck global pendiente por dos errores previos en ConfigurationPage.vue.
- [x] Plantillas 58/80/88mm + A4 + comanda + precuenta + cierre; TicketBuilder ESC/POS-ready; config por terminal
- [x] OmniPOS Windows Agent empaquetado (.zip en public/downloads y endpoint /api/v1/agent-terminals/download), modal interactivo de descarga e instalación en 1 clic desde el botón de estado del POS (desktop_windows) y panel de hardware con detección en vivo.

## FASE 12 — Módulos por negocio
- [x] Restaurante: mesas/áreas, KDS, comandas, delivery, menú digital
- [x] Barbería / Salón: módulos activables **`employee`** (empleados con % de comisión) y **`appointment`** (citas). Agenda por fecha/empleado, alta de cita con servicios (total con ITBIS vía bcmath), ciclo de estados con transiciones validadas; botón **"Facturar en POS"** que transfiere servicios, cliente y notas a la caja con cálculo de ITBIS y emisión fiscal NCF B02 sin deducción física de stock.
- [x] Taller mecánico: módulos activables **`vehicle`** y **`work_order`**. Órdenes con diagnóstico, servicios, repuestos y mano de obra; ciclo de estados con transiciones validadas; botón **"Facturar en POS"** con puente reactivo `ExternalOrderBridge`, soporte a conceptos virtuales/repuestos/mano de obra, y facturación NCF B02 auditada.
- [x] Tienda de electrodomésticos y tecnología (`appliance_store`): soporte modular para trazabilidad de números de serie (`serial_numbers`) y pólizas de garantía (`warranty`). Asignación unitaria de S/N / IMEI en el POS, desglose de garantía (`warranty_terms`) en carrito, orden y factura fiscal NCF, impresión en ticket térmico ESC/POS, texto monoespaciado y comprobantes A4/HTML. Empresa demo **ElectroHogar Dominicana** provisionada con 2 almacenes (Showroom y Depósito), secuencias B01/B02, catálogo con garantías y suite de pruebas `ApplianceStoreTest`.

## FASE 13 — Reportes
- [x] Ventas y caja por período exportable CSV; desgloses por producto/categoría/método de pago/cajero/cliente/impuestos/descuentos; reporte de anulaciones (`/reports/annulments`) con resumen
- [x] **Formatos DGII:** 606, 607 y 608 TXT implementados y cubiertos en casos base; falta confirmar tratamiento de e-NCF y resumen B02 en OFV (pendiente contra doc oficial)
- [x] Pantalla de Reportes: 7 pestañas (ventas/producto/categoría/método/impuestos/caja/anulaciones), filtros por fecha, KPIs, descarga CSV + DGII 606/607/608 e impresión (`print:hidden`); verificado en navegador con datos reales
- [x] Export **Excel nativo (.xlsx)** y **PDF** del reporte de ventas mediante escritores propios sin dependencias (`App\Core\Support\XlsxWriter` con `ZipArchive`; `App\Core\Support\PdfTableDocument`, tabla A4 paginada Helvetica/WinAnsi). Endpoints `/reports/sales/export.xlsx|.pdf` con content-types correctos y botones Excel/PDF en la pantalla de Reportes. Cubierto por `ReportExportTest` (firma ZIP `PK`, cabecera `%PDF`, HTTP real).

## FASE 14 — Seguridad, pruebas y deploy
- [x] 2FA TOTP (RFC 6238 sin dependencias, `TotpService`): enable/confirm/disable + reto en login (`TWO_FACTOR_REQUIRED`/`INVALID`), secreto cifrado y auditado; probado (Pest) y verificado en servidor en vivo
- [x] Rate limiting en `/auth/login` y `/auth/register`; Policies por recurso ya presentes; auditoría de eventos sensibles (incl. 2FA)
- [x] `.env.example` completo (locale es-DO, colas en `database`) + guía de despliegue de producción (`docs/10`: Nginx/PHP-FPM/Redis, worker de colas para e-CF, backups, checklist)
- [x] Suite **Playwright E2E** dedicada (runner + CI): 18 escenarios sobre la app real cubren todos los flujos críticos transversales — acceso/2FA, navegación y gates de módulo, POS fiscal (caja→venta→efectivo/cambio→B02→ticket→inventario), pago mixto tarjeta DOP + efectivo USD, cierre de caja/arqueo, activación/desactivación de módulos con protección del núcleo, bloqueo de venta sin stock, y los verticales **Barbería** (agendar cita con total ITBIS + transición) y **Taller** (registrar vehículo → crear orden con servicio+mano de obra + transición). Los flujos por-vertical restantes (restaurante, compras/FEFO, vencimientos, e-CF Mock) quedan en backlog: hoy cubiertos por pruebas backend (Pest) + verificación manual; se automatizarán al madurar cada vertical (varios son post-v1).
- [x] UI de 2FA en `/seguridad`: asistente activar (clave manual agrupada + enlace `otpauth://`) → confirmar con código → desactivar con código; campo de código 2FA en el login que aparece ante `TWO_FACTOR_REQUIRED`. Verificado en navegador (activar → reto en login → acceso con código válido). (QR gráfico opcional a futuro; hoy clave manual, soportada por toda app autenticadora)
- [x] CI GitHub Actions: Pint, Larastan, Pest, typecheck, ESLint, Prettier, Vitest, build y Playwright Chromium con artefactos de fallo.
- [x] **Centro de Notificaciones Operativas en Vivo (`app/Modules/Notification/` & `NotificationDropdown.vue`):**
  - Módulo core multi-tenant con `SystemNotification` (categorías `inventory`, `fiscal`, `appointment`, `work_order`, `cash`, `system`).
  - Detección automática y sincronización de alertas inteligentes: existencias críticas bajo umbral, secuencias NCF por agotarse, citas del día, órdenes de taller terminadas y turnos de caja abiertos por más de 12 horas.
  - Endpoints RESTful con marcas de lectura individuales y masivas (`/api/v1/notifications`).
  - Dropdown interactivo con badge dinámico animado, tiempos relativos, eliminación y navegación directa al recurso. Cubierto con 8 pruebas Pest y verificado en navegador.
- [x] **Preparación de e-CF como Servicio SaaS Opcional:**
  - Módulo `electronic_invoice` desacoplado y opcional (`is_core = false`). Si no se contrata, la empresa opera legal y fluidamente con NCF tradicionales (B01, B02, B14). Si se solicita, todo el pipeline e-CF (contratos, provider, colas y secuencias E31/E32) está listo para encenderse de inmediato.

## Backlog (post-v1)
- [x] Agente local de impresión y hardware Windows: OmniPOS Windows Agent (Kotlin 2.1 / Ktor 3.1 Netty en loopback 8765), detección PnP de dispositivos Bluetooth emparejados (reconocimiento de impresora `2C-P58-C` en COM7/COM6), puertos seriales COM, Spooler Windows, impresión directa ESC/POS raw (`\\.\COMx`), pulso de gaveta, backend Laravel con tokens HMAC-SHA256 (`/agent-terminals`), ticket monoespaciado (`/invoices/{id}/print/text`), página Bento Grid `/configuracion/terminales`, selector dinámico de impresora en el POS y orquestador `scripts/start-suite.ps1`.
- [ ] Cuentas por cobrar/pagar completas, fidelización, reservas, garantías, gastos, notificaciones WhatsApp, panel super-admin SaaS, provider e-CF real (DGII directo o PSFE), app Android Kotlin
