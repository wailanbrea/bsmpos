# OmniPOS Modular SaaS — Instrucciones del proyecto

Sistema SaaS multiempresa de POS + facturación + inventario + e-CF (DGII, República Dominicana). Laravel 12 + Vue 3/TS. **Estado: Fases 1–13 completas; Fase 14 (seguridad/deploy) en curso.** El detalle vivo del avance está en [docs/11_TODO_MASTER.md](docs/11_TODO_MASTER.md).

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

## Cómo trabajar sin perderte (Graphify + Playwright)

Tienes dos herramientas de orientación: el **grafo de conocimiento (Graphify)** para
SABER DÓNDE MIRAR, y **Playwright MCP** para VER SI FUNCIONA. Úsalas en ese orden:
orienta → localiza → cambia → verifica.

**Regla de oro:** NUNCA hagas `grep` a ciegas sobre todo el repo como primer paso.
Primero consulta el grafo. Grep es solo para el último tramo, cuando ya sabes el módulo.
Si te encuentras abriendo >5 archivos "por si acaso", detente y haz una consulta al grafo.

### 1) Orientarte con Graphify (por bash, NO es un MCP)
El grafo vive en `graphify-out/graph.json` (+ `GRAPH_REPORT.md`). Se usa por CLI:
- `graphify query "<pregunta en lenguaje natural>"` — ej: `graphify query "cómo se reserva el NCF al facturar una orden"`.
- `graphify path "PosPage" "NcfSequenceService"` — camino entre dos conceptos.
- `graphify explain "ElectronicInvoiceService"` — qué es y con qué se conecta.
Lee la respuesta como un MAPA (nodos = clases/archivos, sus vecinos, su comunidad/subsistema)
y ve directo a esos archivos. Al terminar un cambio de código, refresca el grafo con el
merge AST incremental (barato, sin LLM): detectar cambios → `graphify.extract` sobre los
`.php/.ts` nuevos → `build_merge` → reconstruir.

### 2) Verificar en navegador con Playwright MCP (herramientas `browser_*`)
Regla del proyecto: TODO cambio de frontend/flujo se confirma en navegador real antes de
cerrarlo. Si las tools `browser_*` (mcp) no aparecen, el MCP no está cargado: revisa
`~/.config/opencode/opencode.json` (bloque `mcp.playwright`) y reinicia opencode.
- Levanta el server en segundo plano, `browser_navigate` a la URL y usa **`browser_snapshot`**
  (árbol de accesibilidad con refs `eXX`), NO screenshots, para leer e interactuar.
- Interactúa por `ref` del snapshot (`browser_click`, `browser_type`, `browser_fill_form`).
  Si un `ref` "no existe", vuelve a hacer `browser_snapshot` (el DOM cambió); no reintentes el viejo.
- Si el selector falla o la UI es dinámica, usa `browser_evaluate` con JS: rellena inputs
  disparando `input`/`change`, o llama al API con `fetch` usando el token de `localStorage`
  para montar datos de prueba rápido (crear un producto y una venta para poblar un reporte).
- Diagnostica SIEMPRE con `browser_console_messages` (nivel error) y `browser_network_requests`
  / `browser_network_request` (mira el `response-body` de la llamada que falló): 401 = token/headers;
  422 = validación (lee `error.details`); 403 = permiso o módulo inactivo.
- No te fíes de "parece que funcionó": confirma el efecto (fila nueva, estado del pedido, NCF
  emitido) y consola sin errores. **Nunca digas que navegaste sin una `tool_call` real.**

### Bucle de trabajo
1) `graphify query` para ubicar el subsistema y sus archivos.
2) Lee solo esos archivos; cambia con el estilo del código vecino.
3) Corre suite/estático del área tocada (Pest, Pint, Larastan, vue-tsc, ESLint, Prettier, build).
4) `browser_navigate` + `browser_snapshot` para verificar el flujo real; diagnostica con consola/red.
5) Refresca el grafo y actualiza TODO + CHANGELOG + docs afectados.
