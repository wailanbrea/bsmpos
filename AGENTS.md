# OmniPOS Modular SaaS — Instrucciones del proyecto

Sistema SaaS multiempresa de POS + facturación + inventario + e-CF (DGII, República Dominicana). Laravel 12 + Vue 3/TS. **Estado: planificación — Fase 0 no iniciada.**

## Fuentes de verdad (en orden de prioridad)

1. Instrucciones directas del usuario.
2. [MASTER_PROMPT_ADDENDUM.md](MASTER_PROMPT_ADDENDUM.md) — mejoras obligatorias (fiscal RD, concurrencia, calidad, SaaS).
3. [promt master.txt](promt%20master.txt) — master prompt original: reglas, stack, módulos, fases, formato de respuesta obligatorio (§31).
4. `Pantallas del sistema/` — 12 pantallas Stitch de inspiración + design system `kinetic_enterprise/DESIGN.md`. Inspiración, no espec.

## Documentación viva

`docs/00–15`. Antes de tocar código: leer [docs/11_TODO_MASTER.md](docs/11_TODO_MASTER.md) (avance) y [docs/13_DECISIONS.md](docs/13_DECISIONS.md). Cada cambio actualiza TODO + CHANGELOG (docs/12) y los docs afectados — ningún módulo está terminado sin documentar ni probar.

## Reglas duras (resumen)

- Dinero: DECIMAL, jamás float. Secuencias NCF/factura con `lockForUpdate()`. Idempotency-Key en ventas/pagos.
- Multi-tenant por `company_id`: cero acceso cruzado; rutas protegidas por auth+company+branch+module+permission.
- Controladores delgados; lógica en Services/Actions; Form Requests; API Resources; Policies; auditoría en acciones sensibles.
- Lógica e-CF solo detrás de `ElectronicInvoiceProviderInterface` (nunca en InvoiceController). No inventar normativa fiscal.
- Módulos activables sin borrar datos históricos. Menú/rutas 100% dinámicos por módulo.
- Frontend es-DO, design system Kinetic Enterprise, touch targets 44px+.
- Verificar cambios de frontend en navegador real (Playwright MCP, skill `depurar-web`) antes de darlos por terminados.
- Responder siguiendo el formato obligatorio del master prompt §31.

## Grafo de conocimiento

`graphify-out/` contiene el grafo del proyecto — usar skill `graphify` para consultas sobre arquitectura/contenido.
