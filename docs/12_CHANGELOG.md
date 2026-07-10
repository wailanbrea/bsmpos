# 12 — CHANGELOG

Formato: [Keep a Changelog](https://keepachangelog.com/es/) adaptado. Cada entrada indica archivos relevantes y fase.

## [No publicado]

### 2026-07-10 — Fase 4: módulo de clientes
**Agregado**
- Tablas `customers`, `customer_addresses`, `customer_contacts`, `customer_credit_accounts`.
- Módulo `Customer`: modelo con crédito/balance, `CustomerCreditService` (cargos/abonos atómicos con `lockForUpdate`, control de límite), acciones de alta/edición, `ProvisionGenericCustomer` (Consumidor Final por compañía).
- Validación fiscal dominicana: `DominicanTaxId` (RNC módulo 11, cédula Luhn) + regla `ValidTaxId`.
- API `/customers` (búsqueda, paginación), `/customers/{id}`, `/customers/{id}/credit`; Policy y permisos `customers.view/manage/credit`.
- Frontend: directorio de clientes con búsqueda en vivo y formulario de alta/edición, enlazado al menú (módulo `customer`).

**Validado**
- Pest: 7 pruebas nuevas (dígitos verificadores RNC/cédula, genérico por compañía, alta con RNC válido, rechazo de RNC inválido, crédito con límite, aislamiento tenant, permisos). Suite total 65 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build sin errores.
- Navegador real (Playwright): consumidor final presente, RNC inválido rechazado con mensaje, alta de "Distribuidora XYZ SRL (RNC 131793916)", búsqueda filtrando.

### 2026-07-10 — Fase 3: base de configuración fiscal (RD)
**Agregado**
- Tablas: `currencies`, `company_currencies`, `exchange_rates`, `taxes`, `payment_methods`, `document_types`, `ncf_sequences`.
- `FiscalCatalog` (monedas DOP/USD/EUR, tipos de comprobante NCF/e-CF, impuestos y métodos de pago por defecto) + `ConfigurationSeeder` (catálogos globales).
- `ProvisionCompanyConfiguration`: al crear una compañía se provisionan moneda base, ITBIS 18/16/exento, propina legal 10% y métodos de pago (efectivo/tarjeta/transferencia/crédito).
- `NcfSequenceService::reserve()`: reserva atómica de NCF/e-NCF con `lockForUpdate()`, formato de 8 (NCF) o 10 dígitos (e-NCF), control de vencimiento, agotamiento y alertas.
- API bajo `/api/v1`: `GET /settings/fiscal`, `POST /taxes`, `PATCH /taxes/{id}`, `POST /payment-methods`, `GET/POST /ncf-sequences` (permisos `settings.view`/`settings.manage`).
- Frontend: pantalla de Configuración fiscal (impuestos, métodos de pago, secuencias NCF con alta) enlazada al menú dinámico (módulo `setting`).

**Validado**
- Pest: 12 pruebas nuevas (NCF: consecutivos únicos, e-NCF 10 dígitos, agotamiento, vencimiento, inexistente, aislamiento, alertas; provisión por compañía; API de configuración y permisos). Suite total 58 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build sin errores.
- Navegador real (Playwright): pantalla de Configuración renderiza, selector con los 12 tipos DGII, alta de secuencia B02 (1–1000) visible en tabla, consola sin errores.

### 2026-07-10 — Fase 2: sistema de módulos y onboarding
**Agregado**
- Tablas del sistema de módulos: `business_types`, `system_modules`, `module_dependencies`, `business_type_modules`, `subscription_plans`, `plan_modules`, `company_subscriptions`, `company_modules`, `branch_modules`, `module_audit_logs`.
- `ModuleCatalog` como fuente de verdad (40 módulos, 14 tipos de negocio, 24 dependencias, presets §9) proyectada por `ModuleSystemSeeder`; 3 planes con límites.
- `ModuleManagerService`: núcleo siempre activo, validación de dependencias y plan, aplicación de presets ordenada por dependencias, auditoría de cambios y caché por compañía.
- Middleware `module:<código>` (403 `MODULE_DISABLED`) registrado como alias.
- API bajo `/api/v1`: `GET /modules`, `POST /modules/{code}/enable|disable`, `GET /business-types`, `POST /onboarding`, protegidas por `modules.view`/`modules.manage`.
- Frontend: `useModuleStore`, guard `requiresModule` con redirección a onboarding, menú dinámico por módulos activos, pantalla de gestión de módulos y wizard de onboarding (tipo de negocio → módulos).

**Validado**
- Pest: 13 pruebas nuevas (servicio + API): núcleo, dependencias, presets, desactivación con dependientes, aislamiento tenant, permisos. Suite total 46 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.
- Navegador real (Playwright): registro → compañía → onboarding con preset de colmado → menú dinámico (POS/Inventario/Clientes) → bloqueo de desactivación de Inventario por dependiente activo.

### 2026-07-09 — Fase 1: Policies de recursos SaaS
**Agregado**
- Policies explícitas para compañía, sucursal, rol y bitácora; los controladores las aplican además de los middleware de ruta.
- Método único de autorización por compañía para membresía, propietario y permisos, reutilizado por middleware y Policies.

**Corregido**
- La consulta de roles durante una decisión de Policy deja de depender del global scope del contexto y se limita explícitamente al tenant del recurso.

**Validado**
- Pest cubre permisos por recurso, denegación entre compañías y cuenta inactiva; la suite completa de calidad se ejecuta al cierre.

### 2026-07-09 — Fase 1: interfaz de sucursales
**Agregado**
- Ruta `/sucursales` para listar, crear y actualizar puntos de operación de la empresa activa.
- Formulario con nombre, código, teléfono, dirección y estado; la sucursal principal se identifica y no puede desactivarse desde la UI.

**Validado**
- Vitest cubre la protección visual de la sucursal principal. Navegador real verificó alta de sucursal secundaria y la protección de la principal sin errores de consola.
- Typecheck, ESLint, Prettier y build se ejecutaron al cierre del cambio.

### 2026-07-09 — Fase 1: interfaz de usuarios por compañía
**Agregado**
- Ruta `/usuarios` para provisionar una cuenta existente o modificar su acceso en la empresa activa.
- Selección de sucursales activas, sucursal predeterminada y roles; no permite enviar una asignación sin sucursal.
- El propietario se distingue y queda deshabilitado en la UI, consistente con el bloqueo del API.
- Los roles del sistema se excluyen de la UI y el servicio los rechaza para impedir escalamiento de privilegios.

**Validado**
- Vitest cubre la carga de datos y la validación de sucursal; Pest cubre el rechazo de roles del sistema.
- Navegador real verificó la ruta con sesión y contexto tenant, incluida la exclusión del rol propietario. Typecheck, ESLint, Prettier y build se ejecutaron al cierre del cambio.

### 2026-07-09 — Fase 1: interfaz de roles y permisos
**Agregado**
- Ruta `/roles` con listado, editor de permisos por código, creación, edición y desactivación de roles personalizados.
- Estados de carga/error y controles deshabilitados para roles del sistema, consistentes con Kinetic Enterprise.

### 2026-07-09 — Fase 1: CRUD seguro de roles
**Agregado**
- Catálogo API de permisos por código y actualización/desactivación lógica de roles personalizados.
- El contrato de roles usa `permission_codes`, sin exponer IDs internos; se impide alterar roles del sistema o desactivar roles asignados.

**Validado**
- Pest cubre permisos públicos, actualización, auditoría, propietario del sistema y desactivación segura.

### 2026-07-09 — Fase 1: acceso de usuarios por compañía
**Agregado**
- Listado y provisión de cuentas ya registradas, con sincronización de sucursales, roles y sucursal predeterminada por compañía.
- Protección contra referencias de otro tenant, preservación de accesos externos, bloqueo de edición del propietario y auditoría de cambios.

**Validado**
- Pest cubre provisión, aislamiento multicompañía, datos ajenos y regla de propietario.

### 2026-07-09 — Fase 1: administración de sucursales
**Agregado**
- Endpoints de listado, creación y actualización de sucursales bajo compañía activa y permiso `company.manage`.
- Validación de código único por compañía, auditoría de altas/cambios, asignación automática del administrador y bloqueo de desactivación de la sucursal principal.

**Validado**
- Pest cubre aislamiento de tenant, permisos, auditoría, actualización y la regla de sucursal principal.

### 2026-07-09 — Fase 1: bitácora de auditoría consultable
**Agregado**
- Módulo `Audit` con endpoints paginados de listado y detalle, protegidos por compañía y `audit.view`.
- ULID público e índices de consulta para `audit_logs`; los valores potencialmente secretos se redactan antes de exponerlos.
- Pantalla `/auditoria` con filtros de módulo/fecha, estados de carga, vacío y error, paginación y detalle del evento.

**Validado**
- Pest cubre aislamiento de tenant, autorización heredada de `audit.view`, redacción y filtros. La verificación completa de calidad y navegador se registra al cierre de este cambio.

### 2026-07-09 — Corrección de navegación SPA
**Corregido**
- Las rutas directas del cliente (por ejemplo `/ingresar`) entregan el shell Vue en lugar de responder 404.
- El fallback de la SPA excluye `/api/*`, por lo que un endpoint API inexistente ya no devuelve HTML con HTTP 200 ni oculta errores de autorización.

**Validado**
- Pest cubre la carga directa de ruta SPA.

### 2026-07-09 — Fase 1: acceso y contexto operativo
**Agregado**
- UI Vue para login, registro, creación inicial de compañía y pase de turno compañía/sucursal.
- Store Pinia persistente y cliente API que envía token Sanctum y cabeceras tenant por solicitud.

**Validado**
- Playwright verificó las pantallas de acceso y registro en navegador real.
- Pest 19/63, Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Fase 1: autenticación API segura
**Agregado**
- Módulo `Auth` con registro, login, logout y perfil actual bajo `/api/v1/auth`.
- Tokens Sanctum por dispositivo, revocación del token actual, rate limits, reglas de contraseña y bloqueo de cuentas inactivas.
- Auditoría de registro, login, fallo de login, bloqueo y logout.

**Validado**
- Pest: 19 pruebas / 63 aserciones; Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Fase 1: RBAC por compañía
**Agregado**
- Tablas `permissions`, `roles`, `permission_role` y `role_user`, con restricciones únicas e índices de consulta por compañía.
- Catálogo mínimo de permisos, rol de propietario aprovisionado al crear una compañía y middleware `permission:<código>`.
- API de roles protegida: `GET/POST /api/v1/roles`.

**Validado**
- Pest: 15 pruebas / 43 aserciones, incluyendo permiso otorgado, denegación y alta de rol.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Fase 1: aislamiento multiempresa base
**Agregado**
- Módulo `Company` con compañías, sucursales, membresías y ULID público para selección segura.
- Acción transaccional que crea compañía, sucursal principal, membresías de propietario y auditoría.
- `GET/POST /api/v1/companies`, Form Request, Resources, `CurrentCompany` y middleware `company`/`branch`.

**Validado**
- Migraciones aplicadas a la base exclusiva `omnipos`.
- Pest: 12 pruebas / 33 aserciones; Pint, Larastan nivel 6, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Inicio de Fase 0: control de versiones
**Agregado**
- `.gitignore` inicial para dependencias, secretos, artefactos de Laravel y estado local de herramientas.
- `.gitattributes` para finales de línea consistentes y archivos PNG binarios.
- Normalización de espacios finales en las maquetas de referencia, sin cambios funcionales.
- Repositorio Git local en rama `main`, vinculado al remoto autorizado `wailanbrea/sistemawebPosSaas`.

### 2026-07-09 — Inicio de Fase 0: runtime y Laravel
**Agregado**
- PHP 8.3.32 portable en `.tools/php83`, aislado de XAMPP y del `PATH` global.
- Laravel 12.63.0 con dependencias bloqueadas en `composer.lock`.
- Configuración base para MySQL, locale `es_DO` y zona horaria `America/Santo_Domingo` en `.env.example`.
- Base local `omnipos` creada con `utf8mb4` y migraciones estándar de Laravel aplicadas.
- Grafo Graphify actualizado con el código Laravel y reemplazado en el grafo global existente (628 nodos, 617 relaciones).
- Sanctum para tokens API, su migración de tokens personales y ruta API protegida de referencia.
- Pest 3.8, Larastan 3.10, configuración `phpstan.neon` nivel 6 y descubrimiento de módulos backend por manifiesto.
- Vue 3 + TypeScript, Vue Router, Pinia, Axios, Vue I18n, Tailwind/Kinetic Enterprise y PWA con actualización automática.
- Shell inicial de dashboard en español dominicano, con barra de estado operativo para conexión, sucursal y caja.
- Núcleo transversal `app/Core`: envoltorios API estables, `ErrorCode`, `ApiException`, dinero mediante `brick/money`, ULID público y auditoría explícita.
- Migraciones para `users.public_id` y `audit_logs`.

**Validado**
- `php artisan test`: 2 pruebas aprobadas.
- Navegador real mediante Playwright: página inicial de Laravel responde sin errores de consola.
- Pest: 3 pruebas aprobadas, incluyendo emisión de token Sanctum; Pint y Larastan sin errores.
- Playwright: dashboard Vue validado en navegador real, sin errores de consola.
- Pest: 7 pruebas aprobadas para respuestas API, errores, dinero, auditoría y Sanctum; Larastan y Pint sin errores.

**Pendiente**
- Crear y configurar la base de datos MySQL local `omnipos`; no se ha alterado ninguna base existente.

### 2026-07-09 — Planificación inicial (pre-código)
**Agregado**
- Documentación inicial completa `docs/00–15` (visión, arquitectura, esquema BD, API, frontend, módulos, e-CF, impresión, seguridad, testing, deploy, TODO, decisiones, issues, inventario).
- `MASTER_PROMPT_ADDENDUM.md` con requisitos que faltaban en `promt master.txt` (localización fiscal RD: NCF/secuencias/606/607/608/propina legal/multimoneda; e-CF Ley 32-23 con calendario y contingencia; idempotencia; concurrencia de secuencias; ULID; calidad CI/Larastan/Playwright; SaaS suscripciones/límites; design system Kinetic Enterprise; i18n es-DO).
- Análisis de las 12 pantallas de referencia Stitch (`Pantallas del sistema/`).
- Extracción del proyecto en 14 lotes de Graphify (documentación, prompts, HTML e imágenes de referencia).
- Grafo consolidado en `graphify-out/graph.json` e incorporado una sola vez al grafo global como `omnipos-modular-saas` (231 nodos, 344 relaciones; sin duplicados ni referencias colgantes).

**Pendiente**
- Continuar Fase 0 con Sanctum, Vue 3/TypeScript, herramientas de calidad y estructura modular.
