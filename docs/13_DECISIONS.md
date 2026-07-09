# 13 — Decisiones Técnicas (ADR)

| # | Fecha | Decisión | Motivo / alternativas |
|---|---|---|---|
| 001 | 2026-07-09 | **Monolito modular** (no microservicios, no paquetes nwidart) | Un solo equipo/deploy; fronteras por convención `app/Modules/*` + ServiceProviders propios. nwidart/laravel-modules se descartó para no acoplarse a un paquete externo; la estructura manual da lo mismo con menos magia. Revisable. |
| 002 | 2026-07-09 | **Multi-tenant por columna `company_id` en una sola BD** (no BD por tenant) | Pymes RD, volumen manejable; simplifica migraciones/backups; aislamiento por global scope + policies + tests dedicados. |
| 003 | 2026-07-09 | **Pest** sobre PHPUnit | Sintaxis más expresiva, estándar de facto en Laravel 12. |
| 004 | 2026-07-09 | Dinero en **DECIMAL(14,2)** + cálculo con centavos/brick-money; cantidades DECIMAL(14,4) | Mandato del master prompt; evita errores float. |
| 005 | 2026-07-09 | **ULID público (`public_id`)** + autoincrement interno | No exponer IDs secuenciales entre tenants; ULID ordenable. |
| 006 | 2026-07-09 | Secuencias (facturas/NCF) con fila de secuencia + `lockForUpdate()` en transacción | Evita NCF duplicados bajo concurrencia de terminales; MAX(+1) prohibido. |
| 007 | 2026-07-09 | **Idempotency-Key** en endpoints de venta/pago/factura | POS con red inestable reintenta sin duplicar ventas. |
| 008 | 2026-07-09 | Localización primaria **República Dominicana** (NCF, ITBIS, propina legal, 606/607/608, e-CF Ley 32-23), pero motor fiscal configurable | Las pantallas de referencia (RD$, ITBIS, e-CF DGII) lo confirman; el diseño no quema reglas RD en el núcleo: `document_types`, `taxes` y providers son datos/estrategias. |
| 009 | 2026-07-09 | e-CF vía `ElectronicInvoiceProviderInterface` con Null/Mock ahora, provider real después (directo o PSFE) | Mandato del master prompt: no inventar normativa; sistema funcional sin proveedor. |
| 010 | 2026-07-09 | TODO/CHANGELOG canónicos en `docs/11_TODO_MASTER.md` y `docs/12_CHANGELOG.md` | El master prompt los pide en ambas ubicaciones; se elige docs/ como única fuente para evitar duplicación. |
| 011 | 2026-07-09 | Notas de crédito/débito modeladas como `invoices` con `document_type` + `affected_invoice_id` | Reutiliza secuencias NCF, ítems, impuestos y PDF; refleja el modelo DGII (B04/E34, B03/E33). |
| 012 | 2026-07-09 | Frontend **es-DO** por defecto con i18n desde el inicio; pantallas Stitch son inspiración, no espec | Las pantallas mezclan inglés/español; producto para RD debe ser consistente en español. |
| 013 | 2026-07-09 | Design system **Kinetic Enterprise** como preset Tailwind + CSS variables, con dark mode | DESIGN.md provisto por el usuario; tokens centralizados evitan estilos duplicados. |
