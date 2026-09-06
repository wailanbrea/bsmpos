# 07 — Impresión

## Alcance (master prompt §20)

Documentos: ticket 58/80/88 mm, factura A4, comanda (cocina/barra), precuenta, cierre de caja, reporte diario, reimpresión (auditada).

## Estrategia por etapas

1. **Impresión por navegador:** plantillas HTML/CSS específicas por ancho (58/80/88mm con `@media print`) y vista previa A4.
2. **Preparado ESC/POS:** capa `TicketBuilder` que genera una representación intermedia (líneas, alineación, negrita, QR, corte) renderizable como HTML **o** bytes ESC/POS (mike42/escpos-php), sin acoplar plantillas al medio.
3. **Agente Windows implementado:** Kotlin/Ktor en loopback 8765; recepción de texto por `/api/print`, envío ESC/POS por Spooler o COM. Android continúa pendiente.

## Ruta de impresión del POS (2026-09-05)

- Los tickets térmicos usan el agente cuando está conectado o hay una impresora local seleccionada. Se conserva esa selección aunque el monitor de conexión cambie a desconectado.
- Un fallo del agente o de `/invoices/{id}/print/text` se muestra dentro del comprobante. Nunca provoca un salto automático a HTML/navegador. A4 conserva la impresión de navegador, al igual que un ticket sin agente conectado ni impresora local seleccionada.
- El botón se deshabilita mientras se envía el ticket para evitar clics simultáneos. Esto no sustituye la idempotencia del agente ni confirma salida física del papel.
- Diagnóstico 2C-P58-C: Windows enumeró COM4, pero el agente instalado devolvía la impresora Bluetooth con `port: null`. El agente ahora usa un escaneo COM/PnP separado con timeout acotado, conserva el fallback serial aunque Bluetooth tarde o falle, extrae puertos desde nombres PnP como `2C-P58-C (COM4)` y escribe mediante `\\.\COMx`. Falta validar impresión física; estar conectado al agente no confirma conexión con la impresora.
- Validación local: cuatro regresiones Vitest y navegador real con API/agente simulados (error visible, sin petición HTML ni consola con errores). ESLint del área y build correctos. Typecheck global bloqueado por dos errores de nulabilidad existentes en `ConfigurationPage.vue`.
- Desplegado el 2026-09-06 (31d77cd): build `app-BxHUcp4B.js` servido con HTTP 200 y POS recargado en navegador de producción con agente conectado. La impresora Bluetooth no apareció en la lista actual del agente. No se reinstaló el agente porque este cambio solo modifica el frontend.

## Configuración

Por sucursal/terminal: impresora de caja, cocina, barra; formato por documento; nº de copias; plantilla (logo, encabezado fiscal, pie, mensaje). Enrutamiento de comandas por categoría de producto → impresora (cocina vs barra).

## Requisitos fiscales del ticket/factura RD

RNC y razón social del emisor, NCF/e-NCF y tipo, fecha, ITBIS desglosado, propina legal si aplica; para e-CF: QR de consulta DGII + código de seguridad (ver [06_ELECTRONIC_INVOICING.md](06_ELECTRONIC_INVOICING.md)).
