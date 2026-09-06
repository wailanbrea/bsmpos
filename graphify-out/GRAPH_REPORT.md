# Graph Report - .  (2026-09-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 3053 nodes · 6302 edges · 317 communities (189 shown, 128 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 381 edges (avg confidence: 0.81)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2f845c9d`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Eloquent\Model
- Illuminate\Http\JsonResponse
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Invoice
- User.php
- Illuminate\Http\Request
- ErrorCode.php
- PosPage.vue
- [No publicado]
- AgendaPage.vue
- User
- ConfigurationPage.vue
- Application.kt
- Inventory Batches (lotes y vencimientos)
- AgentTerminalsPage.vue
- CurrentCompany
- Illuminate\Database\Eloquent\Relations\HasMany
- InventoryPage.vue
- WorkOrderListPage.vue
- Customer
- Product.php
- Company
- api.ts
- ModuleManagerService
- AppLayout.vue
- NcfSequence
- support/session.ts
- router/index.ts
- OnboardingPage.vue
- Warehouse
- UserManagementPage.vue
- Branch
- ProductListPage.vue
- VehicleListPage.vue
- CustomerListPage.vue
- ReportsPage.vue
- SecurityPage.vue
- LocalPrinterService
- 00_PROJECT_OVERVIEW.md
- ValidTaxId.php
- Kardex Product Movement History Screen
- Role
- 10 — Despliegue
- compilerOptions
- ModuleManagementPage.vue
- Dashboard Principal Perfil Retail (OmniPOS)
- SettingsService
- Purchase
- 11 — TODO MASTER
- AuditLogPage.vue
- BranchManagementPage.vue
- Illuminate\Foundation\Http\FormRequest
- devDependencies
- RestaurantLayoutPage.vue
- Closure
- ProductController
- scripts
- TotpService
- NcfSequenceService
- dependencies
- IndexedDBService
- ElectronicInvoicePage.vue
- composer.json
- 02 — Esquema Inicial de Base de Datos
- 09 — Estrategia de Pruebas
- RoleManagementPage.vue
- products/services.ts
- PrintController
- self
- Reportes operativos (Fase 13)
- DashboardPage.vue
- ApiException
- FiscalCatalog
- scripts
- Entrada de Mercancía Screen (Compras y Lotes)
- VehicleController.php
- require-dev
- Ciclo operativo completo
- System Configuration Active Modules Screen
- Facturación Electrónica Control de e-CF Screen
- Inventario Avanzado Control de Lotes Screen
- Onboarding Module Activation Screen (Paso 3 de 5)
- POS Touch Screen - Restaurant Profile (OmniPOS)
- Procesar Pago Multimoneda y Mixto Screen
- 01 — Arquitectura
- 03 — Documentación de API
- MASTER PROMPT — ADDENDUM (v1.1)
- KitchenKdsPage.vue
- Onboarding Business Type Selection Screen
- AgentTerminalController
- ElectronicInvoiceController
- BusinessType
- AppServiceProvider
- 00 — Visión General del Proyecto: OmniPOS Modular SaaS
- 05 — Sistema de Módulos
- Ciclo operativo completo
- Ciclo operativo completo
- Ciclo operativo completo
- Ciclo operativo completo
- README.md
- pos/services.ts
- OmniPOS Modular SaaS — Instrucciones del proyecto
- UserFactory
- PdfTableDocument
- XlsxWriter
- .store
- .index
- InvoiceController
- .index
- config
- setup
- 04 — Estructura Frontend
- 15 — Inventario, Lotes y Vencimientos
- TestCase
- DESIGN.md
- products/types.ts
- Configuración fiscal (Fase 3)
- 06 — Facturación Electrónica (e-CF DGII, República Dominicana)
- Ciclo operativo completo
- Ciclo operativo
- Ciclo operativo completo
- IdempotencyService.php
- SquareImage
- module.json
- OrderController
- keywords
- require
- 2026_09_05_100000_create_agent_terminals_table.php
- Verificación en dos pasos (Fase 14)
- Guía de uso — Servicios profesionales 💼
- .of
- ProvisionCompanyUserRequest
- LoginRequest
- RegisterRequest
- StoreBranchRequest
- StoreCompanyRequest
- psr-4
- Roles y permisos (Fase 1)
- Módulos y onboarding (Fase 2)
- 07 — Impresión
- main.cjs
- Acceso al sistema (común a todos los giros)
- Guía de Uso — OmniPOS
- .prettierrc.json
- UpdateRoleRequest
- ListAuditLogsRequest
- AuditValuesSanitizer
- RecordCreditRequest
- StoreEmployeeRequest
- UpdateEmployeeRequest
- UpsertPurchaseFiscalDataRequest
- AnnulInvoiceRequest
- ToggleModuleRequest
- UpdateProductRequest.php
- ExportDgii608Request
- StoreServiceRequest
- StoreExchangeRateRequest
- StoreNcfSequenceRequest
- StorePaymentMethodRequest
- StoreTaxRequest
- UpdateTaxRequest
- StoreVehicleRequest
- UpdateVehicleRequest
- StoreWorkOrderRequest
- UpdateWorkOrderStatusRequest
- DatabaseSeeder
- Facturación electrónica (Fase 10)
- Sucursales (Fase 1)
- Usuarios por compañía (Fase 1)
- ExampleTest
- useAgentStatus.ts
- StoreAppointmentRequest
- UpdateAppointmentStatusRequest
- .credentials
- post-autoload-dump
- App.vue
- start-suite.ps1
- Pantalla: Perfil de Cliente (Historial y Crédito)
- Controller.php
- package.json
- 12_CHANGELOG.md
- @eslint/js
- eslint-plugin-vue
- @intlify/devtools-types
- jsdom
- laravel-vite-plugin
- tailwindcss
- @tailwindcss/vite
- typescript
- typescript-eslint
- vitest
- @vue/test-utils
- vue-tsc
- printWithAgent
- CLAUDE.md Project Instructions
- 00 Project Overview: OmniPOS Modular SaaS
- OmniPOS Platform
- 01 Architecture
- BelongsToCompany Global Scope
- Domain Events (OrderPaid, InvoiceIssued, ModuleToggled, ...)
- Idempotency-Key on sale/payment/invoice endpoints
- Modular Monolith Pattern
- Money as DECIMAL(14,2)
- Multi-tenancy by company_id column
- Public ULID (public_id)
- Layered Route Protection (auth/company/branch/module/permission/policy)
- Sequence generation with lockForUpdate
- 02 Database Schema
- Credit/Debit notes modeled as invoices
- dgii_reports (606/607/608)
- electronic_invoices tables (settings/logs/errors/contingencies)
- inventory_batches / inventory_movements / inventory_stock
- invoices table
- ncf_sequences table
- 03 API Documentation
- 04 Frontend Structure
- Kinetic Enterprise Design System
- PWA offline support (IndexedDB cart, cached catalog)
- useModuleStore (frontend module gating)
- 05 Module System
- Business type module presets
- EnsureModuleEnabled middleware (module:<code>)
- Module dependency map
- 06 Electronic Invoicing (e-CF DGII)
- e-CF Contingency Mode
- e-CF (Electronic Fiscal Receipt, DGII)
- e-CF Jobs (Generate/Send/CheckStatus)
- Ley 32-23 (DR Electronic Invoicing Law)
- MockElectronicInvoiceProvider
- NullElectronicInvoiceProvider
- 07 Printing
- TicketBuilder (medium-agnostic ticket layer)
- 08 Security and Audit
- Audit module (audit_logs, before/after diffs)
- Elevated authorization for sensitive actions (supervisor PIN)
- Sanctum token authentication
- 09 Testing Strategy
- Pest test framework
- Playwright E2E suite (10 critical flows)
- 10 Deployment
- 11 TODO Master (phased plan)
- 13 Technical Decisions (ADR)
- 14 Known Issues
- 15 Inventory, Batches and Expirations
- Expiration sale rules (blocked/supervisor/warned)
- FEFO outgoing method
- FIFO outgoing method
- InventoryService (transactional stock mutations)
- Kardex (product movement history)
- Modo Contingencia e-CF
- Reportes DGII 606/607/608
- Idempotency-Key en ventas
- Ley 32-23 (Facturación Electrónica RD)
- Secuencias con lockForUpdate
- Localización Fiscal República Dominicana
- SaaS Comercial (company_subscriptions)
- Prioridad de Fuentes
- Touch Targets 44/56px
- @vitejs/plugin-vue

## God Nodes (most connected - your core abstractions)
1. `CurrentCompany` - 141 edges
2. `ApiResponse` - 123 edges
3. `User` - 89 edges
4. `Company` - 73 edges
5. `[No publicado]` - 58 edges
6. `Invoice` - 49 edges
7. `CreateCompanyAction` - 39 edges
8. `Customer` - 36 edges
9. `Branch` - 36 edges
10. `Product` - 32 edges

## Surprising Connections (you probably didn't know these)
- `Inventory Batches (lotes y vencimientos)` --references--> `Pantalla: Dashboard Principal (Retail)`  [INFERRED]
  promt master.txt → Pantallas del sistema/stitch_omnipos_modular_saas/dashboard_principal_perfil_retail/code.html
- `Inventory Batches (lotes y vencimientos)` --references--> `Pantalla: Entrada de Mercancía (Compras y Lotes)`  [INFERRED]
  promt master.txt → Pantallas del sistema/stitch_omnipos_modular_saas/entradas_de_mercanc_a_compras_y_lotes/code.html
- `Inventory Batches (lotes y vencimientos)` --references--> `Pantalla: Inventario Avanzado (Control de Lotes)`  [INFERRED]
  promt master.txt → Pantallas del sistema/stitch_omnipos_modular_saas/inventario_avanzado_control_de_lotes/code.html
- `makeSequence()` --references--> `NcfSequence`  [EXTRACTED]
  tests/Feature/NcfSequenceTest.php → app/Modules/Setting/Models/NcfSequence.php
- `makeInvoice()` --references--> `Invoice`  [EXTRACTED]
  tests/Feature/ReportAnnulmentsTest.php → app/Modules/Invoice/Models/Invoice.php

## Import Cycles
- None detected.

## Communities (317 total, 128 thin omitted)

### Community 0 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.05
Nodes (28): audit(), auditLogs(), AuditLog, CompanyModel, AuditLogger, CustomerAddress, CustomerContact, CustomerCreditMovement (+20 more)

### Community 1 - "Illuminate\Http\JsonResponse"
Cohesion: 0.06
Nodes (18): ApiResponse, AuditLogController, AuthController, TwoFactorController, CompanyController, CustomerController, StockController, SupplierController (+10 more)

### Community 2 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.03
Nodes (16): AgentTerminal, AppointmentServiceLine, PurchaseFiscalData, PurchaseItem, InvoiceItem, CompanyModule, AgentTerminal, CashMovement (+8 more)

### Community 3 - "Invoice"
Cohesion: 0.05
Nodes (21): cancel(), checkStatus(), generatePayload(), send(), validateBeforeSend(), SendElectronicInvoiceJob, ProcessIssuedInvoice, ElectronicInvoice (+13 more)

### Community 4 - "User.php"
Cohesion: 0.10
Nodes (4): CreateCompanyAction, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Http\UploadedFile, Laravel\Sanctum\Sanctum

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (21): PermissionController, CompanyUserResource, PermissionResource, RoleResource, AppointmentResource, AuditLogResource, UserResource, BranchResource (+13 more)

### Community 6 - "ErrorCode.php"
Cohesion: 0.07
Nodes (10): CreateRoleAction, DeactivateRoleAction, UpdateRoleAction, RoleController, UpdateBranchAction, BranchController, CustomerCreditService, Illuminate\Support\Carbon (+2 more)

### Community 7 - "PosPage.vue"
Cohesion: 0.03
Nodes (48): activeSession, ActiveSessionData, applyTip, availablePrinters, cart, changeDueDop, closeCountedAmount, customers (+40 more)

### Community 8 - "[No publicado]"
Cohesion: 0.03
Nodes (58): 2026-07-09 — Corrección de navegación SPA, 2026-07-09 — Fase 1: acceso de usuarios por compañía, 2026-07-09 — Fase 1: acceso y contexto operativo, 2026-07-09 — Fase 1: administración de sucursales, 2026-07-09 — Fase 1: aislamiento multiempresa base, 2026-07-09 — Fase 1: autenticación API segura, 2026-07-09 — Fase 1: bitácora de auditoría consultable, 2026-07-09 — Fase 1: CRUD seguro de roles (+50 more)

### Community 9 - "AgendaPage.vue"
Cohesion: 0.06
Nodes (51): appointments, billInPos(), changeStatus(), customers, employees, emptyForm(), error, filterDate (+43 more)

### Community 10 - "User"
Cohesion: 0.06
Nodes (14): authorizeApi(), User, AuditLogPolicy, LogoutUserAction, RegisterUserAction, CompanyPolicy, PurchaseConfirmAction, InventoryService (+6 more)

### Community 11 - "ConfigurationPage.vue"
Cohesion: 0.08
Nodes (40): error, labels, load(), loading, props, save(), saved, saving (+32 more)

### Community 12 - "Application.kt"
Cohesion: 0.07
Nodes (25): AgentConfig, AgentSecurity, RequestWindow, SecurityDecision, ALLOWED, FORBIDDEN, RATE_LIMITED, UNAUTHORIZED (+17 more)

### Community 13 - "Inventory Batches (lotes y vencimientos)"
Cohesion: 0.06
Nodes (39): ElectronicInvoiceProviderInterface, ITBIS y Propina Legal, Multimoneda DOP/USD (exchange_rates), Secuencias NCF / e-NCF, Pantalla: Configuración de Módulos, Pantalla: Dashboard Principal (Retail), Pantalla: Entrada de Mercancía (Compras y Lotes), Pantalla: Facturas Electrónicas (e-CF) (+31 more)

### Community 14 - "AgentTerminalsPage.vue"
Cohesion: 0.06
Nodes (33): AgentBluetoothDevice, AgentDeviceSummary, AgentPrinterInfo, AgentPrintResult, AgentSerialPort, getAgentDevices(), getAgentPrinters(), testWithAgent() (+25 more)

### Community 15 - "CurrentCompany"
Cohesion: 0.13
Nodes (6): CurrentCompany, EmployeeController, ReportController, SettingController, Illuminate\Http\Response, Symfony\Component\HttpFoundation\StreamedResponse

### Community 16 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.06
Nodes (7): Order, Product, ProductModifier, RestaurantService, WorkOrder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Eloquent\Relations\HasOne

### Community 17 - "InventoryPage.vue"
Cohesion: 0.07
Nodes (34): activeTab, availableProducts, confirmPurchase(), errorMsg, filteredProductsDropdown, getErrorMessage(), handleCreatePurchase(), handleCreateSupplier() (+26 more)

### Community 18 - "WorkOrderListPage.vue"
Cohesion: 0.09
Nodes (33): setExternalOrderBridge(), billInPos(), changeStatus(), emptyForm(), error, filterStatus, form, load() (+25 more)

### Community 19 - "Customer"
Cohesion: 0.11
Nodes (8): CreateCustomerAction, ProvisionGenericCustomer, UpdateCustomerAction, Customer, CustomerPolicy, DemoVerticalsSeeder, Tax, User

### Community 20 - "Product.php"
Cohesion: 0.13
Nodes (5): CreateAppointmentAction, Appointment, Employee, Vehicle, Illuminate\Database\Eloquent\SoftDeletes

### Community 21 - "Company"
Cohesion: 0.14
Nodes (4): Company, SaveProductAction, ReportService, Illuminate\Database\Eloquent\Builder

### Community 22 - "api.ts"
Cohesion: 0.10
Nodes (22): api, storageKeys, availableBranches, branchCode, branchName, companies, companyName, errorMessage (+14 more)

### Community 23 - "ModuleManagerService"
Cohesion: 0.14
Nodes (4): OnboardingController, SystemModule, ModuleManagerService, ModuleCatalog

### Community 24 - "AppLayout.vue"
Cohesion: 0.08
Nodes (22): AppLocale, getCurrentLanguage(), i18n, setLanguage(), branchName, companyName, currentLang, filteredSections (+14 more)

### Community 25 - "NcfSequence"
Cohesion: 0.09
Nodes (9): CashRegister, NcfSequence, ConfigurationSeeder, DemoSeeder, OwnerUserSeeder, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash, makeSequence() (+1 more)

### Community 26 - "support/session.ts"
Cohesion: 0.16
Nodes (14): RFC-6238, globalSetup(), APP_ENV_VARS, BASE_URL, DEMO_USER, E2E_DATABASE, HOST, phpBinary() (+6 more)

### Community 27 - "router/index.ts"
Cohesion: 0.09
Nodes (18): code, email, errorMessage, isSubmitting, needsCode, password, router, session (+10 more)

### Community 28 - "OnboardingPage.vue"
Cohesion: 0.10
Nodes (24): businessTypes, cashRegisterName, chooseType(), chosen, currencyCode, defaults, defaultTaxRate, error (+16 more)

### Community 29 - "Warehouse"
Cohesion: 0.10
Nodes (5): InventoryBatch, InventoryMovement, InventoryStock, Warehouse, CreateOrderAction

### Community 30 - "UserManagementPage.vue"
Cohesion: 0.11
Nodes (22): branches, editing, emptyForm(), ensureDefaultBranch(), error, form, load(), loading (+14 more)

### Community 31 - "Branch"
Cohesion: 0.09
Nodes (7): CreateBranchAction, UpdateBranchRequest, Branch, BranchPolicy, ProvisionDefaultWarehouse, ProvisionDefaultCashRegister, CashSessionService

### Community 32 - "ProductListPage.vue"
Cohesion: 0.09
Nodes (14): categories, editing, emptyForm(), error, form, imageFile, imageInput, imagePreview (+6 more)

### Community 33 - "VehicleListPage.vue"
Cohesion: 0.16
Nodes (20): customers, emptyForm(), error, form, load(), loading, message(), reset() (+12 more)

### Community 34 - "CustomerListPage.vue"
Cohesion: 0.16
Nodes (19): customers, editing, emptyForm(), error, form, load(), loading, message() (+11 more)

### Community 35 - "ReportsPage.vue"
Cohesion: 0.11
Nodes (19): activeTab, download(), downloading, error, from, load(), loading, message() (+11 more)

### Community 36 - "SecurityPage.vue"
Cohesion: 0.14
Nodes (20): begin(), busy, confirm(), confirmCode, disable(), disableCode, enabled, error (+12 more)

### Community 37 - "LocalPrinterService"
Cohesion: 0.22
Nodes (8): BluetoothDeviceInfo, DeviceSummary, ByteArray, LocalPrinterService, PrinterInfo, SerialPortInfo, PrinterState, Result

### Community 38 - "00_PROJECT_OVERVIEW.md"
Cohesion: 0.14
Nodes (7): Documentación viva, Fuentes de verdad (en orden de prioridad), Grafo de conocimiento, OmniPOS Modular SaaS — Instrucciones del proyecto, Reglas duras (resumen), 13 — Decisiones Técnicas (ADR), 14 — Problemas Conocidos

### Community 39 - "ValidTaxId.php"
Cohesion: 0.10
Nodes (6): StoreCustomerRequest, UpdateCustomerRequest, ValidTaxId, DominicanTaxId, StoreSupplierRequest, Illuminate\Contracts\Validation\ValidationRule

### Community 40 - "Kardex Product Movement History Screen"
Cohesion: 0.13
Nodes (21): Kardex Product Movement History Screen, OmniPOS Admin Sidebar Navigation, CSV Export Button, Lot and Reference Traceability, Manual Adjustment Action (Ajuste Manual), Movement Type Badges, Movements Table, 30-Day Rotation Sparkline Chart (+13 more)

### Community 41 - "Role"
Cohesion: 0.13
Nodes (5): ProvisionCompanyOwnerAccess, Permission, Role, RolePolicy, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 42 - "10 — Despliegue"
Cohesion: 0.09
Nodes (21): 08 — Seguridad y Auditoría, Acciones sensibles con autorización elevada, Auditoría (módulo Audit), Autenticación y sesiones, Autorización en capas, Datos, Implementado en Fase 1, Implementado en Fase 1: acceso de usuarios (+13 more)

### Community 43 - "compilerOptions"
Cohesion: 0.10
Nodes (20): DOM, DOM.Iterable, ES2022, resources/js/**/*.ts, resources/js/**/*.vue, vite/client, vite.config.js, vitest/globals (+12 more)

### Community 44 - "ModuleManagementPage.vue"
Cohesion: 0.17
Nodes (16): busy, categories, categoryLabels, error, message(), store, toggle(), disableModule() (+8 more)

### Community 45 - "Dashboard Principal Perfil Retail (OmniPOS)"
Cohesion: 0.18
Nodes (19): Dashboard Principal Perfil Retail (OmniPOS), Abrir POS Quick Action Button, Alert Color Coding (red urgency for inventory alerts and pending collections), KPI Cards (Ventas de Hoy, Facturas Emitidas, Total Cobrado, Alertas de Inventario), Dominican Peso (RD$) Currency Localization, Retail/Supermarket Business Profile (Supermercado Central), Proximos a Vencer Panel (expiring lots with days-remaining badges), Sidebar Navigation (Dashboard, Inventory, Sales, Customers, Reports, Settings) (+11 more)

### Community 46 - "SettingsService"
Cohesion: 0.15
Nodes (4): CompleteOnboardingAction, SettingsService, BusinessTypeSettingsPresets, SettingsSchema

### Community 47 - "Purchase"
Cohesion: 0.20
Nodes (3): PurchaseController, Purchase, PurchaseFiscalDataService

### Community 48 - "11 — TODO MASTER"
Cohesion: 0.11
Nodes (18): 11 — TODO MASTER, Backlog (post-v1), Conocimiento del proyecto, FASE 0 — Base, FASE 10 — Facturación electrónica, FASE 11 — Impresión, FASE 12 — Módulos por negocio, FASE 13 — Reportes (+10 more)

### Community 49 - "AuditLogPage.vue"
Cohesion: 0.14
Nodes (14): AuditLogResponse, errorMessage, filters, hasFilters, isLoading, loadLogs(), logs, pagination (+6 more)

### Community 50 - "BranchManagementPage.vue"
Cohesion: 0.15
Nodes (15): branches, BranchForm, editing, emptyForm(), error, form, load(), loading (+7 more)

### Community 51 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.15
Nodes (5): StoreRoleRequest, UpdateCompanyUserAccessRequest, CompleteOnboardingRequest, StoreProductRequest, Illuminate\Foundation\Http\FormRequest

### Community 52 - "devDependencies"
Cohesion: 0.12
Nodes (17): axios, concurrently, eslint, devDependencies, axios, concurrently, eslint, @playwright/test (+9 more)

### Community 53 - "RestaurantLayoutPage.vue"
Cohesion: 0.12
Nodes (12): Area, areas, availableTablesForTransfer, destinationTableId, errorMsg, executeTransfer(), loading, loadLayout() (+4 more)

### Community 54 - "Closure"
Cohesion: 0.24
Nodes (6): EnsureBranchContext, EnsureCompanyContext, EnsureModuleEnabled, EnsurePermission, Closure, Symfony\Component\HttpFoundation\Response

### Community 55 - "ProductController"
Cohesion: 0.17
Nodes (4): CategoryController, ProductController, CategoryResource, Illuminate\Support\Facades\Route

### Community 56 - "scripts"
Cohesion: 0.12
Nodes (16): scripts, analyse, dev, post-create-project-cmd, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout (+8 more)

### Community 57 - "TotpService"
Cohesion: 0.19
Nodes (3): TotpService, LoginUserAction, currentTotpCode()

### Community 58 - "NcfSequenceService"
Cohesion: 0.18
Nodes (3): ReservedNcf, DocumentType, NcfSequenceService

### Community 59 - "dependencies"
Cohesion: 0.18
Nodes (11): dependencies, pinia, vite-plugin-pwa, vue, vue-i18n, vue-router, pinia, vite-plugin-pwa (+3 more)

### Community 60 - "IndexedDBService"
Cohesion: 0.23
Nodes (7): openDrawerWithAgent(), IndexedDBService, OfflineOrder, checkOfflineQueue(), submitOrder(), syncOfflineOrders(), testDrawer()

### Community 61 - "ElectronicInvoicePage.vue"
Cohesion: 0.17
Nodes (14): EInvoice, EInvoiceSettings, error, load(), loading, message(), records, retry() (+6 more)

### Community 62 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, extra, laravel, dont-discover, license, minimum-stability (+5 more)

### Community 63 - "02 — Esquema Inicial de Base de Datos"
Cohesion: 0.14
Nodes (14): 02 — Esquema Inicial de Base de Datos, 10. Facturación electrónica (§19), 11. Módulos verticales, 12. Reportes DGII (addendum), 1. Núcleo SaaS, 2. Sistema de módulos y planes, 3. Configuración fiscal y monedas, 4. Clientes (+6 more)

### Community 64 - "09 — Estrategia de Pruebas"
Cohesion: 0.14
Nodes (13): 09 — Estrategia de Pruebas, Cobertura obligatoria backend (master prompt §29), E2E Playwright (Fase 14, sobre app real), Herramientas, Implementado en Fase 10: e-CF y onboarding operativo, Implementado en Fase 13: reportes base, Implementado en Fase 1: auditoría, Implementado en Fase 1: Policies de recursos (+5 more)

### Community 65 - "RoleManagementPage.vue"
Cohesion: 0.22
Nodes (13): deactivate(), editing, error, form, load(), loading, message(), permissions (+5 more)

### Community 66 - "products/services.ts"
Cohesion: 0.24
Nodes (12): loadData(), load(), message(), removeImage(), save(), createProduct(), deleteProductImage(), fetchCategories() (+4 more)

### Community 69 - "Reportes operativos (Fase 13)"
Cohesion: 0.15
Nodes (13): `GET /api/v1/reports/cash`, `GET /api/v1/reports/dgii/606?period=YYYY-MM`, `GET /api/v1/reports/dgii/607?period=YYYY-MM`, `GET /api/v1/reports/dgii/608?period=YYYY-MM`, `GET /api/v1/reports/sales`, `GET /api/v1/reports/sales/by-customer`, `GET /api/v1/reports/sales/by-payment-method`, `GET /api/v1/reports/sales/by-product` (+5 more)

### Community 70 - "DashboardPage.vue"
Cohesion: 0.15
Nodes (11): activeDiningTab, branchName, currentPeriod, filteredSales, modules, operatorName, RecentSale, recentSales (+3 more)

### Community 71 - "ApiException"
Cohesion: 0.27
Nodes (3): ApiException, ModuleException, NcfSequenceException

### Community 72 - "FiscalCatalog"
Cohesion: 0.18
Nodes (3): PermissionCatalog, ProvisionCompanyConfiguration, FiscalCatalog

### Community 73 - "scripts"
Cohesion: 0.17
Nodes (12): scripts, build, dev, electron:build, electron:dev, format:check, lint, test:e2e (+4 more)

### Community 74 - "Entrada de Mercancía Screen (Compras y Lotes)"
Cohesion: 0.29
Nodes (11): Entrada de Mercancía Screen (Compras y Lotes), OmniPOS Admin Sidebar Navigation (Inventory module active), Barcode Product Scanning (Escanear Producto), Batch/Lot Tracking with Expiration Dates (Lote, Vencimiento), Document Info Form (Proveedor, Almacén Destino, Fecha de Entrada), Draft vs Confirm Workflow (Borrador badge, Guardar Borrador, Confirmar Entrada Afecta Stock), Editable Line Items Table (Cant., Costo Unit., manual row add, row delete), Multi-Warehouse Destination Selection (Bodega Principal) (+3 more)

### Community 76 - "require-dev"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision (+3 more)

### Community 78 - "Ciclo operativo completo"
Cohesion: 0.18
Nodes (11): Ciclo operativo completo, El panel de la ferretería, Guía de uso — Ferretería 🔧, Paso 1 — Registrar tus productos, Paso 2 — Revisar y cargar existencias (Inventario), Paso 3 — Abrir la caja, Paso 4 — Armar la venta, Paso 5 — Cobrar (+3 more)

### Community 79 - "System Configuration Active Modules Screen"
Cohesion: 0.24
Nodes (10): Dual Navigation Layout (top nav + sidebar), Guardar Cambios Save Button, Inventario Avanzado Module, Mesas y Comandas Module, Modular SaaS Architecture (feature flags per tenant), Module Dependency Badge (Requiere Inventario Base), Module Toggle Cards, POS Touch Module (+2 more)

### Community 80 - "Facturación Electrónica Control de e-CF Screen"
Cohesion: 0.27
Nodes (10): Facturación Electrónica Control de e-CF Screen, Contextual Action Buttons per Status, DGII e-CF Compliance (Dominican Republic), DGII Status Chip, e-CF Invoice Table, Filter Bar (Estado DGII, Tipo Comprobante, Rango de Fechas), NCF Type Badge (B01/B02), RNC Inválido Inline Validation Error (+2 more)

### Community 81 - "Inventario Avanzado Control de Lotes Screen"
Cohesion: 0.36
Nodes (10): Inventario Avanzado Control de Lotes Screen, Alerts Counter Button (Alertas 3), Batch/Lot Control with Expirations, Branch and Warehouse Filters, Expandable Product Table, Expiration Badge (Prox. Vencer), Low Stock Highlight (Red Arrow on Quantity), Sidebar Navigation (OmniPOS Modules) (+2 more)

### Community 82 - "Onboarding Module Activation Screen (Paso 3 de 5)"
Cohesion: 0.29
Nodes (10): Onboarding Module Activation Screen (Paso 3 de 5), Business Type Badge (Restaurante / Retail context chip), Facturacion Electronica Module (fiscal compliance, e-CF for DGII), Modular SaaS Architecture (per-tenant feature activation), Module Toggle Cards (icon, name, subtitle, description, on/off switch), Optional Modules Section (Inventario Avanzado toggled off), Recommended Modules Section (industry-based, 3 pre-activated modules), Restaurant-Oriented Modules (POS Touch, Mesas/Comandas with KDS) (+2 more)

### Community 83 - "POS Touch Screen - Restaurant Profile (OmniPOS)"
Cohesion: 0.38
Nodes (10): POS Touch Screen - Restaurant Profile (OmniPOS), Category Sidebar (Food, Drinks, Dessert, Snacks), Dominican Peso (RD$) Currency Formatting, ITBIS 18% Tax Calculation, Line Item Modifiers and Quantity Steppers, Order Actions (Guardar Pedido, Imprimir Comanda, Registrar Venta), Order Panel (Order #4092 with Line Items), Product Grid with Photo Cards (+2 more)

### Community 84 - "Procesar Pago Multimoneda y Mixto Screen"
Cohesion: 0.33
Nodes (10): Procesar Pago Multimoneda y Mixto Screen, Change Calculation (Monto Recibido / Devuelta), Finalizar y Facturar Primary Action Button, Dominican Tax Breakdown (ITBIS 18% and Propina Legal 10%), Mixed Payments Applied List (Pagos Aplicados / Falta por Pagar), Multi-Currency USD Payment with Exchange Rate (Tasa: 60.50), NCF Comprobante Type Selector (Consumidor Final / Credito Fiscal), Numeric Keypad (+2 more)

### Community 85 - "01 — Arquitectura"
Cohesion: 0.20
Nodes (10): 01 — Arquitectura, Capas de protección de una ruta, Contexto tenant implementado (Fase 1), Convenciones críticas, Decisiones registradas, Estructura backend, Eventos de dominio clave, Multi-tenancy (+2 more)

### Community 86 - "03 — Documentación de API"
Cohesion: 0.20
Nodes (10): 03 — Documentación de API, Auditoría (Fase 1), Clientes (Fase 4), Convenciones, `GET /api/v1/audit-logs`, `GET /api/v1/audit-logs/{publicId}`, `GET /api/v1/customers?search=&page=`, Mapa de endpoints previsto (por fase) (+2 more)

### Community 87 - "MASTER PROMPT — ADDENDUM (v1.1)"
Cohesion: 0.20
Nodes (10): 34. LOCALIZACIÓN FISCAL REPÚBLICA DOMINICANA, 35. FACTURACIÓN ELECTRÓNICA — CONTEXTO LEY 32-23, 36. INTEGRIDAD Y CONCURRENCIA (OBLIGATORIO), 37. CALIDAD, TOOLING Y CI (OBLIGATORIO), 38. SAAS COMERCIAL, 39. UX / FRONTEND (COMPLEMENTO), 40. MÓDULOS ADICIONALES (CATÁLOGO EXTENDIDO), 41. OPERACIÓN (+2 more)

### Community 88 - "KitchenKdsPage.vue"
Cohesion: 0.24
Nodes (9): errorMsg, getMinutesElapsed(), getTimeColor(), items, KitchenItem, loading, loadKds(), successMsg (+1 more)

### Community 89 - "Onboarding Business Type Selection Screen"
Cohesion: 0.28
Nodes (9): Onboarding Business Type Selection Screen, Business Type Cards Grid, Dominican Republic Context (RD$ currency), Industry Vertical Options (Restaurante, Cafeteria, Supermercado, Retail, Barberia/Salon, Taller Mecanico, Servicios Profesionales), Business Type Drives Modular Feature Configuration, OmniPOS SaaS Onboarding Flow, Selected Card State (indigo border, filled icon, checkmark badge), Wizard Navigation Footer (Atras / Siguiente buttons) (+1 more)

### Community 92 - "BusinessType"
Cohesion: 0.25
Nodes (3): BusinessType, SubscriptionPlan, ModuleSystemSeeder

### Community 93 - "AppServiceProvider"
Cohesion: 0.28
Nodes (3): AppServiceProvider, ModuleServiceProvider, Illuminate\Support\ServiceProvider

### Community 94 - "00 — Visión General del Proyecto: OmniPOS Modular SaaS"
Cohesion: 0.22
Nodes (9): 00 — Visión General del Proyecto: OmniPOS Modular SaaS, Arquitectura en una línea, Documentos, Fases de desarrollo (resumen), Mercado objetivo, Módulos opcionales, Núcleo (siempre activo), Qué es (+1 more)

### Community 95 - "05 — Sistema de Módulos"
Cohesion: 0.22
Nodes (9): 05 — Sistema de Módulos, Catálogo de módulos, Core (no desactivables), Mapa de dependencias (regla), Matriz por tipo de negocio, Middleware y frontend, ModuleManagerService, Opcionales (+1 more)

### Community 96 - "Ciclo operativo completo"
Cohesion: 0.22
Nodes (9): Ciclo operativo completo, El panel del restaurante, Guía de uso — Restaurante 🍽️, Paso 1 — El plano de mesas, Paso 2 — Abrir cuenta en una mesa, Paso 3 — Tomar la orden (POS), Paso 4 — La cocina (KDS), Paso 5 — Cobrar y cerrar la mesa (+1 more)

### Community 97 - "Ciclo operativo completo"
Cohesion: 0.22
Nodes (9): Ciclo operativo completo, El panel de la barbería, Guía de uso — Salón / Barbería 💈, Paso 1 — Registrar a tus barberos (Empleados), Paso 2 — Ver la agenda del día, Paso 3 — Agendar una cita nueva, Paso 4 — Hacer avanzar la cita por sus estados, Paso 5 — Cobrar (+1 more)

### Community 98 - "Ciclo operativo completo"
Cohesion: 0.22
Nodes (9): Ciclo operativo completo, El panel del supermercado, Guía de uso — Supermercado 🛒, Paso 1 — Existencias con lotes y vencimientos, Paso 2 — Cargar mercancía (compras), Paso 3 — Vender en el POS, Paso 4 — Cobro y comprobante, Paso 5 — Cierre y reportes (+1 more)

### Community 99 - "Ciclo operativo completo"
Cohesion: 0.22
Nodes (9): Ciclo operativo completo, El panel del taller, Guía de uso — Taller mecánico 🚗, Paso 1 — Registrar el vehículo del cliente, Paso 2 — Ver las órdenes de trabajo, Paso 3 — Crear una orden nueva, Paso 4 — Hacer avanzar la orden por sus estados, Paso 5 — Cobrar (+1 more)

### Community 100 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 101 - "pos/services.ts"
Cohesion: 0.22
Nodes (8): cartScope(), loadData(), consumeExternalOrderBridge(), ExternalOrderBridge, PosOrder, PosOrderItem, PosPayment, PosService

### Community 102 - "OmniPOS Modular SaaS — Instrucciones del proyecto"
Cohesion: 0.25
Nodes (8): 1) Orientarte con Graphify (por bash, NO es un MCP), 2) Verificar en navegador con Playwright MCP (herramientas `browser_*`), Bucle de trabajo, Cómo trabajar sin perderte (Graphify + Playwright), Documentación viva, Fuentes de verdad (en orden de prioridad), OmniPOS Modular SaaS — Instrucciones del proyecto, Reglas duras (resumen)

### Community 103 - "UserFactory"
Cohesion: 0.32
Nodes (5): bootBelongsToCompany(), bootHasPublicUlid(), UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 110 - "config"
Cohesion: 0.25
Nodes (8): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, platform-check, preferred-install, sort-packages

### Community 111 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 112 - "04 — Estructura Frontend"
Cohesion: 0.25
Nodes (7): 04 — Estructura Frontend, Design system: "Kinetic Enterprise", Estructura (master prompt §5), Implementado en Fase 1, Implementado en Fase 10, Pantallas de referencia (Stitch), Reglas frontend

### Community 113 - "15 — Inventario, Lotes y Vencimientos"
Cohesion: 0.25
Nodes (7): 15 — Inventario, Lotes y Vencimientos, Alertas (job diario + eventos en tiempo real), Kardex, Modelo, Métodos de salida (por producto, `outgoing_method`), Reglas de venta con vencimiento (configurables por empresa), Reportes

### Community 114 - "TestCase"
Cohesion: 0.29
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 115 - "DESIGN.md"
Cohesion: 0.25
Nodes (7): Brand & Style, Colors, Components, Elevation & Depth, Layout & Spacing, Shapes, Typography

### Community 116 - "products/types.ts"
Cohesion: 0.25
Nodes (7): Category, Product, ProductCombo, ProductForm, ProductModifier, ProductModifierOption, ProductVariant

### Community 117 - "Configuración fiscal (Fase 3)"
Cohesion: 0.29
Nodes (7): Configuración fiscal (Fase 3), `GET /api/v1/settings/fiscal`, `GET/POST /api/v1/exchange-rates`, `GET/POST /api/v1/ncf-sequences`, `GET/PUT /api/v1/settings/{group}`, `POST /api/v1/payment-methods`, `POST /api/v1/taxes` · `PATCH /api/v1/taxes/{publicId}`

### Community 118 - "06 — Facturación Electrónica (e-CF DGII, República Dominicana)"
Cohesion: 0.29
Nodes (7): 06 — Facturación Electrónica (e-CF DGII, República Dominicana), Contexto legal (investigado 2026-07), Criterios de aceptación del módulo, Diseño en el sistema, Flujo e-CF (base implementada detrás de la interface), Fuentes, Tipos de comprobante

### Community 119 - "Ciclo operativo completo"
Cohesion: 0.29
Nodes (7): Ciclo operativo completo, El panel de la cafetería, Guía de uso — Cafetería ☕, Paso 1 — Abrir la caja, Paso 2 — Vender rápido, Paso 3 — Cobrar, Resumen del ciclo

### Community 120 - "Ciclo operativo"
Cohesion: 0.29
Nodes (7): Ciclo operativo, El panel de la distribuidora, Guía de uso — Distribuidora 🚚, Paso 1 — Registrar clientes con crédito, Paso 2 — Abastecerte (compras a proveedores), Paso 3 — Vender, Resumen del ciclo

### Community 121 - "Ciclo operativo completo"
Cohesion: 0.29
Nodes (7): Ciclo operativo completo, El panel del colmado, Guía de uso — Colmado / Minimarket 🏪, Paso 1 — Registrar productos, Paso 2 — Vender en el POS, Paso 3 — Cobrar y emitir el comprobante, Resumen del ciclo

### Community 124 - "module.json"
Cohesion: 0.33
Nodes (5): category, code, dependencies, is_core, name

### Community 126 - "keywords"
Cohesion: 0.33
Nodes (6): keywords, facturacion, laravel, pos, republica-dominicana, saas

### Community 127 - "require"
Cohesion: 0.33
Nodes (6): require, brick/money, laravel/framework, laravel/sanctum, laravel/tinker, php

### Community 128 - "2026_09_05_100000_create_agent_terminals_table.php"
Cohesion: 0.33
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 129 - "Verificación en dos pasos (Fase 14)"
Cohesion: 0.33
Nodes (6): `GET /api/v1/auth/2fa`, `POST /api/v1/auth/2fa/confirm`, `POST /api/v1/auth/2fa/disable`, `POST /api/v1/auth/2fa/enable`, `POST /api/v1/auth/login`, Verificación en dos pasos (Fase 14)

### Community 130 - "Guía de uso — Servicios profesionales 💼"
Cohesion: 0.33
Nodes (6): Ciclo operativo, El panel de servicios profesionales, Guía de uso — Servicios profesionales 💼, Paso 1 — Registrar clientes, Paso 2 — Catálogo de servicios y cotizaciones, Resumen del ciclo

### Community 131 - ".of"
Cohesion: 0.40
Nodes (3): Money, Brick\Money\Money, BrickMoney

### Community 137 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 138 - "Roles y permisos (Fase 1)"
Cohesion: 0.40
Nodes (5): `DELETE /api/v1/roles/{publicId}`, `GET /api/v1/permissions`, `GET/POST /api/v1/roles`, `PATCH /api/v1/roles/{publicId}`, Roles y permisos (Fase 1)

### Community 139 - "Módulos y onboarding (Fase 2)"
Cohesion: 0.40
Nodes (5): `GET /api/v1/business-types[?business_type=<code>]`, `GET /api/v1/modules`, Módulos y onboarding (Fase 2), `POST /api/v1/modules/{code}/enable` · `POST /api/v1/modules/{code}/disable`, `POST /api/v1/onboarding`

### Community 140 - "07 — Impresión"
Cohesion: 0.40
Nodes (5): 07 — Impresión, Alcance (master prompt §20), Configuración, Estrategia por etapas, Requisitos fiscales del ticket/factura RD

### Community 141 - "main.cjs"
Cohesion: 0.50
Nodes (4): { app, BrowserWindow }, createWindow(), isAllowedNavigation(), path

### Community 142 - "Acceso al sistema (común a todos los giros)"
Cohesion: 0.40
Nodes (5): Acceso al sistema (común a todos los giros), Cerrar sesión, Paso 1 — Iniciar sesión, Paso 2 — Elegir compañía y sucursal, Paso 3 — El panel de control

### Community 143 - "Guía de Uso — OmniPOS"
Cohesion: 0.40
Nodes (5): Antes de empezar, Cuentas de demostración, Guía de Uso — OmniPOS, Guías por tipo de negocio, Idea clave: módulos activables

### Community 144 - ".prettierrc.json"
Cohesion: 0.40
Nodes (4): printWidth, singleQuote, tabWidth, trailingComma

### Community 167 - "Facturación electrónica (Fase 10)"
Cohesion: 0.50
Nodes (4): Facturación electrónica (Fase 10), `GET /api/v1/electronic-invoices` · `GET /api/v1/electronic-invoices/{publicId}/logs`, `GET/PUT /api/v1/electronic-invoices/settings`, `POST /api/v1/electronic-invoices/{publicId}/retry`

### Community 168 - "Sucursales (Fase 1)"
Cohesion: 0.50
Nodes (4): `GET /api/v1/branches`, `PATCH /api/v1/branches/{publicId}`, `POST /api/v1/branches`, Sucursales (Fase 1)

### Community 169 - "Usuarios por compañía (Fase 1)"
Cohesion: 0.50
Nodes (4): `GET /api/v1/users`, `PATCH /api/v1/users/{publicId}/access`, `POST /api/v1/users`, Usuarios por compañía (Fase 1)

### Community 171 - "useAgentStatus.ts"
Cohesion: 0.50
Nodes (3): AgentConnectionState, AgentStatusPayload, useAgentStatus()

### Community 176 - "post-autoload-dump"
Cohesion: 0.67
Nodes (3): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan package:discover --ansi

### Community 215 - "package.json"
Cohesion: 0.50
Nodes (3): private, $schema, type

## Ambiguous Edges - Review These
- `Purchase Order Reception (PO-2023-0891)` → `Reception Summary Panel (Subtotal, Impuestos IVA 19%, Flete/Otros, Total)`  [AMBIGUOUS]
  Pantallas del sistema/stitch_omnipos_modular_saas/entradas_de_mercanc_a_compras_y_lotes/screen.png · relation: conceptually_related_to

## Knowledge Gaps
- **873 isolated node(s):** `ExternalOrderBridge`, `DocumentType`, `PaymentMethod`, `ProductCombo`, `ProductModifier` (+868 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **128 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Purchase Order Reception (PO-2023-0891)` and `Reception Summary Panel (Subtotal, Impuestos IVA 19%, Flete/Otros, Total)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `Product` connect `products/types.ts` to `ProductListPage.vue`, `products/services.ts`, `Illuminate\Http\Request`, `PosPage.vue`, `InventoryPage.vue`, `ProductController`?**
  _High betweenness centrality (0.185) - this node is a cross-community bridge._
- **Why does `Company` connect `Company` to `Illuminate\Database\Eloquent\Model`, `Invoice`, `User.php`, `ErrorCode.php`, `FiscalCatalog`, `Role`, `User`, `SettingsService`, `CurrentCompany`, `Customer`, `Product.php`, `Closure`, `ModuleManagerService`, `NcfSequence`, `Branch`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `CurrentCompany` connect `CurrentCompany` to `Illuminate\Http\JsonResponse`, `User.php`, `Illuminate\Http\Request`, `ErrorCode.php`, `.store`, `VehicleController.php`, `InvoiceController`, `.index`, `Purchase`, `Company`, `ProductController`, `ElectronicInvoiceController`, `Branch`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Are the 120 inferred relationships involving `ApiResponse` (e.g. with `.index()` and `.store()`) actually correct?**
  _`ApiResponse` has 120 INFERRED edges - model-reasoned connections that need verification._
- **What connects `ExternalOrderBridge`, `DocumentType`, `PaymentMethod` to the rest of the system?**
  _873 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Eloquent\Model` be split into smaller, more focused modules?**
  _Cohesion score 0.05347985347985348 - nodes in this community are weakly interconnected._