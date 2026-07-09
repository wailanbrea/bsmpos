# 02 — Esquema Inicial de Base de Datos

> Borrador de planificación. Se actualiza con cada migración real. Convenciones: dinero `DECIMAL(14,2)`, cantidades `DECIMAL(14,4)`, `company_id` en toda tabla multiempresa, `branch_id` cuando aplique, timestamps en todas, soft deletes en entidades de negocio, `public_id` ULID en entidades expuestas.

## 1. Núcleo SaaS

- **companies** — id, public_id, name (comercial), legal_name, tax_id_type (RNC/cédula/CIF/NIF), tax_id, phone, whatsapp, email, address, logo_path, business_type_id, timezone, currency_code, is_active, suspended_at, trial_ends_at
- **branches** — id, company_id, name, code, phone, address, is_main, is_active
- **users** — id, name, email, password, phone, is_super_admin, two_factor_secret (nullable), last_login_at, is_active
- **company_user** — company_id, user_id, is_owner, default_branch_id
- **branch_user** — branch_id, user_id (sucursales permitidas)
- **roles** — id, company_id (nullable = plantilla del sistema), name, description
- **permissions** — id, code, name, module_code, description
- **role_user** — role_id, user_id, company_id
- **permission_role** — permission_id, role_id
- **settings** — id, company_id, branch_id (nullable), group, key, value (json), unique(company,branch,group,key)
- **audit_logs** — id, company_id, branch_id, user_id, action, auditable_type, auditable_id, old_values (json), new_values (json), ip, user_agent, created_at

## 2. Sistema de módulos y planes

Según master prompt §8: **business_types**, **system_modules**, **module_dependencies**, **business_type_modules**, **company_modules**, **branch_modules**, **subscription_plans** (+ límites: max_branches, max_users, max_invoices_month), **plan_modules**, **module_audit_logs**, y adicional **company_subscriptions** — id, company_id, plan_id, status (trial/active/past_due/suspended/canceled), current_period_start/end, canceled_at.

## 3. Configuración fiscal y monedas

- **currencies** — id, code, name, symbol, decimals
- **company_currencies** — company_id, currency_code, is_default, is_active
- **exchange_rates** — id, company_id, currency_code, rate DECIMAL(14,6), effective_date, created_by_user_id
- **taxes** — id, company_id, name (ITBIS 18%, ITBIS 16%, Exento, Propina 10%), code, rate DECIMAL(7,4), type (percentage/fixed), is_inclusive, applies_to (product/service/both), is_active
- **document_types** — catálogo NCF/e-CF: code (B01, B02, B04, B14, B15, E31, E32, E33, E34, E44, E45), name, is_electronic, is_fiscal
- **ncf_sequences** — id, company_id, branch_id (nullable), document_type_code, series, current_number, start_number, end_number, expires_at, alert_threshold, is_active — *consumo con lockForUpdate*
- **payment_methods** — id, company_id, name, code (cash/card/transfer/credit/delivery_app/other), requires_reference, is_active, sort_order

## 4. Clientes

**customers** (kind persona/empresa/genérico, tax_id_type, tax_id, name, phone, whatsapp, email, credit_limit, credit_days, balance, is_generic), **customer_addresses**, **customer_contacts**, **customer_credit_accounts** (movimientos de crédito/abonos).

## 5. Productos y servicios

**categories**, **products** (campos del master prompt §14 + unit_id, brand nullable), **product_images**, **product_variants**, **product_modifiers**, **product_modifier_options**, **product_combos**, **units** (unidad, caja, libra… con factor de conversión), **product_units**, **services**, **service_categories**, **product_inventory_settings** (§15).

## 6. Inventario avanzado (§15 del master prompt)

**warehouses**, **warehouse_locations**, **inventory_stock** (agregado por producto/almacén: quantity, avg_cost, last_cost — snapshot para lecturas rápidas), **inventory_batches**, **inventory_movements** (tipos: purchase_in, sale_out, adjustment_in/out, transfer_in/out, waste, return_in/out, initial), **inventory_alerts**, **inventory_transfers**, **inventory_transfer_items**, **serial_numbers**, **suppliers**, **purchases**, **purchase_items**.

## 7. POS / Órdenes

- **orders** — id, public_id, company_id, branch_id, customer_id, user_id, order_number, type (dine_in/takeout/delivery/quote/direct), status (draft/open/sent_to_kitchen/pending_payment/paid/canceled), table_id (nullable), subtotal, discount_total, tax_total, tip_total, total, notes
- **order_items** — order_id, product_id/service_id, description, quantity, unit_price, discount, tax_id, tax_amount, total, kitchen_status (nullable)
- **order_item_modifiers**, **order_item_notes**

## 8. Caja y pagos

**cash_registers** (por sucursal), **cash_sessions** (opened_by, opening_amount, expected_amount, counted_amount, difference, status, opened_at/closed_at), **cash_movements** (in/out, concepto, monto, referencia), **cash_closings** (arqueo por denominación json), **payments** — id, company_id, branch_id, invoice_id/order_id, cash_session_id, payment_method_id, currency_code, exchange_rate, amount, amount_in_base, reference, tip_amount, change_amount, status, paid_at.

## 9. Facturación

- **invoices** — todos los campos del master prompt §18 + ncf (NCF/e-NCF asignado), ncf_expires_at, document_type_code, tip_total, idempotency_key unique
- **invoice_items**, **invoice_taxes**, **invoice_payments**, **invoice_sequences** (numeración interna por sucursal)
- **credit_notes / debit_notes** → modeladas como invoices con document_type (B04/E34, E33) y `affected_invoice_id` + `affected_ncf`

## 10. Facturación electrónica (§19)

**electronic_invoice_settings** (+ certificate_expires_at, delegate_rnc para PSFE), **electronic_invoices** (+ e_ncf, security_code, signed_xml_hash), **electronic_invoice_logs**, **electronic_invoice_errors**, **electronic_invoice_contingencies** — registro de facturación en contingencia offline y su regularización.

## 11. Módulos verticales

- Restaurante: **areas**, **tables** (status libre/ocupada/reservada), **table_sessions**, **kitchen_tickets**, **kitchen_ticket_items**, **deliveries** (repartidor, plataforma, estado, liquidación), **digital_menu_screens**, **digital_menu_items**
- Barbería/Salón: **employees**, **appointments**, **appointment_services**, **employee_commissions**
- Taller: **vehicles**, **work_orders**, **work_order_items** (services/parts/labor), **work_order_photos**
- Recetas: **recipes**, **recipe_ingredients** (consumo de inventario al vender)

## 12. Reportes DGII (addendum)

- **dgii_reports** — id, company_id, type (606/607/608), period (YYYYMM), status, file_path, generated_at
- Vistas/consultas derivadas de purchases (606), invoices (607), invoices anuladas (608)

## Índices mínimos obligatorios

`(company_id)`, `(company_id, branch_id)` en tablas tenant; `(company_id, ncf)` unique en invoices; `(product_id, expiration_date)` en inventory_batches (FEFO); `(product_id, created_at)` en inventory_movements (kardex); `barcode`, `sku` unique por company en products; `idempotency_key` unique.
