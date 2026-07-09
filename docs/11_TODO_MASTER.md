# 11 — TODO MASTER

> Fuente de verdad del avance. Marcar `[x]` solo con pruebas verdes y documentación actualizada. Fases del master prompt §28 + adiciones del addendum.

**Estado global: FASE 0 en progreso — preparación del repositorio e infraestructura base.**

## Conocimiento del proyecto
- [x] Grafo Graphify consolidado e incorporado al grafo global como `omnipos-modular-saas` (231 nodos, 344 relaciones; validado sin duplicados ni referencias colgantes).

## FASE 0 — Base
- [x] Inicializar repositorio Git local (`main`) y vincular el remoto autorizado `origin`
- [ ] Crear proyecto Laravel 12 + configurar MySQL + .env(.example)
- [ ] Sanctum, Vue 3 + TS, Tailwind (preset Kinetic Enterprise), Pinia, Router, Axios, PWA
- [ ] Pint + Larastan + Pest; ESLint + Prettier + Vitest; Playwright configurado
- [ ] Estructura modular backend (`app/Core`, `app/Modules/*` + ModuleServiceProvider) y frontend
- [ ] API response estándar + manejo global de errores + códigos de error
- [ ] Trait BelongsToCompany, Auditable, soporte Money/DECIMAL, ULID público
- [ ] Seeders base (monedas, unidades, impuestos RD, document_types NCF/e-CF, permisos)
- [ ] CI GitHub Actions (si hay repo git — **crear repo git**)

## FASE 1 — Núcleo SaaS
- [ ] Empresas, sucursales, usuarios, roles, permisos (tablas + CRUD + policies)
- [ ] Login / logout / me; selección empresa y sucursal; middleware company/branch
- [ ] Tests de aislamiento de tenant
- [ ] Auditoría base (audit_logs + trait + pantalla mínima)

## FASE 2 — Módulos y onboarding
- [ ] business_types, system_modules, dependencies, presets por tipo, company/branch_modules, planes + suscripciones
- [ ] ModuleManagerService + middleware `module:` + cache
- [ ] useModuleStore + guards + menú dinámico
- [ ] Onboarding 5 pasos (pantallas de referencia Stitch)

## FASE 3 — Configuración
- [ ] Empresa, monedas + tasas, impuestos (ITBIS 18/16/exento, propina 10%), métodos de pago
- [ ] Secuencias NCF/e-NCF por tipo con vencimiento y alertas
- [ ] Config impresión, POS, inventario, seguridad, backups

## FASE 4 — Clientes
- [ ] CRUD + genérico + datos fiscales (validación RNC/cédula con dígito verificador) + direcciones + historial + crédito

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
