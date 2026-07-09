# 06 — Facturación Electrónica (e-CF DGII, República Dominicana)

> Regla del master prompt: módulo separado, sin lógica electrónica en `InvoiceController`, sin inventar normativa. Lo normativo aquí resumido proviene de investigación (ver fuentes) y **debe validarse contra la documentación técnica oficial de la DGII antes de implementar un provider real**.

## Contexto legal (investigado 2026-07)

- **Ley 32-23** de Facturación Electrónica (promulgada 16-may-2023) hace obligatorio el e-CF para todos los contribuyentes de forma escalonada: grandes nacionales desde may-2024; **grandes locales y medianos: 15-nov-2025**; **pequeños, micro y no clasificados: 15-nov-2026** (con prórrogas puntuales, Aviso 12-25). → Nuestro mercado objetivo (pymes) entra en obligatoriedad justo en la vida útil del sistema: el módulo es estratégico, no opcional.
- Emisión requiere **certificado digital de firma** emitido por entidad acreditada por INDOTEL, y autorización de DGII como emisor electrónico (proceso de certificación con ambiente de pruebas).
- Vías de emisión: sistema propio certificado, **proveedor de servicios de facturación electrónica certificado (PSFE)**, o Facturador Gratuito DGII (limitado).

## Tipos de comprobante

| Tradicional | Electrónico | Uso |
|---|---|---|
| B01 | E31 | Crédito Fiscal (venta a contribuyente con RNC) |
| B02 | E32 | Consumo (consumidor final) |
| B03 | E33 | Nota de Débito |
| B04 | E34 | Nota de Crédito |
| B11 | E41 | Compras (a no registrados) |
| B13 | E43 | Gastos menores |
| B14 | E44 | Regímenes especiales |
| B15 | E45 | Gubernamental |
| B16 | E46 | Exportaciones |
| — | E47 | Pagos al exterior |

- NCF tradicional: secuencia 8 dígitos autorizada por rangos con vencimiento; **e-NCF: 10 dígitos**.
- El sistema modela esto en `document_types` + `ncf_sequences` (rango, actual, vencimiento, alerta de agotamiento) — el consumo de secuencia es transaccional con lock.

## Flujo e-CF (a implementar detrás de la interface)

1. Factura emitida → estado electrónico `pending` → `GenerateElectronicInvoiceJob` construye el **XML según formato DGII** para el tipo (E31/E32/…).
2. Firma con certificado digital (XMLDSig) → `SendElectronicInvoiceJob` envía a DGII (o PSFE) → `track_id`.
3. `CheckElectronicInvoiceStatusJob` (reintentos con backoff) → `accepted` / `rejected` (+ errores detallados) / condicional.
4. **Representación impresa (RI):** ticket/PDF con e-NCF, código de seguridad y **QR de consulta DGII**.
5. B2B: envío/aprobación comercial entre emisores electrónicos cuando aplique.
6. **Contingencia:** si no hay conexión/DGII caída, emitir en modo contingencia y regularizar al reconectar (tabla `electronic_invoice_contingencies`); la política se configura por empresa. Nunca bloquear la venta física por fallo del canal electrónico.

## Diseño en el sistema

- `ElectronicInvoiceProviderInterface` con `generatePayload`, `validateBeforeSend`, `send`, `checkStatus`, `cancel`, `downloadXml`, `downloadPdf`.
- Providers: `NullElectronicInvoiceProvider` (empresa sin e-CF), `MockElectronicInvoiceProvider` (simula aceptación/rechazo para tests y demo), `DgiiDirectProvider` (futuro), `PsfeAdapterProvider` (futuro, para integrar un PSFE certificado).
- Ambientes: `TesteCF` / `CerteCF` (certificación) / `eCF` (producción) — campo `environment` en settings.
- Estados: not_required, pending, queued, generated, sent, accepted, rejected, canceled, error, **contingency**.
- Tablas y jobs: master prompt §19 + [02_DATABASE_SCHEMA.md](02_DATABASE_SCHEMA.md#10-facturación-electrónica-19).
- Credenciales y certificado: cifrados (Laravel Crypt), alerta de vencimiento de certificado.
- Log completo request/response por intento; errores normalizados.

## Criterios de aceptación del módulo

El sistema funciona 100% sin proveedor real (Null/Mock); el fallo electrónico jamás rompe la factura interna; todo intento queda logueado y auditable; reintentos manuales y automáticos desde la pantalla e-CF (como en la pantalla de referencia `facturaci_n_electr_nica_control_de_e_cf`).

## Fuentes

- https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF/
- https://efactrd.com/ley-32-23-facturacion-electronica-rd.html
- https://blog.alegra.com/republica-dominicana/obligatoriedad-de-factura-electronica/
- https://facturandord.com/tipos-de-comprobantes-fiscales-electronicos-dgii/
- https://thefactoryhka.com.do/ley-32-23-y-la-obligatoriedad-de-factura-electronica-fechas-clave-y-todo-lo-que-debes-saber/
