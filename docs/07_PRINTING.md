# 07 — Impresión

## Alcance (master prompt §20)

Documentos: ticket 58/80/88 mm, factura A4, comanda (cocina/barra), precuenta, cierre de caja, reporte diario, reimpresión (auditada).

## Estrategia por etapas

1. **Fase 11 (ahora):** impresión por navegador — plantillas HTML/CSS específicas por ancho (58/80/88mm con `@media print`) y PDF A4 (dompdf/browsershot). Vista previa antes de imprimir.
2. **Preparado ESC/POS:** capa `TicketBuilder` que genera una representación intermedia (líneas, alineación, negrita, QR, corte) renderizable como HTML **o** bytes ESC/POS (mike42/escpos-php), sin acoplar plantillas al medio.
3. **Futuro:** agente local (WebSocket/HTTP en LAN) para impresión directa a impresoras de red/USB, y app Android Kotlin (Bluetooth/USB). No desarrollar ahora.

## Configuración

Por sucursal/terminal: impresora de caja, cocina, barra; formato por documento; nº de copias; plantilla (logo, encabezado fiscal, pie, mensaje). Enrutamiento de comandas por categoría de producto → impresora (cocina vs barra).

## Requisitos fiscales del ticket/factura RD

RNC y razón social del emisor, NCF/e-NCF y tipo, fecha, ITBIS desglosado, propina legal si aplica; para e-CF: QR de consulta DGII + código de seguridad (ver [06_ELECTRONIC_INVOICING.md](06_ELECTRONIC_INVOICING.md)).
