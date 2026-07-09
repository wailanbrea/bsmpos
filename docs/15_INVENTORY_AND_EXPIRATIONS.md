# 15 — Inventario, Lotes y Vencimientos

> Especificación de referencia (master prompt §15). Se actualiza con cada cambio que afecte inventario, lotes o vencimientos.

## Modelo

- Stock por empresa → sucursal → almacén → ubicación interna.
- `inventory_stock` como snapshot agregado (lecturas rápidas del POS); `inventory_batches` + `inventory_movements` como verdad histórica. Toda mutación de stock ocurre en `InventoryService` dentro de transacción y genera movimiento; el snapshot se recalcula/incrementa atómicamente.
- Lotes: batch_number, fechas fabricación/vencimiento, cantidades inicial/disponible, costos, estado (`active, near_expiration, expired, depleted, blocked, returned, damaged`).
- Seriales para productos que lo requieren (electrónica, repuestos con garantía).
- Unidades con conversión (caja de 24 → unidad).

## Métodos de salida (por producto, `outgoing_method`)

- **FEFO** (First Expired, First Out) — descuenta el lote con vencimiento más próximo; empates → el más antiguo.
- **FIFO** — descuenta el lote de entrada más antigua.
- **Costo promedio** — sin lote; recalcula avg_cost en cada entrada.
- **Manual** — el cajero/almacenista elige lote (auditado).

Una venta puede consumir múltiples lotes parciales; cada consumo genera su movimiento con costo del lote (COGS correcto).

## Reglas de venta con vencimiento (configurables por empresa)

1. **Vencido:** bloquear / permitir con autorización de supervisor / permitir con advertencia — siempre auditado.
2. **Próximo a vencer** (`expiration_alert_days` por producto): alerta visible, venta permitida, auditoría, descuento sugerido opcional.
3. Producto `requires_batch` sin lote válido → venta bloqueada.
4. Anulación de factura → reverso de movimientos a los mismos lotes.

## Alertas (job diario + eventos en tiempo real)

Stock bajo/sin stock/exceso (vs min/max/reorden), próximos a vencer, vencidos, rotación lenta, margen bajo, precio < costo, cambio de costo relevante, lote sin vencimiento cuando el producto lo exige, secuencia NCF por agotarse (fiscal, vive en Invoice pero se lista aquí como alerta del dashboard).

## Kardex

Por producto (+filtros almacén/lote/fecha/tipo): fecha, tipo movimiento, documento referencia, entrada, salida, balance, costo unitario, costo total. Exportable. Pantalla de referencia: `kardex_historial_de_movimientos_de_producto`.

## Reportes

Stock actual/bajo/sin stock, próximos a vencer, vencidos, por lote, por proveedor, movimientos, transferencias, mermas, pérdidas por vencimiento, compras, rotación, margen, precio<costo, valor total, por almacén/sucursal.
