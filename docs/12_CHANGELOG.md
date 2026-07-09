# 12 — CHANGELOG

Formato: [Keep a Changelog](https://keepachangelog.com/es/) adaptado. Cada entrada indica archivos relevantes y fase.

## [No publicado]

### 2026-07-09 — Inicio de Fase 0: control de versiones
**Agregado**
- `.gitignore` inicial para dependencias, secretos, artefactos de Laravel y estado local de herramientas.
- `.gitattributes` para finales de línea consistentes y archivos PNG binarios.
- Normalización de espacios finales en las maquetas de referencia, sin cambios funcionales.
- Repositorio Git local en rama `main`, vinculado al remoto autorizado `wailanbrea/sistemawebPosSaas`.

**Pendiente**
- El scaffolding de Laravel requiere PHP 8.3+; el entorno local disponible es PHP 8.2.12.

### 2026-07-09 — Planificación inicial (pre-código)
**Agregado**
- Documentación inicial completa `docs/00–15` (visión, arquitectura, esquema BD, API, frontend, módulos, e-CF, impresión, seguridad, testing, deploy, TODO, decisiones, issues, inventario).
- `MASTER_PROMPT_ADDENDUM.md` con requisitos que faltaban en `promt master.txt` (localización fiscal RD: NCF/secuencias/606/607/608/propina legal/multimoneda; e-CF Ley 32-23 con calendario y contingencia; idempotencia; concurrencia de secuencias; ULID; calidad CI/Larastan/Playwright; SaaS suscripciones/límites; design system Kinetic Enterprise; i18n es-DO).
- Análisis de las 12 pantallas de referencia Stitch (`Pantallas del sistema/`).
- Extracción del proyecto en 14 lotes de Graphify (documentación, prompts, HTML e imágenes de referencia).
- Grafo consolidado en `graphify-out/graph.json` e incorporado una sola vez al grafo global como `omnipos-modular-saas` (231 nodos, 344 relaciones; sin duplicados ni referencias colgantes).

**Pendiente**
- Confirmación del usuario para iniciar FASE 0 (creación del proyecto Laravel + Vue).
