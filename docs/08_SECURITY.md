# 08 — Seguridad y Auditoría

## Autenticación y sesiones

- Laravel Sanctum (tokens por dispositivo/terminal, revocables individualmente).
- Rate limiting estricto en login; lockout progresivo por intentos fallidos.
- Política de contraseñas (mínimo 8, complejidad configurable); hash bcrypt/argon2id.
- 2FA TOTP opcional por usuario (obligatorio configurable para roles admin).
- PIN corto de cajero para cambio rápido de usuario en el POS (encima de sesión de terminal autenticada, no la sustituye).

## Autorización en capas

`auth` → `company` → `branch` → `module:<code>` → `permission:<code>` → Policies. Ver [01_ARCHITECTURE.md](01_ARCHITECTURE.md). **Cero acceso cruzado entre empresas** (global scope + policy + tests dedicados de tenant isolation).

### Implementado en Fase 1

- `POST /api/v1/auth/register` usa contraseña de 12+ caracteres con mayúsculas, minúsculas, números y símbolo.
- `POST /api/v1/auth/login` limita a 5 intentos por minuto por email/IP; el registro limita a 10 solicitudes por hora por IP.
- Los tokens de Sanctum son por dispositivo; logout revoca solo el token presentado. Registro, login, login fallido, bloqueo y logout quedan auditados.
- Policies de compañía, sucursal, rol y auditoría se registran explícitamente y se invocan desde los controladores. El middleware `permission:` y las Policies usan la misma comprobación de membresía, propietario y permiso por compañía.
- La evaluación de roles para Policies ignora el global scope de consulta y filtra explícitamente por la compañía del recurso, evitando que el contexto de una solicitud altere una decisión de autorización sobre otro recurso.

### Implementado en Fase 1: bitácora consultable

- `GET /api/v1/audit-logs` y `GET /api/v1/audit-logs/{publicId}` requieren `audit.view` y contexto de compañía; las consultas aplican el filtro de tenant explícitamente.
- Los eventos se exponen mediante ULID público. Los campos cuyo nombre contiene `password`, `token`, `secret`, `certificate` o `private_key` se redactan recursivamente antes de responder.
- Los índices compuestos de la bitácora cubren los filtros operativos compañía/usuario/fecha y compañía/acción/fecha.

### Implementado en Fase 1: gestión de sucursales

- Las rutas de sucursales exigen compañía seleccionada y `company.manage`; el ULID de ruta siempre se resuelve dentro del tenant actual.
- La sucursal principal no se puede desactivar y no existe borrado físico por API, preservando el contexto operativo y trazabilidad histórica.

### Implementado en Fase 1: acceso de usuarios

- Solo `access.users.manage` puede conceder o modificar acceso de cuentas existentes; cada rol y sucursal se resuelve dentro de la compañía seleccionada.
- La sincronización afecta exclusivamente pivotes de la compañía activa, por lo que modificar usuarios en un tenant no desprende sus sucursales ni roles de otros tenants.
- El propietario queda fuera de esta mutación y los roles del sistema no se pueden asignar por este endpoint, evitando pérdida o escalamiento accidental de control administrativo. Cada provisión o cambio se audita con ULIDs públicos.

### Implementado en Fase 1: roles y permisos

- El catálogo de permisos expone códigos estables y no claves internas; los roles se crean y actualizan con esos códigos.
- Los roles del sistema, incluido el propietario, no se pueden modificar ni desactivar. Un rol personalizado asignado tampoco se puede desactivar hasta reasignar sus usuarios.
- Alta, actualización y desactivación de roles dejan registros de auditoría con permisos antes/después.

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
