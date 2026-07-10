# 04 — Estructura Frontend

## Estructura (master prompt §5)

```
resources/js/
  app.ts
  router/            # rutas raíz + guards (requiresAuth, requiresPermission, requiresModule)
  stores/            # auth, company, module, ui (globales)
  layouts/           # AuthLayout, DashboardLayout, PosLayout, PublicDisplayLayout
  modules/
    auth/ dashboard/ companies/ branches/ users/ module-manager/ settings/
    customers/ products/ services/ inventory/ warehouses/ purchases/ suppliers/
    pos/ orders/ invoices/ electronic-invoices/ payments/ cash-register/
    tables/ delivery/ kitchen/ digital-menu/ appointments/ vehicles/ work-orders/
    reports/ printers/ audit/
    # cada uno: pages/ components/ services/ store.ts types.ts routes.ts
  components/        # ui/ forms/ tables/ modals/ feedback/ touch/
  composables/       # useMoney, useModules, usePermissions, usePrinter, useOffline...
  services/          # apiClient (axios + interceptores), echo, indexeddb
  types/             # tipos compartidos (ApiResponse, Money, etc.)
  utils/             # formato moneda RD$, fechas, validación RNC/cédula
```

## Design system: "Kinetic Enterprise"

Fuente: `Pantallas del sistema/stitch_omnipos_modular_saas/kinetic_enterprise/DESIGN.md`. Resumen operativo:

- **Tipografía:** Inter única. Estilo `numeric-pos` (28px/700/letter-spacing 0.05em) para precios y keypad.
- **Colores semánticos:** Primary indigo `#4F46E5` (marca/nav/acciones admin), Success verde (Pagado/botón Pagar), Danger rojo (ocupada/error/sin stock), Warning ámbar (pendiente/stock bajo), neutros fríos `#F9FAFB`–`#111827`.
- **Táctil:** touch target mínimo 44px, botones de cobro 56px; feedback de presión (scale 98% + quitar sombra).
- **Grid:** sidebar fija 280px en admin + contenido fluido; móvil bottom-bar; espaciado múltiplos de 8px.
- **Formas:** radio 8px estándar, 16px modales, pills solo para chips de estado (tinte suave + texto de alto contraste).
- **Elevación:** tonal + sombras suaves; modales con backdrop 40% + blur.

Estos tokens se implementan como **CSS variables + preset de Tailwind** (`tailwind.config` → theme extraído del DESIGN.md), con **modo oscuro** desde el inicio (`data-theme`).

## Pantallas de referencia (Stitch)

12 pantallas HTML+PNG en `Pantallas del sistema/stitch_omnipos_modular_saas/`: onboarding (tipo de negocio, activación de módulos), dashboard retail, POS touch restaurante, pago multimoneda/mixto, perfil de cliente con crédito, inventario avanzado (lotes), entradas de mercancía, kardex, facturación electrónica e-CF, configuración de módulos, reporte de ventas.

**Son inspiración, no ley:** al implementarlas se debe mejorar accesibilidad (contraste, focus, aria), estados vacíos/carga/error, i18n (textos mezclan inglés/español — el sistema será **es-DO** por defecto con arquitectura i18n), y responsive real.

## Implementado en Fase 1

- `modules/audit/pages/AuditLogPage.vue`: ruta `/auditoria` protegida por autenticación y contexto tenant. Incluye filtros por módulo y fecha, carga skeleton, error recuperable, estado vacío, paginación y panel de detalle. La autorización definitiva se mantiene en el API mediante `audit.view`.
- `modules/access/pages/RoleManagementPage.vue`: ruta `/roles` para listar, crear, editar y desactivar roles, con matriz de permisos, estados de carga/error y bloqueo visual de roles del sistema.
- `modules/access/pages/UserManagementPage.vue`: ruta `/usuarios` para provisionar cuentas existentes y actualizar, de forma aislada por empresa, sus sucursales, sucursal predeterminada y roles. Muestra carga, error recuperable, estado vacío y deshabilita el propietario, cuya administración está protegida también por el API.
- `modules/company/pages/BranchManagementPage.vue`: ruta `/sucursales` para listar, crear y editar puntos de operación. Incluye carga, vacío, error recuperable y bloquea visualmente la desactivación de la sucursal principal, regla que el API también aplica.

## Reglas frontend

- Menú lateral 100% dinámico: módulos activos + permisos + sucursal + plan (`useModuleStore.canUse()`).
- Nada de reglas de negocio fiscales en el front: el backend decide; el front muestra.
- Dinero: siempre formatear desde centavos/string decimal del API (`useMoney`), nunca aritmética float en JS para totales — los totales los calcula el backend; el POS solo pre-visualiza con `decimal.js`.
- Carrito del POS persistido en IndexedDB (no se pierde al refrescar).
- PWA: instalable, assets cacheados, indicador online/offline, catálogo cacheado.
- Componentes de `components/ui` documentados y reutilizados — prohibido duplicar tablas/modales por módulo.
