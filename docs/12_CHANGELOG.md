# 12 — CHANGELOG

Formato: [Keep a Changelog](https://keepachangelog.com/es/) adaptado. Cada entrada indica archivos relevantes y fase.

## [No publicado]

### 2026-07-09 — Inicio de Fase 0: control de versiones
**Agregado**
- `.gitignore` inicial para dependencias, secretos, artefactos de Laravel y estado local de herramientas.
- `.gitattributes` para finales de línea consistentes y archivos PNG binarios.
- Normalización de espacios finales en las maquetas de referencia, sin cambios funcionales.
- Repositorio Git local en rama `main`, vinculado al remoto autorizado `wailanbrea/sistemawebPosSaas`.

### 2026-07-09 — Inicio de Fase 0: runtime y Laravel
**Agregado**
- PHP 8.3.32 portable en `.tools/php83`, aislado de XAMPP y del `PATH` global.
- Laravel 12.63.0 con dependencias bloqueadas en `composer.lock`.
- Configuración base para MySQL, locale `es_DO` y zona horaria `America/Santo_Domingo` en `.env.example`.
- Base local `omnipos` creada con `utf8mb4` y migraciones estándar de Laravel aplicadas.
- Grafo Graphify actualizado con el código Laravel y reemplazado en el grafo global existente (628 nodos, 617 relaciones).
- Sanctum para tokens API, su migración de tokens personales y ruta API protegida de referencia.
- Pest 3.8, Larastan 3.10, configuración `phpstan.neon` nivel 6 y descubrimiento de módulos backend por manifiesto.
- Vue 3 + TypeScript, Vue Router, Pinia, Axios, Vue I18n, Tailwind/Kinetic Enterprise y PWA con actualización automática.
- Shell inicial de dashboard en español dominicano, con barra de estado operativo para conexión, sucursal y caja.
- Núcleo transversal `app/Core`: envoltorios API estables, `ErrorCode`, `ApiException`, dinero mediante `brick/money`, ULID público y auditoría explícita.
- Migraciones para `users.public_id` y `audit_logs`.

**Validado**
- `php artisan test`: 2 pruebas aprobadas.
- Navegador real mediante Playwright: página inicial de Laravel responde sin errores de consola.
- Pest: 3 pruebas aprobadas, incluyendo emisión de token Sanctum; Pint y Larastan sin errores.
- Playwright: dashboard Vue validado en navegador real, sin errores de consola.
- Pest: 7 pruebas aprobadas para respuestas API, errores, dinero, auditoría y Sanctum; Larastan y Pint sin errores.

**Pendiente**
- Crear y configurar la base de datos MySQL local `omnipos`; no se ha alterado ninguna base existente.

### 2026-07-09 — Planificación inicial (pre-código)
**Agregado**
- Documentación inicial completa `docs/00–15` (visión, arquitectura, esquema BD, API, frontend, módulos, e-CF, impresión, seguridad, testing, deploy, TODO, decisiones, issues, inventario).
- `MASTER_PROMPT_ADDENDUM.md` con requisitos que faltaban en `promt master.txt` (localización fiscal RD: NCF/secuencias/606/607/608/propina legal/multimoneda; e-CF Ley 32-23 con calendario y contingencia; idempotencia; concurrencia de secuencias; ULID; calidad CI/Larastan/Playwright; SaaS suscripciones/límites; design system Kinetic Enterprise; i18n es-DO).
- Análisis de las 12 pantallas de referencia Stitch (`Pantallas del sistema/`).
- Extracción del proyecto en 14 lotes de Graphify (documentación, prompts, HTML e imágenes de referencia).
- Grafo consolidado en `graphify-out/graph.json` e incorporado una sola vez al grafo global como `omnipos-modular-saas` (231 nodos, 344 relaciones; sin duplicados ni referencias colgantes).

**Pendiente**
- Continuar Fase 0 con Sanctum, Vue 3/TypeScript, herramientas de calidad y estructura modular.
