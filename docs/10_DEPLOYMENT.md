# 10 — Despliegue

## Entornos

- **Desarrollo (Windows/XAMPP):** PHP 8.3 portable en `.tools/php83` (aislado de XAMPP), MySQL local `omnipos`. Servir con `php artisan serve` + `npm run dev`.
- **Producción objetivo:** Linux (Ubuntu 22.04+), Nginx + PHP-FPM 8.3 + MySQL 8 + Redis (colas/cache), HTTPS obligatorio. Plataformas tipo Forge/Ploi simplifican el aprovisionamiento.

## Requisitos del servidor

- PHP 8.3 con extensiones: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
- Composer 2, Node 20+ (solo para construir assets), MySQL 8 / MariaDB 10.6+, Redis 6+.
- Supervisor (o systemd) para el worker de colas; cron para el scheduler.

## Variables de entorno críticas (`.env`)

```
APP_ENV=production
APP_DEBUG=false            # NUNCA true en producción (expone trazas)
APP_KEY=base64:...         # generar con `php artisan key:generate`
APP_URL=https://tu-dominio
APP_TIMEZONE=America/Santo_Domingo
APP_LOCALE=es_DO

DB_CONNECTION=mysql        # BD dedicada por instalación
QUEUE_CONNECTION=redis     # o database; obligatorio un worker corriendo (jobs e-CF)
CACHE_STORE=redis          # el lock de idempotencia usa el cache store
SESSION_DRIVER=redis
FILESYSTEM_DISK=local      # o s3 para XML/PDF fiscales con retención

MAIL_MAILER=smtp           # notificaciones y recuperación
```

`APP_KEY` cifra los secretos en reposo (credenciales e-CF, secreto 2FA de usuarios). **Rotarla invalida esos secretos**: hacerlo solo con un plan de re-cifrado.

## Pasos de despliegue

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart      # que el worker recoja el código nuevo
```

Nunca `migrate:fresh` en producción. Las migraciones son aditivas e idempotentes; se aplican con `--force`.

## Colas y scheduler

La facturación electrónica se transmite en un **job asíncrono** (`SendElectronicInvoiceJob`) con reintentos y backoff, así que un worker es obligatorio si el módulo e-CF está activo:

```
# Supervisor: /etc/supervisor/conf.d/omnipos-worker.conf
[program:omnipos-worker]
command=php /var/www/omnipos/artisan queue:work --sleep=3 --tries=5 --max-time=3600
numprocs=2
autostart=true
autorestart=true
stopwaitsecs=3600
```

```
# Cron (scheduler)
* * * * * cd /var/www/omnipos && php artisan schedule:run >> /dev/null 2>&1
```

## Backups

- BD diaria + retención (mínimo 30 días); conservar además los XML/PDF de e-CF (obligación fiscal de conservación).
- Verificar restauraciones periódicamente; un backup no probado no es un backup.

## Optimización de producción

- OPcache habilitado; `config:cache`/`route:cache`/`view:cache` tras cada deploy.
- Índices ya definidos en migraciones (`company_id`, `company_id+ncf` único, `product_id+expiration_date` para FEFO, etc.).
- Assets con hash y Service Worker (PWA) generados por `npm run build`.

## CI (recomendado, GitHub Actions)

En cada push: `pint --test`, `phpstan`, `pest`, `vue-tsc`, `eslint`, `prettier --check`, `vitest`, `vite build`. Playwright E2E en pipeline nightly/pre-release contra un entorno efímero. Ver [09_TESTING.md](09_TESTING.md).

## Checklist de puesta en marcha

- [ ] `APP_DEBUG=false`, `APP_KEY` generada, HTTPS forzado.
- [ ] BD dedicada migrada; seeders de catálogo (`ModuleSystemSeeder`, `ConfigurationSeeder`) ejecutados.
- [ ] Worker de colas y cron activos y supervisados.
- [ ] Backups automáticos verificados.
- [ ] Certificado e-CF (si aplica) cargado y ambiente correcto (test/cert/prod) por empresa.
- [ ] Rate limits y 2FA revisados (ver [08_SECURITY.md](08_SECURITY.md)).
