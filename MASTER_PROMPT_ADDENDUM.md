# MASTER PROMPT — ADDENDUM (v1.1)

> Complementa `promt master.txt` (que queda intacto como base). Estas secciones cubren requisitos que faltaban y que son necesarios para un producto profesional. Tienen la **misma fuerza obligatoria** que el master prompt. Numeración continúa desde §33.

=====================================================================
## 34. LOCALIZACIÓN FISCAL REPÚBLICA DOMINICANA
=====================================================================

El mercado primario es RD (confirmado por las pantallas: RD$, ITBIS, e-CF DGII). El motor fiscal es configurable, pero RD se implementa completo:

1. **Comprobantes (NCF/e-NCF):** catálogo `document_types` con B01/B02/B03/B04/B11/B13/B14/B15/B16 y sus equivalentes electrónicos E31/E32/E33/E34/E41/E43/E44/E45/E46/E47.
2. **Secuencias NCF:** por empresa (+sucursal opcional) y tipo, con rango autorizado, número actual, **fecha de vencimiento de la secuencia** y alerta de agotamiento (umbral configurable). Consumo transaccional con lock. e-NCF de 10 dígitos.
3. **Impuestos:** ITBIS 18%, ITBIS reducido 16%, exento; **propina legal 10%** (restaurantes/bares — es cargo por servicio, no impuesto: línea separada en factura y reportes, configurable por tipo de negocio).
4. **Retenciones** (ITBIS/ISR sobre ventas a ciertos clientes): estructura preparada en invoice_taxes (type=retention), reglas configurables — no inventar tasas.
5. **Reportes DGII:** generación de **Formato 606 (compras), 607 (ventas), 608 (NCF anulados)** exportables (TXT/Excel según especificación DGII vigente), por período.
6. **Validación RNC (9 dígitos) y cédula (11 dígitos)** con dígito verificador; tipo de documento configurable por país para futuro multi-país.
7. Moneda base DOP con formato RD$; multimoneda (mínimo USD) con `exchange_rates` histórica por fecha; pagos en moneda extranjera registran tasa aplicada y equivalente en base.
8. Timezone `America/Santo_Domingo`; idioma **es-DO** por defecto (i18n preparado).

=====================================================================
## 35. FACTURACIÓN ELECTRÓNICA — CONTEXTO LEY 32-23
=====================================================================

(Complementa §19; detalles en docs/06.)

1. Obligatoriedad escalonada: grandes nacionales 2024; grandes locales/medianos 15-nov-2025; **pequeños/micro 15-nov-2026** → clientes objetivo del sistema estarán obligados: el módulo debe quedar listo para conectar un provider real.
2. Emisión requiere **certificado digital (INDOTEL)** y proceso de certificación DGII (ambientes TesteCF/CerteCF/eCF) → campo environment, certificado cifrado, alerta de vencimiento de certificado.
3. **Representación impresa** con e-NCF, código de seguridad y **QR de consulta DGII** en tickets/PDF.
4. **Modo contingencia:** si DGII/red falla, la venta NUNCA se bloquea; se emite en contingencia y se regulariza automáticamente (tabla y job dedicados). Política configurable.
5. Aprobación comercial B2B y conservación de XML firmados (retención fiscal) contemplados.
6. Estado adicional: `contingency`.

=====================================================================
## 36. INTEGRIDAD Y CONCURRENCIA (OBLIGATORIO)
=====================================================================

1. **Idempotencia:** `Idempotency-Key` en creación de órdenes/pagos/facturas (el POS reintenta con red inestable; jamás duplicar una venta).
2. **Secuencias con `lockForUpdate()`** dentro de transacción (facturas, NCF, órdenes). Prohibido MAX(+1).
3. **Stock:** descuento atómico dentro de transacción con verificación de disponible; ventas concurrentes del último ítem → solo una gana.
4. Transacción única por venta: orden + inventario + factura + pago + caja se confirman o revierten juntos.
5. **Soft deletes** en entidades de negocio; documentos fiscales nunca se borran, solo se anulan (con NCF anulado reportado en 608).
6. IDs públicos **ULID** (`public_id`); no exponer autoincrement.
7. Redondeo half-up a 2 decimales, aplicado en un solo lugar (Money helper).

=====================================================================
## 37. CALIDAD, TOOLING Y CI (OBLIGATORIO)
=====================================================================

1. Git desde el día 0 (la carpeta actual no es repo — inicializar), commits convencionales, ramas por fase.
2. Backend: Pest, **Larastan nivel ≥6**, Pint. Frontend: ESLint + Prettier + vue-tsc + Vitest.
3. **Playwright E2E** para los 10 flujos críticos (docs/09) + verificación en navegador real (Playwright MCP) de cada pantalla al desarrollarla.
4. CI (GitHub Actions): lint + estático + tests + build en cada push.
5. Factories y seeders de **demo por tipo de negocio** (restaurante demo, colmado demo, taller demo) para pruebas y ventas del SaaS.
6. Tests obligatorios de: aislamiento de tenant, race de secuencias NCF, FEFO/FIFO multi-lote, pago mixto que cuadra, idempotencia.

=====================================================================
## 38. SAAS COMERCIAL
=====================================================================

1. `company_subscriptions` (trial/active/past_due/suspended/canceled) + límites por plan (sucursales, usuarios, facturas/mes) además de módulos por plan.
2. Suspensión por impago: solo lectura (no bloquear acceso a SUS datos; bloquear emisión).
3. **Panel super-admin** (fuera del tenant): gestión de empresas, planes, suscripciones, métricas de uso, impersonación auditada. (Backlog v1.x, pero el modelo de datos se crea desde Fase 2.)
4. Onboarding con empresa demo pre-cargada opcional.

=====================================================================
## 39. UX / FRONTEND (COMPLEMENTO)
=====================================================================

1. Implementar el design system **Kinetic Enterprise** (DESIGN.md de las pantallas) como preset Tailwind + CSS variables; **modo oscuro** desde el inicio.
2. Todo en **español dominicano** consistente (las pantallas Stitch mezclan idiomas — corregir), con vue-i18n preparado.
3. Estados obligatorios por pantalla: carga (skeleton), vacío (con CTA), error (con retry), sin permiso, módulo inactivo.
4. Accesibilidad: contraste AA, focus visible, navegación teclado en formularios, `aria-*` en componentes ui.
5. POS: atajos de teclado + lector de código de barras (input capture global), teclado numérico ≥40% alto en móvil, touch targets 44/56px.
6. Búsqueda global (productos por nombre/SKU/barcode, clientes, facturas).
7. Notificaciones in-app (campana) para alertas de inventario/NCF/e-CF.

=====================================================================
## 40. MÓDULOS ADICIONALES (CATÁLOGO EXTENDIDO)
=====================================================================

Agregar al catálogo §3: **Quotation** (cotizaciones como documento propio convertible), **Expense** (gastos con categorías — alimenta 606), **Employee/Commission** (empleados, comisiones por venta/servicio — requerido por barbería), **Notification** (email/WhatsApp: recordatorio de cita, factura por WhatsApp — RD-friendly). AccountsReceivable incluye: ventas a crédito, abonos parciales, estado de cuenta del cliente, antigüedad de saldos.

=====================================================================
## 41. OPERACIÓN
=====================================================================

1. Backups automáticos programados (BD + storage fiscal) con retención.
2. Colas para: e-CF, PDFs pesados, reportes grandes, notificaciones, alertas de inventario (job diario).
3. Logs estructurados por empresa; health endpoint.
4. Documentar en `.env.example` cada variable.

=====================================================================
## 42. PRIORIDAD DE FUENTES
=====================================================================

Ante conflicto: (1) instrucciones directas del usuario en la conversación → (2) este addendum → (3) `promt master.txt` → (4) pantallas de referencia (inspiración). Las pantallas nunca justifican romper una regla de los prompts.
