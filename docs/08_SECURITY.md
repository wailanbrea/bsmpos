# 08 — Seguridad y Auditoría

## Autenticación y sesiones

- Laravel Sanctum (tokens por dispositivo/terminal, revocables individualmente).
- Rate limiting estricto en login; lockout progresivo por intentos fallidos.
- Política de contraseñas (mínimo 8, complejidad configurable); hash bcrypt/argon2id.
- 2FA TOTP opcional por usuario (obligatorio configurable para roles admin).
- PIN corto de cajero para cambio rápido de usuario en el POS (encima de sesión de terminal autenticada, no la sustituye).

## Autorización en capas

`auth` → `company` → `branch` → `module:<code>` → `permission:<code>` → Policies. Ver [01_ARCHITECTURE.md](01_ARCHITECTURE.md). **Cero acceso cruzado entre empresas** (global scope + policy + tests dedicados de tenant isolation).

## Datos

- Credenciales de e-CF, certificados y secretos: cifrados con Laravel Crypt; jamás en respuestas API ni logs.
- `.env` fuera de VCS; `.env.example` mantenido.
- Validación de entrada exclusivamente vía Form Requests; salida vía Resources (nunca `->toArray()` del modelo crudo).
- Uploads (logos, imágenes, fotos de taller): validación mime/tamaño, almacenamiento fuera de public con URLs firmadas.
- Backups automáticos de BD programados (spatie/laravel-backup), retención configurable.

## Auditoría (módulo Audit)

Registrar con usuario, IP, user-agent, valores antes/después: login/logout (y fallidos), CRUD usuarios/roles/permisos, toggle de módulos, cambios de precio y costo, descuentos sobre umbral, anulaciones, cambios fiscales (secuencias NCF, impuestos, settings e-CF), apertura/cierre/movimientos de caja y diferencias, ajustes de inventario y mermas, reimpresiones, ventas de productos vencidos/próximos a vencer autorizadas, errores críticos.

Pantallas: lista con filtros (usuario, módulo, acción, fecha) + detalle con diff.

## Acciones sensibles con autorización elevada

Anular factura, vender producto vencido, descuento mayor a X%, abrir caja ya cerrada → requieren permiso específico y (configurable) PIN/credencial de supervisor, todo auditado.
