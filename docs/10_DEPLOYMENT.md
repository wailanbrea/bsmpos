# 10 — Despliegue

> Se detalla en Fase 14. Lineamientos iniciales:

- **Entorno dev:** XAMPP local (Windows) — PHP 8.3, MySQL. `php artisan serve` + `npm run dev` (Vite).
- **Producción objetivo:** VPS Linux (Nginx + PHP-FPM 8.3 + MySQL 8 + Redis para colas/cache) o plataforma tipo Forge/Ploi. HTTPS obligatorio.
- Colas: `queue:work` supervisado (Supervisor/systemd); Scheduler vía cron `schedule:run`.
- Optimización: `config:cache`, `route:cache`, `view:cache`, `npm run build`, OPcache.
- Backups BD diarios + retención; almacenamiento de XML/PDF e-CF con respaldo (obligación de conservación fiscal).
- CI (GitHub Actions): pint + larastan + pest + eslint + vue-tsc + vitest + build en cada push; Playwright E2E en pipeline nightly/pre-release.
- `.env.example` completo y documentado; secretos solo por variables de entorno.
- Migraciones con `--force` en deploy; nunca `migrate:fresh` en producción.
