# 00 — Visión General del Proyecto: OmniPOS Modular SaaS

> Estado: **Planificación (Fase 0 no iniciada)** · Última actualización: 2026-07-09

## Qué es

**OmniPOS** es una plataforma SaaS multiempresa de **facturación, POS Touch, caja, clientes, productos/servicios, inventario avanzado, reportes y facturación electrónica (e-CF DGII)**, adaptable a distintos tipos de negocio mediante **módulos activables/desactivables**.

No es un POS rígido de restaurante: es una **plataforma modular de ventas y facturación** donde cada empresa activa solo lo que necesita según su tipo de negocio (restaurante, colmado, supermercado, ferretería, barbería, taller mecánico, distribuidora, servicios profesionales, etc.).

## Mercado objetivo

Negocios de **República Dominicana** (localización fiscal DGII: NCF/e-NCF, ITBIS, propina legal, reportes 606/607/608, Ley 32-23 de facturación electrónica), con arquitectura preparada para otros países (documento fiscal configurable).

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.3+, MySQL, Sanctum, Queues/Jobs/Scheduler |
| Frontend | Vue 3 + TypeScript, Pinia, Vue Router, Tailwind CSS, Axios, PWA |
| Calidad | Pest (backend), Vitest (unit front), Playwright (E2E), Pint, ESLint/Prettier, Larastan |
| Diseño | Design system "Kinetic Enterprise" (ver `Pantallas del sistema/.../kinetic_enterprise/DESIGN.md`) |
| Futuro | App Android Kotlin (impresión BT/USB ESC-POS, offline avanzado) — **no desarrollar ahora** |

## Arquitectura en una línea

**Monolito modular** (Laravel `app/Modules/*` + Vue `resources/js/modules/*`), multi-tenant por columna `company_id` en una sola base de datos, con menú/rutas/endpoints protegidos por autenticación + empresa + sucursal + permiso + módulo activo.

## Núcleo (siempre activo)

Auth · Company · Branch · UserAccess · ModuleManager · Setting · Customer · Payment · CashRegister · Invoice · ElectronicInvoice · Report básico · Audit · Security.

## Módulos opcionales

POS, Product, Service, Inventory, AdvancedInventory, Barcode, Purchase, Supplier, Warehouse, TableManagement, Kitchen, Delivery, DigitalMenu, Appointment, Vehicle, WorkOrder, Recipe, AccountsReceivable, AccountsPayable, Printer, Loyalty, Reservation, Warranty, SerialNumbers, Quotation, Expense, Employee/Commission, Notification.

Regla: desactivar un módulo **nunca borra datos históricos**.

## Documentos

| Doc | Contenido |
|---|---|
| [01_ARCHITECTURE.md](01_ARCHITECTURE.md) | Arquitectura, estructura backend, decisiones de capas |
| [02_DATABASE_SCHEMA.md](02_DATABASE_SCHEMA.md) | Esquema inicial de BD |
| [03_API_DOCUMENTATION.md](03_API_DOCUMENTATION.md) | Convenciones y endpoints |
| [04_FRONTEND_STRUCTURE.md](04_FRONTEND_STRUCTURE.md) | Estructura Vue + design system |
| [05_MODULE_SYSTEM.md](05_MODULE_SYSTEM.md) | Sistema de módulos y dependencias |
| [06_ELECTRONIC_INVOICING.md](06_ELECTRONIC_INVOICING.md) | e-CF DGII, Ley 32-23, providers |
| [07_PRINTING.md](07_PRINTING.md) | Impresión térmica/A4 |
| [08_SECURITY.md](08_SECURITY.md) | Seguridad y auditoría |
| [09_TESTING.md](09_TESTING.md) | Estrategia de pruebas (incl. Playwright) |
| [10_DEPLOYMENT.md](10_DEPLOYMENT.md) | Despliegue |
| [11_TODO_MASTER.md](11_TODO_MASTER.md) | TODO maestro por fases |
| [12_CHANGELOG.md](12_CHANGELOG.md) | Historial de cambios |
| [13_DECISIONS.md](13_DECISIONS.md) | Decisiones técnicas (ADR) |
| [14_KNOWN_ISSUES.md](14_KNOWN_ISSUES.md) | Problemas conocidos |
| [15_INVENTORY_AND_EXPIRATIONS.md](15_INVENTORY_AND_EXPIRATIONS.md) | Inventario, lotes, FEFO/FIFO, vencimientos |

Complemento del prompt original: [`MASTER_PROMPT_ADDENDUM.md`](../MASTER_PROMPT_ADDENDUM.md) (mejoras y requisitos que faltaban en `promt master.txt`).

## Fases de desarrollo (resumen)

0. Base (Laravel + Vue + tooling + estructura modular + docs)
1. Núcleo SaaS (empresas, sucursales, usuarios, roles, permisos, auditoría)
2. Sistema de módulos + onboarding
3. Configuración general (monedas, impuestos, métodos de pago, fiscal)
4. Clientes
5. Productos y servicios
6. Inventario avanzado (lotes, FEFO/FIFO, vencimientos)
7. POS Touch y órdenes
8. Caja y pagos (incl. pago mixto y multimoneda)
9. Facturación (NCF, secuencias, PDF/ticket, anulación, notas crédito/débito)
10. Facturación electrónica (interface + mock provider + jobs)
11. Impresión
12. Módulos por negocio (restaurante, barbería, taller)
13. Reportes (incl. 606/607 DGII)
14. Seguridad, pruebas E2E Playwright y deploy

Detalle con checklist en [11_TODO_MASTER.md](11_TODO_MASTER.md).
