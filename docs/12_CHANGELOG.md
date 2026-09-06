# 12 — CHANGELOG

Formato: [Keep a Changelog](https://keepachangelog.com/es/) adaptado. Cada entrada indica archivos relevantes y fase.

## [No publicado]

### 2026-09-05 — Branding: Cambio de Nombre e Identidad del Sistema a "BSM-POS"
**Modificado**
- **Identidad de Marca y Aplicación:**
  - Actualización oficial del nombre comercial del sistema de "OmniPOS" a **"BSM-POS"**.
  - Configuración backend: `.env`, `.env.example` y `config/app.php` actualizados con `APP_NAME=BSM-POS`.
  - Título de la aplicación web y PWA: `resources/views/welcome.blade.php` y `vite.config.js` (`name: 'BSM-POS'`, `short_name: 'BSM-POS'`).
  - Layout y navegación principal (`AppLayout.vue`): cabecera, logotipo del sidebar (`BSM-POS` con badge de comprobantes `e-CF`), nombre de empresa por defecto `BSM-POS Enterprise`.
  - Punto de Venta POS (`PosPage.vue`): título superior `BSM-POS · [Nombre de Caja]`, pie de comprobante fiscal impreso `BSM-POS Modular SaaS`, modal de instalación de hardware `BSM-POS Windows Agent`.
  - Módulo de Hardware (`AgentTerminalsPage.vue` y `AgentTerminalController.php`): tarjetas de terminales, botones de descarga directa de `BSM-POS-Agent.zip` y control de servicio de impresión térmica local.
  - Autenticación y Cuentas (`LoginPage.vue`, `RegisterPage.vue`, `ContextPage.vue`, `TwoFactorController.php`): branding unificado, textos de registro y código QR de autenticación 2FA TOTP con emisor `BSM-POS`.
  - Generador de Facturas e Impresión Térmica ESC/POS (`PrintController.php`): tickets ESC/POS nativos, tickets de texto plano y facturas HTML con pie de comprobante `BSM-POS Modular SaaS`.
- **Pruebas y Calidad:**
  - Pruebas E2E de Playwright (`tests/e2e/pos-sale.spec.ts`, `tests/e2e/pos-no-stock.spec.ts`) sincronizadas con el nuevo encabezado `BSM-POS · Caja Demo`.
  - 180 pruebas Pest pasando, 0 errores en Larastan (nivel 8), 100% código limpio en Pint y verificación visual en navegador con Playwright MCP.

### 2026-09-05 — Hardware: Modal Interactivo de Instalación en 1 Clic del OmniPOS Windows Agent
**Agregado**
- **Modal Interactivo del Agente en POS (`PosPage.vue`):**
  - La insignia de estado `desktop_windows` en el encabezado del terminal POS ahora es un botón interactivo y reactivo.
  - Cuando el agente está desconectado (`Instalar Agente`), parpadea en color ámbar y al hacer clic despliega un modal explicativo detallado con el estado en tiempo real, botón de reintento (`refresh`) y guía de instalación visual en 3 pasos.
  - Botón de descarga directa del paquete `OmniPOS-Agent.zip` (21 MB).
  - Detección automática en vivo: cuando el usuario ejecuta el instalador en su máquina, el POS detecta el agente en `http://127.0.0.1:8765/api/status` y conmuta la insignia a color verde (`Agente Windows`) automáticamente sin recargar la página.
- **Botón de Descarga en Panel de Terminales (`AgentTerminalsPage.vue`):**
  - Botón "Descargar Agente Windows" en el encabezado principal de hardware y en la tarjeta de estado del agente local.
- **Distribución y Endpoint de Descarga:**
  - Generación del paquete comprimido `public/downloads/omnipos-windows-agent.zip` incluyendo ejecutables `bin/`, librerías Ktor/Netty `lib/`, wrapper de servicio Windows `service/OmniPOSAgent.exe`, `instalar-servicio.bat` con elevación de permisos, `iniciar-sin-servicio.bat` y `LEEME-INSTRUCCIONES.txt`.
  - Endpoint `GET /api/v1/agent-terminals/download` en `AgentTerminalController.php`.
- **Calidad:** 180 pruebas Pest pasando, 0 errores en Larastan, 100% formateado con Pint y verificado en navegador con Playwright MCP.

### 2026-09-05 — POS: Eliminación Rápida de Ítems / Vaciar Carrito y Centro de Configuración Totalmente Editable
**Agregado**
- **Eliminación Rápida en Punto de Venta POS (`PosPage.vue`):**
  - Botón de papelera directa (`delete`) en cada fila de producto en el carrito de compras: permite eliminar cualquier ítem en 1 solo clic sin tener que presionar repetidamente el botón de restar cantidad hasta llegar a cero.
  - Botón "Vaciar" (`delete_sweep`) en la cabecera del carrito con confirmación de seguridad para reiniciar la orden completa instantáneamente.
  - Indicador de número total de ítems en la cabecera de la orden.
  - Recálculo en tiempo real de subtotales, ITBIS y total general al eliminar ítems o vaciar el carrito.
- **Centro de Configuración Totalmente Editable (`ConfigurationPage.vue` & Backend):**
  - **Pestaña "Empresa & Perfil":** Formulario completo para editar Nombre Comercial, Razón Social, RNC/Cédula, Moneda Contable Base, Teléfono Principal, WhatsApp de Atención al Cliente, Correo Electrónico Comercial, Dirección Física Fiscal y Zona Horaria.
  - **Pestaña "Fiscal & Comprobantes":**
    - Impuestos de Ley (ITBIS/Propina): Modal para crear nuevos impuestos y modal para editar tasas, nombres descriptivos, ámbitos (productos/servicios/ambos) y estado activo/inactivo.
    - Métodos de Pago: Modal para crear nuevos métodos y modal para editar nombres, exigencia de referencia/banco y estado activo/inactivo.
    - Secuencias NCF y e-CF: Modal de ajuste de secuencias para modificar el número final del rango autorizado por la DGII, umbral de alerta de agotamiento, fecha de vencimiento y estado activo/pausado.
  - **Pestaña "Preferencias Operativas":** Conmutadores y políticas para Punto de Venta (venta sin stock, NCF por defecto, exigencia de RNC, propina legal), Facturación & Precios, e Inventario.
  - **Pestaña "Hub del Sistema":** Tarjetas de acceso directo a Terminales Windows, Gestor de Módulos SaaS, Monitor e-CF, Sucursales, Roles RBAC y Seguridad 2FA.
- **Endpoints y Servicios Backend:**
  - `GET /api/v1/company/profile`: Consulta de datos completos de la empresa activa.
  - `PATCH /api/v1/company/profile`: Actualización con validación `UpdateCompanyRequest`, autorización `CompanyPolicy@update` (`company.manage`) y auditoría.
  - `PATCH /api/v1/payment-methods/{publicId}`: Edición de métodos de pago con `UpdatePaymentMethodRequest`.
  - `PATCH /api/v1/ncf-sequences/{id}`: Ajuste de rangos y umbrales de comprobantes fiscales.
- **Calidad:**
  - 180 pruebas Pest pasando (747 aserciones).
  - Larastan 0 errores (315 archivos).
  - Verificación end-to-end con Playwright MCP: prueba de eliminación rápida en POS, vaciado de carrito, edición de perfil de empresa y ajuste de secuencias NCF con actualización reactiva de la UI.

### 2026-09-05 — Centro de Notificaciones Inteligentes en Vivo y e-CF Modular Opcional
**Agregado**
- **Centro de Notificaciones en Tiempo Real (`app/Modules/Notification/` & `NotificationDropdown.vue`):**
  - Módulo core `notification` registrado en `ModuleCatalog.php` y habilitado por defecto para todos los tenants.
  - Modelo `SystemNotification` con aislamiento estricto multi-empresa (`BelongsToCompany`, `HasPublicUlid`), tipos (`info`, `warning`, `success`, `error`), categorías (`inventory`, `fiscal`, `appointment`, `work_order`, `cash`, `system`), URL de acción y marcas de lectura (`read_at`).
  - Servicio `NotificationService.php` con generación sincronizada de alertas inteligentes operativas:
    - Inventario crítico: detecta productos activos con stock por debajo o igual al mínimo requerido (consulta optimizada y compatible con `ONLY_FULL_GROUP_BY` de MySQL).
    - Secuencias NCF por agotarse: alerta cuando el rango restante de comprobantes alcanza el umbral de alerta (`alert_threshold`).
    - Citas programadas para el día de hoy (módulo `appointment`).
    - Órdenes de taller listas para cobro o entrega (módulo `work_order`).
    - Turnos de caja prolongados abiertos por más de 12 horas (módulo `pos`).
  - Endpoints RESTful completos en `routes.php`:
    - `GET /api/v1/notifications`: Listado con soporte de paginación y metadata de no leídas (`unread_count`).
    - `GET /api/v1/notifications/unread-count`: Consulta rápida de contador.
    - `PATCH /api/v1/notifications/{publicId}/read`: Marcado de notificación individual como leída.
    - `POST /api/v1/notifications/mark-all-read`: Marcado masivo de todas las notificaciones como leídas.
    - `DELETE /api/v1/notifications/{publicId}`: Eliminación de notificaciones.
  - Componente frontend interactivo `NotificationDropdown.vue` integrado en la barra superior de `AppLayout.vue`:
    - Campana interactiva con badge dinámico animado con número de no leídas (oculto en 0).
    - Desplegable con diseño Bento / Kinetic Enterprise, iconos temáticos por categoría, tiempos relativos, botón "Marcar todas", eliminación individual y navegación directa al recurso mediante clic.
    - Polling en segundo plano cada 30 segundos sin interrumpir la interacción del usuario.
    - Textos y categorías localizados en español e inglés (`es.ts` y `en.ts`).
  - Suite de pruebas Pest en `tests/Feature/NotificationTest.php` (8 pruebas completas cubriendo listado, lectura individual/masiva, borrado y aislamiento multi-tenant).
- **Facturación Electrónica e-CF como Servicio SaaS Opcional:**
  - El sistema mantiene desacoplado y opcional el módulo `electronic_invoice` (`is_core = false`): si el cliente no lo contrata/solicita, emite de forma 100% legal y transparente comprobantes fiscales tradicionales (B01, B02, B14, etc.) sin bloqueos ni dependencias de certificados electrónicos.
  - Si el cliente lo solicita/activa, el sistema ya tiene preparado el pipeline completo (`ElectronicInvoiceProviderInterface`, provider Mock/DGII, secuencias e-NCF E31/E32 y colas con backoff exponencial).
  - Banners informativos claros en `ConfigurationPage.vue` y `ElectronicInvoicePage.vue` destacando la naturaleza opcional bajo demanda del servicio e-CF sin afectar la operativa diaria tradicional.
- **Calidad:**
  - Suite Pest: **180 pruebas pasando (747 aserciones)** al 100%.
  - Larastan: **0 errores en 313 archivos** (`[OK] No errors`).
  - Pint: 100% formateado según estándar oficial.
  - Vite build: compilación limpia en 2.52s.
  - Verificación end-to-end en navegador real con Playwright: recepción de alertas en vivo, apertura de dropdown, marcado masivo, eliminación y navegación directa a la agenda.
**Agregado**
- **Puente entre Verticales y POS (`ExternalOrderBridge`):**
  - Implementación de `setExternalOrderBridge()` y `consumeExternalOrderBridge()` en `resources/js/modules/pos/services.ts` para transferir citas y órdenes de trabajo a la caja registradora de forma reactiva y desacoplada vía `sessionStorage`.
  - Botón `"Facturar en POS"` en la Agenda de Citas (`AgendaPage.vue`) para transferir clientes, empleados asignados, notas y servicios.
  - Botón `"Facturar en POS"` en las Órdenes de Trabajo (`WorkOrderListPage.vue`) para transferir vehículos, diagnósticos, servicios, repuestos y mano de obra tarifada.
- **Backend POS Híbrido (Productos Físicos + Servicios de Catálogo + Conceptos Dinámicos):**
  - Actualización de `OrderController.php` y `CreateOrderAction.php` para aceptar tanto `Product` como `Service` (por `public_id` o ID numérico), así como conceptos dinámicos de taller (`srv-`, `part-`, `labor-`, `custom-`).
  - Creación y resolución automática de productos sombra no inventariables (`track_inventory = false`) con el nombre y precio del servicio o mano de obra, garantizando integridad referencial en `order_items` e `invoice_items` sin tocar el stock de almacén.
  - Emisión de facturas fiscales NCF tradicionales (B02 / B01) directas desde el POS con desglose transparente de servicios y repuestos.
- **Pruebas y Calidad:**
  - Nueva prueba Feature en `tests/Feature/POSOrderTest.php`: venta de un servicio en el POS y emisión de factura NCF B02 sin descuento de existencias físicas.
  - Suite Pest completa pasando al 100% (172 pruebas, 717 aserciones).
  - PHPStan / Larastan en nivel máximo pasando con 0 errores en 307 archivos.
  - ESLint sin advertencias y Vite build limpio.
  - Verificación end-to-end en navegador real (Playwright MCP) completando y cobrando ventas fiscales reales desde Barbería y Taller AutoMax con el agente Windows conectado.

### 2026-09-05 — Integración Windows: OmniPOS Agent (Kotlin/Ktor), Detección de Dispositivos Bluetooth/Serial y Gestión de Terminales POS
**Agregado**
- **Agente Local Windows nativo (`agent/`):**
  - Servicio liviano en Kotlin 2.1 + Ktor 3.1 escuchando exclusivamente en loopback `127.0.0.1:8765`.
  - **Detección de Hardware y Periféricos (`LocalPrinterService.kt`):**
    - Impresoras Spooler de Windows vía Java Print Service (`PrintServiceLookup`).
    - Detección profunda de puertos seriales COM (`Win32_SerialPort`) y dispositivos Bluetooth vinculados en Windows (`Get-PnpDevice -Class Bluetooth`).
    - Detección y emparejamiento automático de impresoras Bluetooth (e.g. `2C-P58-C`) asociándolas directamente a sus puertos virtuales `COM7` / `COM6`.
    - Hilo en segundo plano no bloqueante (`OmniPOS-HardwareScanner`) con caché suave de 6 segundos para escaneos ultrarrápidos (<5ms).
    - Impresión raw ESC/POS directa hacia puertos COM / Bluetooth (`\\.\COMx`) y spooler de Windows.
    - Soporte de apertura de gaveta de dinero por pulso ESC/POS (`0x1B, 0x70, 0x00, 0x19, 0xFA`).
  - **Seguridad Loopback Reforzada (`AgentSecurity.kt`):**
    - Validación de loopback estricto mediante `InetAddress.isLoopbackAddress` (soporta Docker Desktop `kubernetes.docker.internal`).
    - Validación estricta de dominios autorizados en `Origin` y rate limiting de 120 req/min.
    - Autenticación opcional mediante tokens HMAC-SHA256 con ventana de tiempo de 300s.
  - **Endpoints del Agente (`Application.kt`):**
    - `GET /api/status`: Estado, versión y host del agente.
    - `GET /api/printers`: Listado de impresoras del sistema y puertos Bluetooth.
    - `GET /api/devices`: Resumen completo de impresoras, dispositivos Bluetooth emparejados y puertos COM.
    - `GET /api/config`: Configuración de la terminal, ancho de papel y codificación.
    - `POST /api/print`: Envío de tickets ESC/POS en texto plano codificado.
    - `POST /api/test`: Prueba de impresión directa sobre cualquier impresora o puerto COM.
    - `POST /api/drawer/open`: Pulso para gaveta conectada a impresora.
  - Empaquetado completo distribuible (`gradle installDist`) en `agent/build/install/omnipos-windows-agent/`.
- **Backend Laravel (`app/Modules/POS/` & `app/Modules/Invoice/`):**
  - Migración y Modelo `AgentTerminal` con almacenamiento seguro de tokens (hash SHA-256) y unicidad por tenant (`company_id`).
  - Controlador `AgentTerminalController` con endpoints RESTful (`index`, `store`, `update`, `destroy`, `rotateToken`).
  - Endpoint de impresión en texto plano monospaced para impresoras térmicas ESC/POS: `GET /api/v1/invoices/{id}/print/text`.
  - Suite de pruebas Pest `AgentTerminalTest.php` (4 pruebas, 24 aserciones pasando al 100%).
- **Frontend Vue 3 / TypeScript (`resources/js/`):**
  - Composables dedicados `useAgentStatus.ts` (polling suave no bloqueante) y `useAgentPrinter.ts` (detección de periféricos, impresión y apertura de gaveta).
  - Nueva página de administración Bento Grid `AgentTerminalsPage.vue` (`/configuracion/terminales`):
    - Banner de estado en vivo del agente Windows.
    - Grilla de impresoras detectadas con botones de prueba de impresión y pulso de gaveta.
    - Panel interactivo de dispositivos Bluetooth vinculados y puertos seriales COM.
    - Tabla de terminales registradas con modal para creación y rotación de tokens HMAC-SHA256.
  - Integración en `PosPage.vue`: Badge en vivo de agente Windows, impresión automática de tickets térmicos vía agente local con fallback transparente al diálogo de impresión del navegador, y pulso de gaveta en cobros en efectivo.
  - Integración en menú de navegación `AppLayout.vue` y rutas de `router/index.ts`.
  - Contenedor Electron con aislamiento de contexto (`electron/main.cjs`, `electron/preload.cjs`).


### 2026-09-05 — i18n: Sistema 100% en español con soporte y alternador a inglés (vue-i18n)
**Agregado**
- **Arquitectura i18n centralizada (`resources/js/i18n/`):**
  - Módulo principal `index.ts` con integración reactiva Composition API de `vue-i18n`.
  - Diccionario exhaustivo en español (`es.ts`) cubriendo términos comunes, navegación, Bento Dashboard, alertas, secuencias DGII e-CF, operaciones y tablas.
  - Diccionario completo en inglés (`en.ts`) con equivalencias semánticas directas.
  - Persistencia automática de la preferencia del usuario en `localStorage` (`omnipos_locale`) y actualización dinámica del atributo `html[lang]`.
- **Selector de Idioma en Topbar (`AppLayout.vue`):**
  - Botón interactivo con icono de traducción y chip de idioma activo (`ES` / `EN`) que conmuta instantáneamente sin recargar la página.
  - Traducción completa y reactiva de los títulos de sección del sidebar, enlaces de navegación, estados e-CF y buscador global.
- **Dashboard Bento Grid traducido al español (`DashboardPage.vue`):**
  - Todos los KPIs, turnos, banners operativos, monitores fiscales DGII y tablas de ventas ahora se muestran en español dominicano por defecto, con fallback y conmutación completa a inglés al pulsar el selector.
- **Configuración backend Laravel (`config/app.php`):**
  - `locale` y `fallback_locale` configurados a `'es'`.


### 2026-09-05 — UI/UX: Rediseño total Enterprise SaaS (Bento Grid, Tokens Zinc/Indigo, Tipografía Geist/Inter)
**Agregado**
- **Adopción integral del Enterprise SaaS Design System:**
  - Integración de fuentes Google Fonts: **Geist** (encabezados métricos y títulos de alta jerarquía), **Inter** (cuerpo e interfaces densas) y **JetBrains Mono** (códigos NCF/e-CF y SKUs) en `welcome.blade.php`.
  - Inclusión de **Material Symbols Outlined** de Google Fonts para iconografía limpia y consistente.
  - Extensión de tokens en `resources/css/app.css` (`@theme` con paleta dark Zinc `#18181b`, `#27272a`, Royal Indigo `#4648d4`, `#6063ee`, emerald y amber).
- **Layout Global Enterprise (`AppLayout.vue`):**
  - **Sidebar de 260px (`#18181b`):** Brand con badge e-CF, tarjeta `Active Store` con `unfold_more` para conmutar sucursal, categorías limpias con scroll invisible (`.no-scrollbar`), footer con avatar del usuario autenticado y acción de desconexión.
  - **Topbar Global:** Input de búsqueda unificada `Global Search (SKU, e-CF, Customer)...`, chip de estatus DGII pulsante (`DGII e-CF: Online` / `NCF Tradicional`), campanilla de notificaciones y botón de acción principal `Open Terminal` / `Abrir POS`.
- **Dashboard Bento Grid (`DashboardPage.vue`):**
  - Encabezado con shift badge (`Store Shift Active #04 • Terminal POS-SD-02`), saludo personalizado, selectores de período (`Today`, `This Week`, `This Month`) y exportación.
  - 4 Tarjetas KPI con borde acentuado de 4px (Ventas del día con % comparativo, Cajón de efectivo/turno, Alertas de stock bajo con umbrales, Emisión DGII e-CF).
  - Banner interactivo de alta velocidad "POS Terminal Ready" con gradiente azul/índigo y accesos rápidos a terminal y atajos de teclado.
  - Tarjeta de Capacidad de Almacén con indicador de SKUs y barra de progreso de capacidad.
  - Monitor en vivo de secuencias DGII activas (E31 Facturas con crédito fiscal, E32 Consumo).
  - Selector de operaciones rápidas y mesas con pestañas dinámicas (`Dining Room`, `Express Pickup`).
  - Tabla de actividad reciente sincronizada con búsqueda en tiempo real, métodos de pago, badges de estado y paginación.


### 2026-09-05 — UI/UX: Rediseño profesional del Dashboard y eliminación de menús locales redundantes
**Agregado**
- **Dashboard Ejecutivo moderno (`DashboardPage.vue`):** Se eliminó por completo el menú viejo local de 220px y el contenedor provisional en desuso. Ahora cuenta con un panel ejecutivo de alto impacto:
  - 4 Tarjetas KPI operativas en tiempo real: Ventas del día (`RD$ 0.00`), Estado de caja (`Requiere apertura`), Catálogo & Stock (`Stock sincronizado`), Fiscal DGII (`e-CF Habilitado` / `NCF Tradicional`).
  - Grilla de **Operaciones Rápidas** interactivas con diseño Kinetic Enterprise para Terminal POS, Catálogo de Productos, Control de Inventario, Directorio de Clientes, Reportes y Cierres, Configuración Fiscal y módulos especializados por giro (Plano de Mesas, Cocina KDS, Agenda, Órdenes de Trabajo, Vehículos, e-CF).
  - Tarjeta de contexto empresarial con resumen de la sucursal, ID, moneda, modo de operación Nube SaaS, aislamiento multitenant y acceso a centro de control de módulos y roles.
- **Limpieza de enlaces locales obsoletos:** Se eliminaron los botones rudimentarios `← Volver al panel` e importaciones en desuso en las 15 vistas internas secundarias (`ProductListPage`, `CustomerListPage`, `ReportsPage`, `ConfigurationPage`, `ElectronicInvoicePage`, `AuditLogPage`, `RoleManagementPage`, `UserManagementPage`, `BranchManagementPage`, `ModuleManagementPage`, `SecurityPage`, `AgendaPage`, `WorkOrderListPage`, `VehicleListPage`, `EmployeeListPage`), unificando toda la navegación en el sidebar fijo global.
- **Optimización y eliminación de scrollbar en sidebar (`AppLayout.vue` y `app.css`):** Se implementó la clase utilitaria `.no-scrollbar` (`scrollbar-width: none; -ms-overflow-style: none; ::-webkit-scrollbar { display: none; }`) para eliminar completamente la barra de desplazamiento gris y tosca nativa de Windows. Se compactaron los paddings, margins y alturas de los ítems de navegación (`h-8.5` con `space-y-0.5` y `space-y-3` entre secciones) logrando un diseño esbelto estilo Linear/Stripe.

**Validado**
- Verificado en navegador real mediante Playwright navegando en vivo entre Dashboard, Productos, Inventario, y Resumen.
- Inspección visual mediante captura de pantalla completa confirmando integración armónica y profesional.
- Suite Pest completa pasando: 167 tests (679 aserciones).
- Compilación Vite y linter ESLint 100% limpios sin advertencias ni errores.

### 2026-09-05 — UI/UX: Layout global con Sidebar lateral fijo (tipo Bootstrap/Laravel SaaS)
**Agregado**
- Nuevo componente `AppLayout.vue` (`resources/js/layouts/AppLayout.vue`) que envuelve todas las vistas internas autenticadas del sistema.
- **Sidebar lateral izquierdo fijo (`w-64`):** Permanece siempre visible en escritorio sin cerrarse al cambiar de pantalla o módulo. Incluye logotipo de OmniPOS, tarjeta de contexto (empresa y sucursal con botón para cambiar), navegación categorizada por áreas operativas (Operación diaria, Catálogo & Almacén, Fiscal & Reportes, Administración SaaS) con resaltado activo automático de ruta, y footer con información de usuario y botón de cerrar sesión. En dispositivos móviles opera como un drawer deslizable con backdrop.
- **Topbar superior fija:** Barra sticky con título de contexto, indicador de estado "En línea", y botón de acceso rápido "Abrir POS".
- Actualizado `App.vue` para aplicar automáticamente `AppLayout` en todas las rutas con contexto, excluyendo únicamente las pantallas de autenticación (`/ingresar`, `/crear-cuenta`, `/seleccionar-contexto`, `/configuracion/inicial`).

### 2026-07-12 — Imágenes de producto (subir, listar, POS)
**Agregado**
- Los productos ahora pueden tener **imagen**. Backend: endpoints `POST /products/{id}/image` (multipart, campo `image`, jpg/png/webp ≤ 2 MB) y `DELETE /products/{id}/image`, con almacenamiento en el disco `public` (`products/`), borrado del archivo anterior y auditoría (`product.image_updated` / `product.image_removed`). `ProductResource` expone `image_url`.
- **Normalización tipo catálogo:** al subir, la imagen se recorta al centro y se reescala a un **cuadrado estándar de 600×600** con GD (sin dependencias, `App\Core\Support\SquareImage`), de modo que todas las fotos quedan uniformes sin importar su proporción original.
- Frontend: la pantalla **Productos** tiene un campo de imagen con vista previa, "Subir/Cambiar imagen" y "Quitar imagen"; el listado muestra **miniatura** por fila; las **tarjetas del POS** muestran la imagen en un contenedor **cuadrado** (o un placeholder 🖼️ si no tiene), y la grilla usa más columnas (hasta 6) para una densidad de catálogo.
- `ProductForm`/servicios: `uploadProductImage` y `deleteProductImage`; la imagen se sube tras guardar el producto (usa su id).

**Corregido**
- ESLint marcaba `no-undef` en archivos `.vue` para globals del navegador (`localStorage`, `File`, `URL`…) porque `typescript-eslint` solo lo desactiva en `.ts`. Se desactiva `no-undef` para `**/*.vue` (vue-tsc ya valida identificadores). Esto además corrige un lint latente introducido al aislar el carrito.

**Requiere despliegue**
- Ejecutar **`php artisan storage:link`** una vez para servir las imágenes desde `public/storage`.

**Validado**
- `ProductCatalogTest` cubre subir (con `image_url` no nulo, archivo persistido y **dimensiones normalizadas a 600×600**), rechazo de no-imagen y eliminación (archivo borrado). 8 pruebas del módulo verdes; Pint/Larastan limpios; typecheck/ESLint/Prettier/build verdes. Verificado en navegador: subida real (200 OK) de una imagen **ancha (800×300)** al Chocolate que el sistema recorta a cuadrado y el POS muestra tipo catálogo (imágenes cuadradas uniformes, SKU truncado).

### 2026-07-11 — Guía de Uso: 4 giros adicionales
**Agregado**
- Guías con imágenes reales para **Minimarket** (ciclo POS completo con comprobante FAC-000003), **Cafetería** (barra rápida), **Distribuidora** (clientes con crédito + compras; documenta que no trae POS por defecto) y **Servicios profesionales** (documenta honestamente que su pantalla de servicios/cotizaciones es backlog). Índice `README.md` actualizado a los 9 giros.

### 2026-07-11 — Aislar el carrito del POS por empresa/sucursal
**Corregido**
- El carrito del POS se persistía en IndexedDB bajo una clave fija (`current_cart`) global: al cambiar de compañía aparecían los ítems del carrito de la empresa anterior (riesgo de vender productos de otro tenant). Ahora cada carrito se guarda por **contexto** (`cart:<companyId>:<branchId>`) en `IndexedDBService`, y `PosPage` pasa ese scope al leer/guardar. Un carrito vacío borra su registro para no acumular claves.

**Validado**
- En navegador: con "Caja de tornillos" en el carrito de la Ferretería, el POS del Supermercado abre **vacío**; al volver a la Ferretería su carrito **se conserva**. typecheck/ESLint/Prettier/build verdes.

### 2026-07-11 — Guía de Uso con imágenes + POS resiliente sin módulo Productos
**Agregado**
- Carpeta **`Guia de Uso/`** con guía operativa por giro, capturas reales del sistema y ciclo completo de cada uno: índice (`README.md`), acceso común (`_comun/`) y guías de **Salón/Barbería**, **Ferretería**, **Restaurante**, **Taller** y **Supermercado**, cada una con su subcarpeta `img/`.

**Corregido**
- **POS resiliente** (`PosPage.vue`): la carga de productos y almacenes ahora tolera un `403` por módulo apagado (giros de puro servicio como barbería) sin tumbar todo el POS con "Error al inicializar". Antes, un salón con `pos` activo pero sin módulo `product` no podía abrir la caja.

**Validado**
- Recorrido en navegador de los 5 giros documentados con venta real donde aplica: Ferretería (FAC-000001, RD$1,958.80, devuelta RD$41.20), Restaurante (comanda RD$1,085.60 + mesas + KDS), Taller (orden a Cotizada), Supermercado (venta RD$595.90 con inventario por lotes). ESLint, vue-tsc, Prettier y build verdes.

### 2026-07-11 — Demos por tipo de negocio (`DemoVerticalsSeeder`)
**Agregado**
- `database/seeders/DemoVerticalsSeeder.php`: crea tres empresas demo adicionales con onboarding completo por giro, RNC válido propio, caja renombrada y secuencia NCF B02 (1–500). Idempotente (se salta cada demo si su usuario existe); contraseña común `Password123!`:
  - **Restaurante El Fogón** (`demo.restaurante@omnipos.test`, giro `restaurant`): 4 platos POS sin inventario y plano de mesas (Salón S1–S4, Terraza T1–T2).
  - **Barbería La Navaja** (`demo.barberia@omnipos.test`, giro `barbershop`): 3 servicios, 2 barberos con comisión, cliente y 2 citas (hoy confirmada Corte+Barba RD$767; mañana pendiente Tinte RD$1,416) vía `CreateAppointmentAction`.
  - **Taller AutoMax** (`demo.taller@omnipos.test`, giro `mechanic`): 2 servicios, repuesto con stock (10 uds), mecánico, cliente con Toyota Corolla 2019 y orden de trabajo en `diagnosticando` (RD$1,794 = mano de obra 500 + servicio con ITBIS 944 + repuesto 350) vía `CreateWorkOrderAction`.
  - **Cafetería Aroma** (`demo.cafeteria@omnipos.test`, giro `cafeteria`): barra rápida, 2 bebidas sin inventario + 2 productos con stock.
  - **Supermercado La Económica** (`demo.super@omnipos.test`, giro `supermarket`): 5 productos con inventario avanzado, lotes y vencimientos (FEFO) — leche/pan/aceite con fecha de vencimiento.
  - **Ferretería El Tornillo** (`demo.ferreteria@omnipos.test`, giro `hardware_store`): 5 materiales con stock (cemento, pintura, martillo, tornillos, PVC).
  - **Distribuidora del Cibao** (`demo.distribuidora@omnipos.test`, giro `distributor`): 3 productos al por mayor + cliente empresa con crédito (RD$50,000, 30 días).
  - **Consultores Pro** (`demo.servicios@omnipos.test`, giro `professional_services`): 3 servicios profesionales + cliente empresa (sin POS, orientado a cotizaciones).
- El minimarket demo existente (`demo@omnipos.test`, DemoSeeder) completa **9 verticales** en total.

**Validado**
- Pint y Larastan limpios sobre el seeder; verificación en navegador real de los tres demos: menú dinámico por giro (Mesas/KDS solo en restaurante; Agenda/Empleados en barbería; Vehículos/Órdenes en taller), plano de mesas con 6 mesas disponibles, agenda con cita y total ITBIS correcto, y orden de taller con total RD$1,794 y transiciones válidas.

### 2026-07-11 — POS accesible para adultos mayores + modernización Kinetic
**Cambiado**
- **Modal "Detalles del Cobro"** rediseñado a estándar táctil Kinetic (`PosPage.vue`): modal más ancho (max-w-2xl), título 24px, labels 14px, selects/inputs de 48px con texto 16px, monto entregado 18px bold, botones de billetes rápidos de 48px en grid, cambio/devuelta destacado en píldora verde con cifra 24px, botón "Confirmar e Imprimir" verde de alta visibilidad (56px, texto 18px) según patrón "Pay button" del design system. Tipo de venta y comprobante ahora con labels propios en grid (antes selects sin etiqueta apretados en una fila).
- **Resto del POS**: apertura de caja (campos 56px), cabecera oscura (texto 14-16px, botones 44px), carrito (nombre/precio 16px, botones ± de 44×44, total general 30px), botón "Completar Venta" verde 56px, tarjetas de producto (nombre 16px, precio 18px), buscador 52px; modales de movimiento/cierre/ticket con títulos 20px, campos 48px y botones 48px; vista previa del ticket a 13px.
- Base global (`app.css`): antialiasing y `optimizeLegibility` para todas las pantallas.
- **Pase de accesibilidad al resto de módulos** (17 archivos): todos los inputs/selects de formularios pasan a 48px de alto con texto 16px (antes 44px con 14px o el default), botones primarios/secundarios a 48px con texto 16px, fechas y filtros de Reportes a 48px, pestañas de Reportes a 16px, tabla de Reportes a 16px con cabecera 14px, navegación del panel a 16px. Afecta: reportes, clientes, productos, inventario, agenda, empleados, vehículos, órdenes, auditoría, roles, usuarios, sucursales, e-CF, onboarding, configuración, seguridad y dashboard.

**Corregido**
- Select de comprobante del POS enviaba `01`/`02` en vez de `B01`/`B02` al elegir manualmente: rompía la validación de RNC para B01 y el código canónico enviado al backend. Valores corregidos a `B01`/`B02`.

**Validado**
- Venta real en navegador con el nuevo modal: FAC-000002, NCF B0200000002, RD$ 236 (2 × Café), efectivo RD$ 500, devuelta RD$ 264, caja 618→854. ESLint, vue-tsc, Prettier y build verdes.

### 2026-07-11 — Fixes de layout en auth y formulario de vehículos (revisión en navegador)
**Corregido**
- `resources/css/app.css`: el padding de escritorio de `.auth-panel` usaba `100vw` (viewport completo) siendo el panel solo la columna izquierda del grid; en pantallas ≥ ~1250px el contenido de login/registro colapsaba a una columna de ~45px. Ahora el cálculo usa el ancho del propio panel (`calc((100% - 520px) / 2)`).
- `VehicleListPage.vue`: los inputs del grid de 2 columnas de "Nuevo vehículo" desbordaban la tarjeta (ancho intrínseco > celda); se añadió `w-full min-w-0`.

**Validado**
- Revisión con Playwright de las 17 pantallas del menú + login/registro/contexto/onboarding con venta de humo en POS (FAC-000001, NCF B0200000001, RD$ 118, caja 500→618, stock 25→24) sin errores de consola ni respuestas 4xx/5xx. ESLint, vue-tsc, Prettier y build verdes.
- Hallazgo operativo (entorno local, no de código): la BD tenía 4 migraciones pendientes de los verticales (`employees`, `appointment_tables`, `vehicles`, `work_order_tables`) que causaban 500 en `/api/v1/employees` y `/api/v1/appointments`; se aplicaron con `php artisan migrate`. El `DemoSeeder` había fallado silenciosamente en el empleado demo por ese motivo (artisan devolvió éxito) — vigilar en el futuro.

### 2026-07-11 — E2E de los verticales Barbería y Taller
**Agregado**
- `appointments.spec.ts`: agenda una cita (empleado + servicio) verificando el total con ITBIS (RD$ 354) y la transición Pendiente→Confirmada.
- `work-orders.spec.ts`: registra un vehículo, crea una orden de trabajo con servicio + mano de obra (RD$ 854) y avanza Recibida→Diagnosticando.
- `DemoSeeder` activa los módulos `service/employee/vehicle/appointment/work_order` y siembra un servicio y un empleado para estos recorridos. Suite E2E ahora en **18 escenarios verdes**.

### 2026-07-11 — Export nativo Excel/PDF de reportes + seeder base global
**Agregado**
- **Excel nativo (.xlsx)** y **PDF** del reporte de ventas con escritores propios sin dependencias: `App\Core\Support\XlsxWriter` (paquete OOXML vía `ZipArchive`, celdas numéricas detectadas) y `App\Core\Support\PdfTableDocument` (PDF 1.4, tabla A4 paginada, Helvetica/WinAnsi). Endpoints `GET /reports/sales/export.xlsx` y `.pdf`; botones **Excel** y **PDF** junto a Exportar CSV en la pantalla de Reportes.
- `ConfigurationSeeder` ahora también siembra el **catálogo de permisos** global (además de monedas y tipos de comprobante), cerrando el ítem de seeder base de Fase 0.

**Validado**
- `ReportExportTest` (4 casos): firma ZIP `PK` del .xlsx, cabecera `%PDF-` del PDF, endpoints por HTTP real con content-types correctos, y el seed global de permisos. Suite backend **166 verde**; Pint y Larastan limpios; typecheck/ESLint/Prettier/build verdes.

### 2026-07-11 — Fase 12: vertical Taller mecánico (vehículos + órdenes de trabajo)
**Agregado**
- Módulo activable **`vehicle`** (dep. `customer`): vehículos de clientes (tabla `vehicles`) con marca, modelo, año, placa, VIN, color, kilometraje. CRUD `GET/POST/PATCH /vehicles` con búsqueda; permisos `vehicles.view/manage`.
- Módulo activable **`work_order`** (dep. `vehicle` + `service`): órdenes de taller (tablas `work_orders`, `work_order_services`, `work_order_parts`). `GET /work-orders` con filtros por estado/vehículo, `POST /work-orders` (diagnóstico + servicios con ITBIS + repuestos cantidad×precio + mano de obra, total en DECIMAL vía bcmath), `GET /work-orders/{id}`, `PATCH /work-orders/{id}/status` con transiciones validadas (recibida→diagnosticando→cotizada→aprobada→en_proceso→lista→entregada; cancelada). Permisos `work_orders.view/manage`.
- Frontend: pantallas **Vehículos** (`/vehiculos`) y **Órdenes de trabajo** (`/ordenes-trabajo`) con repuestos dinámicos y transiciones de estado, gated por módulo, con ítems de menú. Design system Kinetic.
- El preset de negocio `mechanic` ya activaba `service`, `vehicle`, `work_order`; ahora tienen implementación real.

**Validado**
- `WorkshopModuleTest` (6 casos): registrar vehículo, orden con total servicios+ITBIS+repuestos+mano de obra (RD$ 1554), transiciones válidas e inválidas (409), filtros por estado/vehículo, bloqueo por módulo desactivado y aislamiento por compañía. Suite backend **162 verde**; Pint y Larastan limpios; typecheck/ESLint/Prettier/build verdes.
- Navegador real: registrar vehículo Toyota Hilux (POST 201, aparece en lista) y crear orden de trabajo (POST 201) desde las pantallas nuevas.

### 2026-07-11 — Fase 12: vertical Barbería / Salón (empleados + citas)
**Agregado**
- Módulo activable **`employee`**: empleados con cargo, contacto y % de comisión (tabla `employees`, CRUD `GET/POST/PATCH /employees`, permisos `employees.view/manage`).
- Módulo activable **`appointment`** (dep. `service`): agenda de citas (tablas `appointments`, `appointment_services`). `GET /appointments` con filtros por fecha/empleado/estado, `POST /appointments` (snapshot de servicios + total con ITBIS calculado en DECIMAL vía bcmath), `GET /appointments/{id}`, `PATCH /appointments/{id}/status` con transiciones validadas (pendiente→confirmada→en_proceso→completada; cancelada/no_asistio). Permisos `appointments.view/manage`.
- Frontend: pantallas **Agenda** (`/agenda`) y **Empleados** (`/empleados`), gated por módulo, con ítems de menú. Design system Kinetic.
- El preset de negocio `barbershop` ya activaba `service`, `appointment` y `employee` en el onboarding; ahora esos módulos tienen implementación real.

**Validado**
- `AppointmentModuleTest` (6 casos): empleado con comisión, cita con total+ITBIS (RD$ 354), transiciones válidas e inválidas (409), filtros de agenda por fecha/empleado, bloqueo por módulo desactivado y aislamiento por compañía. Suite backend **156 verde**; Pint y Larastan limpios; typecheck/ESLint/Prettier/build verdes.
- Navegador real (usuario demo con módulos activados): crear cita RD$ 413 (350 + 18%), transición Pendiente→Confirmada, pantalla de Empleados; 0 errores de consola.

### 2026-07-10 — Fase 14: E2E de venta sin stock + fix de feedback en POS
**Corregido**
- **POS:** un cobro fallido durante un turno activo (stock insuficiente, fallo de emisión fiscal o cualquier error del servidor) no mostraba ningún mensaje al cajero — el único banner de error vivía dentro del formulario de apertura de caja (`v-if="!activeSession"`). Ahora el modal "Detalles del Cobro" muestra el error (`role="alert"`), así el cajero ve el motivo del rechazo.

**Agregado**
- Escenario E2E `pos-no-stock.spec.ts`: el POS bloquea la venta de un producto inventariable agotado (backend responde 400 "Stock insuficiente", la interfaz muestra el error y no emite comprobante), y arquea la caja para no dejar turno abierto. `DemoSeeder` siembra "Botellón de Agua 20 L" sin existencias. Suite E2E en 16 escenarios verdes.

### 2026-07-10 — Fase 14: E2E de módulos y endurecimiento de CI
**Agregado**
- Escenarios E2E `modules.spec.ts`: desactivar y reactivar un módulo opcional (Código de barras) verificando el `role="switch"`/`aria-checked`, y comprobación de que un módulo del núcleo no se puede desactivar. Suite E2E ahora en 15 escenarios verdes.

**Cambiado**
- `phpunit.xml` fija un `APP_KEY` de pruebas para que la suite backend (incl. cifrado del secreto 2FA) pase en CI sin depender de un `.env`.

### 2026-07-10 — Fase 14: pago mixto y multimoneda E2E
**Agregado**
- Segundo recorrido POS automatizado: tarjeta DOP RD$58 con referencia + efectivo USD 1.00 a tasa vigente RD$60, total exacto RD$118, B02 y descuento de stock.
- Etiquetas accesibles por línea de pago para método, moneda, monto y referencia; permiten operar y probar cobros mixtos sin depender de la posición visual de los controles.
- Producto E2E adicional aislado para que cada venta POS valide sus existencias sin depender del escenario anterior.

**Corregido**
- El POS ya no usa una tasa USD fija: carga la última tasa vigente configurada por compañía y bloquea el cobro en una moneda sin tasa vigente.

**Validado**
- Playwright Chromium verificó la tasa visible, el payload de cobros enviado, la factura, el ticket y el stock final.

### 2026-07-10 — Fase 14: venta POS E2E y correcciones fiscales de interfaz
**Agregado**
- `pos-sale.spec.ts`: abre Caja Demo, vende el producto con ITBIS, cobra RD$200, valida devuelta RD$82, emisión B02, ticket e inventario reducido de 25 a 24.
- Datos E2E deterministas para producto inventariable, stock y secuencia NCF B02.

**Corregido**
- El catálogo de productos expone el `tax_id` público, consistente con la configuración fiscal pública; el POS vuelve a asociar correctamente ITBIS sin consultas N+1.
- El modal de comprobante emitido dejó de estar anidado en el modal de cierre de caja, por lo que se muestra inmediatamente tras facturar.

**Validado**
- Chromium completó apertura de caja → orden → pago → factura `B0200000001` → ticket → consulta de existencias.

### 2026-07-10 — Fase 14: runner E2E estable e integración continua
**Agregado**
- Workflow GitHub Actions con gates de calidad backend, frontend y navegador; el job E2E prepara SQLite aislada, construye assets, instala Chromium y publica artefactos Playwright si falla.
- Documentación del alcance real de la suite E2E y de los flujos de negocio que quedan por automatizar.

**Corregido**
- Playwright ya no reutiliza un servidor PHP implícitamente: evita ejecutar contra una base E2E obsoleta. La reutilización requiere `PLAYWRIGHT_REUSE_SERVER=true`.
- Cache E2E en memoria para que los escenarios no se contaminen entre sí mediante el rate limiter; los límites permanecen cubiertos por las pruebas backend.
- Aserciones 2FA delimitadas a chips de estado exactos, evitando colisiones con texto auxiliar y botones.

**Validado**
- `npm run test:e2e`: 11 escenarios Chromium aprobados, incluido activar → reto de login → acceso TOTP → desactivar.

### 2026-07-10 — Fase 14: UI de 2FA (pantalla de Seguridad + reto en login)
**Agregado**
- Pantalla `/seguridad` (nivel cuenta, sin gate de módulo): estado del 2FA; asistente de activación (clave de configuración agrupada de 4 en 4 + enlace `otpauth://` para la app autenticadora) → confirmar con código de 6 dígitos → desactivar exigiendo código.
- Login con reto de segundo factor: al recibir `TWO_FACTOR_REQUIRED`/`TWO_FACTOR_INVALID` se revela el campo "Código de verificación (2FA)" y se reenvía con `code`; `session.login` acepta `code` opcional.
- Ítem "Seguridad" en el menú (siempre visible, no depende de módulo).

**Validado**
- typecheck, ESLint, Prettier y build sin errores; suite backend 149 verde (sin cambios de backend).
- Navegador real: activar 2FA en `/seguridad` (clave `UCWB DLEU…`, URI otpauth correcto) → confirmar con código → chip "Activa"; logout → login sin código muestra el reto → login con código TOTP válido entra a `/seleccionar-contexto`.

### 2026-07-10 — Fase 14: 2FA TOTP y guía de despliegue
**Agregado**
- `App\Core\Security\TotpService` (RFC 6238, sin dependencias): secreto Base32, HMAC-SHA1, ventana ±1 período, `codeAt` y `provisioningUri`.
- Columnas `two_factor_secret` (cifrado) y `two_factor_confirmed_at` en `users`; `User::hasTwoFactorEnabled()`, secreto oculto en respuestas.
- Endpoints `GET/POST /auth/2fa` (status/enable/confirm/disable): activación en dos pasos (secreto → confirmar con código), desactivación exige código.
- Reto de 2FA en el login: con 2FA activo, `POST /auth/login` requiere `code` (`TWO_FACTOR_REQUIRED` sin él, `TWO_FACTOR_INVALID` si es incorrecto); eventos `auth.2fa_*` auditados.
- `docs/10_DEPLOYMENT.md`: guía de producción (Nginx/PHP-FPM 8.3/MySQL/Redis, worker de colas obligatorio para e-CF, cron scheduler, backups, optimización, checklist). `docs/08` y `docs/03` actualizados.

**Validado**
- Pest: 4 pruebas nuevas (TOTP determinista; enable/confirm/challenge/disable; reto en login; sin reto sin 2FA). Suite total 149 verde.
- Pint, Larastan, typecheck, ESLint, Prettier sin errores.
- Servidor en vivo: login sin código → `TWO_FACTOR_REQUIRED`; código inválido → `TWO_FACTOR_INVALID`; código TOTP válido → token emitido.

### 2026-07-10 — Fase 13 (cierre): pantalla de Reportes y reporte de anulaciones
**Agregado**
- `ReportService::annulments` + `GET /reports/annulments`: facturas anuladas del período con resumen (conteo y total), base operativa del 608.
- Pantalla de Reportes (`/reportes`, módulo `report`): 7 pestañas (ventas, por producto, por categoría, por método de pago, impuestos, caja, anulaciones), filtros de fecha, tarjetas KPI de resumen, descargas CSV de ventas y TXT DGII 606/607/608, e impresión (`print:hidden` oculta controles al imprimir). Enlazada al menú dinámico.
- Servicio front `downloadReport` (blob) y `fetchReport` sobre el cliente `api` autenticado.

**Validado**
- Pest: 2 pruebas nuevas (anulaciones filtradas por período con resumen; permiso requerido). Suite total 145 verde.
- Pint, Larastan, typecheck, ESLint, Prettier y build sin errores.
- Navegador real: venta B02 (NCF B0200000001, RD$ 700) reflejada en resumen y desglose por producto; descarga CSV HTTP 200 con encabezado y datos; cambio de pestañas sin errores de consola.

### 2026-07-10 — Fase 10: Tolerancia a Fallos, Contingencia e-CF y Onboarding Fiscal Completado
**Agregado**
- Job asíncrono `SendElectronicInvoiceJob` con reintentos automáticos (5 intentos) y backoff exponencial (5s, 30s, 60s, 300s, 900s) para transmisión de comprobantes.
- Soporte para estado `contingency` (contingencia fiscal de 24h) ante caídas de comunicación o timeouts de red del WebService de la DGII/PSFE.
- Migración `add_attempts_to_electronic_invoices` para rastrear intentos de envío directamente en base de datos.
- UI guiada Paso 3 en Onboarding Wizard para parametrización inicial de RNC, moneda, tasas de ITBIS predeterminadas y creación automática de la primera caja registradora.
- Pruebas de integración Pest `POSElectronicInvoiceContingencyTest` y `OnboardingFiscalSetupTest`.

**Corregido**
- Transmisión síncrona original migrada a encolado asíncrono no-bloqueante al emitir la factura.
- Solucionada excepción de clave única `taxes.code` no nula durante la inicialización de tasas de impuesto en onboarding.
- El nombre de caja del onboarding ahora **renombra la caja principal ya provisionada** (al crear la sucursal) en vez de crear una segunda caja duplicada; el ITBIS del onboarding usa `firstOrCreate` para no duplicar el ITBIS ya provisionado. Verificado en navegador: restaurante queda con una sola caja, propina legal ON, FEFO y bloqueo de vencidos, y e-CF activable/activo.

### 2026-07-10 — Fase 10: base e-CF y presets operativos
**Agregado**
- Módulo opcional `electronic_invoice`, tablas, evento de emisión, contrato de provider, Null/Mock providers, bitácora, API y pantalla de control e-CF.
- Presets de ajustes operativos por tipo de negocio al completar onboarding.

**Validado**
- Pest cubre providers e-CF, reintento, permiso, módulo inactivo y presets; Pint, Larastan, typecheck, ESLint, Prettier y build PWA sin errores.

### 2026-07-10 — Endurecimiento de integridad de ventas (revisión pre-commit)
**Corregido**
- Cierre de caja: el efectivo esperado ya solo suma pagos en efectivo (tarjeta/transferencia no entran a la gaveta) y `COALESCE` evita que un cambio NULL descarte filas del SUM.
- Correlativo de facturas/notas serializado con `lockForUpdate` sobre la compañía (dos ventas simultáneas ya no colisionan en `FAC-XXXXXX`) + uniques de respaldo en BD (`company_id+invoice_number`, `company_id+ncf`, `company_id+idempotency_key`).
- Notas de crédito acumuladas limitadas a la cantidad facturada (antes se podía devolver más de lo vendido en notas sucesivas).
- Anulación tras nota de crédito ya no duplica el reingreso de stock (solo restaura lo no devuelto) y reingresa al almacén que despachó la venta (`orders.warehouse_id` nuevo) en anulaciones y notas.
- Solo se facturan órdenes `completed`: una orden pendiente ya no puede recibir NCF ni nacer "paid".
- Totales del POS redondeados a 2 decimales en cada paso (float ya no rechaza pagos exactos con ITBIS/propina).
- Idempotencia atómica con `Cache::lock` (reintentos concurrentes con la misma key ya no duplican la venta).
- Sesiones de caja y órdenes anclados a la sucursal activa del contexto (antes siempre `branches()->first()`); la caja debe pertenecer a la sucursal.
- Frontend POS/Inventario/Restaurante migrado al cliente `api` compartido: los llamados con `axios` crudo usaban claves de localStorage inexistentes (401 siempre) y `GET /taxes` inexistente (ahora `/settings/fiscal`); corregido `order_id` undefined al facturar desde el POS.
- Interceptor 401: token revocado/expirado limpia la sesión local y regresa al login (antes quedaba "medio autenticado" con menú vacío).
- Almacenes y proveedores exponen `public_id` (antes filtraban el autoincrement interno y rompían la validación del POS).

**Agregado**
- Provisión automática al crear compañía/sucursal: Caja Principal y Almacén Principal (sin ellos el POS no podía operar; master prompt §10 paso 5).

**Validado**
- 6 pruebas de regresión nuevas (`SalesIntegrityRegressionTest`): factura de orden pendiente rechazada, pago exacto con ITBIS+propina, tope acumulado de notas de crédito, anulación tras nota sin doble stock, cierre de caja solo-efectivo, replay de Idempotency-Key. Suite total 128 verde.
- Navegador real (Playwright): onboarding restaurante → producto → apertura de Caja Principal provisionada → venta con devuelta → secuencia B02 creada en Configuración → segunda venta emite `FAC-000001 / NCF B0200000001`; caja esperada 2050.00 exacta; venta sin secuencia NCF falla con mensaje claro sin romper el cobro.

### 2026-07-10 — Fase 13: impuestos y descuentos operativos
**Agregado**
- Reportes de ventas por impuesto y de facturas con descuentos, filtrados por compañía, período y sucursal.

**Validado**
- Pest cubre agregación de impuestos y descuentos; Pint y Larastan sin errores.

### 2026-07-10 — Fase 13: Formato DGII 607
**Agregado**
- `GET /api/v1/reports/dgii/607?period=YYYY-MM` con detalle de ventas tradicionales, ITBIS, propina y distribución por forma de pago.
- Excluye B02 menores de RD$50,000 y rechaza ventas que requieren identificación fiscal sin tenerla.

**Validado**
- Pest cubre pagos mixtos y exclusión B02 bajo el umbral; Larastan sin errores.

### 2026-07-10 — Fase 13: Formato DGII 606
**Agregado**
- `GET /api/v1/reports/dgii/606?period=YYYY-MM` para exportar compras confirmadas en TXT delimitado por `|`.
- Validación estricta de snapshot fiscal, RNC/cédula y NCF tradicional antes de generar el archivo.

**Validado**
- Pest cubre el encabezado y detalle 606; Pint y Larastan sin errores.

### 2026-07-10 — Fase 13: base fiscal para Formato 606
**Agregado**
- Tabla `purchase_fiscal_data` aislada del flujo operativo de compra para conservar el snapshot requerido por 606.
- Endpoint protegido para registrar clasificación del gasto, NCF, identificación del proveedor, impuestos, retenciones y forma de pago.

**Validado**
- Pruebas de inventario existentes, Pint y Larastan sin errores.

### 2026-07-10 — Fase 13: Formato DGII 608
**Agregado**
- Metadatos obligatorios de anulación en factura: fecha y código de motivo DGII (1–10), validados por Form Request y auditados.
- `GET /api/v1/reports/dgii/608?period=YYYY-MM`, que descarga el TXT mensual delimitado por `|`.

**Seguridad fiscal**
- La exportación rechaza datos incompletos, NCF no tradicionales o identificador fiscal de empresa inválido; no produce archivos que aparenten cumplimiento.

**Validado**
- Pest cubre anulación con metadatos, contenido 608 y rechazo por datos fiscales incompletos; Pint y Larastan sin errores.

### 2026-07-10 — Corrección fiscal: código y formato NCF canónicos
**Corregido**
- La emisión POS deja de persistir códigos internos abreviados (`01`, `02`, `04`) y NCF sin serie; ahora usa `B01`, `B02`, `B04` y el comprobante completo (por ejemplo, `B0200000001`).
- La UI e impresiones interpretan los códigos canónicos. La API mantiene compatibilidad de entrada para `01`/`02`, pero normaliza la persistencia.
- Migración de datos para secuencias y facturas históricas con códigos abreviados.

**Validado**
- Pruebas de secuencia NCF, facturación POS e impresión; typecheck, ESLint y Prettier sin errores.

### 2026-07-10 — Fase 13: reportes operativos base
**Agregado**
- Módulo `Report` con ventas por período, sesiones de caja cerradas y filtro opcional por sucursal.
- Exportación CSV UTF-8 de ventas bajo el mismo filtro y permiso `reports.view`.
- Desgloses de ventas pagadas por producto, categoría, método de pago, cajero y cliente, siempre limitados por compañía, período y sucursal opcional.

**Corregido**
- Los totales de reportes se agregan en la base de datos y se preservan como DECIMAL serializado; se elimina la conversión de dinero a `float`.

**Validado**
- Pest cubre aislamiento de tenant, exclusión de facturas anuladas, CSV y los tres desgloses; Pint y Larastan sin errores.

### 2026-07-10 — Corrección de calidad transversal antes de Fase 13
**Corregido**
- Errores de Pint en los módulos recientes de inventario, POS, facturación, impresión y restaurante.
- 55 hallazgos Larastan: serialización de fechas, contratos de recursos, relaciones de caja/impresión, nullability y colecciones tipadas.
- Contrato TypeScript de POS: métodos de caja, campos fiscales de productos, datos de arqueo y representación de factura.
- Apertura de mesa: la orden ahora toma una sucursal real de la compañía en lugar de referir un inexistente `user.company_id`.

**Validado**
- Pest: 115 pruebas / 431 aserciones; Pint, Larastan, vue-tsc, Vitest, ESLint, Prettier y build PWA sin errores.

### 2026-07-10 — Fase 12: Módulo de Restaurante
**Agregado**
- Migración `create_restaurant_tables` con las tablas: `restaurant_areas` (sectores físicos), `restaurant_tables` (mesas, capacidad y estados) y `kitchen_orders` (comandos de cocina).
- Modelos Eloquent backend: `RestaurantArea`, `RestaurantTable` y `KitchenOrder` mapeados con ULIDs de auditoría y relaciones.
- Relación `table()` dinámica en el modelo `Order` del POS y trigger automático en `CreateOrderAction` para liberar la mesa física al cobrarse la orden.
- Servicios `RestaurantService` (apertura y transferencia de cuentas con bloqueo pesimista `lockForUpdate`) y `KitchenService` (envío de comanda y cambio de estatus en preparación).
- API routes y `RestaurantController` y `KitchenController` expuestos bajo tenant.
- Plano de Mesas interactivo en el frontend (`RestaurantLayoutPage.vue`) con estados dinámicos, tiempos transcurridos y totales.
- Tablero Kanban KDS en cocina (`KitchenKdsPage.vue`) con tarjetas de pedidos, alertas de demoras en color y triggers táctiles.
- Enlaces dinámicos en la barra de navegación lateral.

**Validado**
- Pest: 6 pruebas funcionales atómicas en `POSRestaurantTest.php` validando flujos de mesas, KDS, transferencias y liberación automática. Suite completa de 115 tests en verde.
- npm checks: compile y linter exitosos.

### 2026-07-10 — Fase 11: Impresión y TicketBuilder
**Agregado**
- Columnas `printer_paper_width` y `printer_name` añadidas en la migración de la tabla `cash_registers`.
- Modelo `CashRegister` actualizado con los nuevos campos en `$fillable`.
- Helper `TicketBuilder` para formatear y concatenar comandos binarios ESC/POS estándar (negritas, alineación, fuentes dobles, corte automático de papel) adaptables a anchos de 58mm (32 cols) y 80mm/88mm (48 cols).
- Endpoints en `PrintController` para renderizado HTML de ticket de 80mm e impresión formal Carta/A4 con reglas `@media print` de márgenes cero y eliminación de cabeceras de navegador.
- Integración en la terminal POS de un dropdown de ancho de papel (`Ticket 80mm`, `Ticket 58mm`, `Factura A4`) persistido en `window.localStorage`.
- Técnica SPA de impresión silenciosa y autenticada en `printTicket` que descarga el HTML a través de Axios e inyecta la impresión mediante un iframe invisible en el DOM.

**Validado**
- Pest: 4 pruebas robustas en `POSPrintTest.php` validando comandos ESC/POS para facturas, HTML de tickets de 80mm, HTML A4 e impresión de cierres de sesión de caja. Suite completa de 109 tests pasando en verde.
- npm checks: clean compile de producción y linter impecable.

### 2026-07-10 — Fase 9: Facturación y NCF tradicional
**Agregado**
- Tablas en migración `create_invoice_tables`: `invoices` y `invoice_items`.
- Modelos Eloquent backend: `Invoice` e `InvoiceItem` en el nuevo dominio `App\Modules\Invoice\Models` con soporte de ULID público y relaciones.
- Lógica de emisión fiscal en `InvoiceService` reservando NCF mediante bloqueos de concurrencia (`lockForUpdate`), anulación restituyendo stock a almacenes, y emisión de Notas de Crédito (B04) vinculadas.
- Endpoints en `InvoiceController` expuestos bajo middleware de tenant y políticas RBAC.
- Interfaces TypeScript actualizadas en el frontend.
- Integración en la UI del selector fiscal (B02/B01) en la pasarela de cobro del POS, con advertencia interactiva ante RNC ausente para Crédito Fiscal.
- Modal de visor de ticket fiscal de 80mm imitando papel térmico detallando RNC, NCF, ITBIS (18%) y Propina (10%), con gatillo de impresión.

**Validado**
- Pest: 4 pruebas de integración robustas en `POSInvoiceTest.php` validando asignación de NCF, protección por módulo, anulación auditada regresando stock y notas de crédito con FEFO. Suite completa de 105 tests pasando en verde.
- npm checks: compile clean con `npm run lint` y `npm run build` a producción.

### 2026-07-10 — Fase 8: Caja y pagos
**Agregado**
- Tablas en migración `create_cash_and_payment_tables`: `cash_registers`, `cash_sessions`, `cash_movements` y `payments`.
- Modelos Eloquent backend: `CashRegister`, `CashSession`, `CashMovement` y `Payment` en el dominio `App\Modules\POS\Models`.
- Servicio de control de turnos de caja `CashSessionService` implementando apertura con fondo, registro de ingresos/egresos y arqueos con diferencias.
- Modificación en `CreateOrderAction` para requerir un turno de caja activo, persistir cobros mixtos y multimoneda USD, y calcular la devuelta automática.
- Controladores y endpoints de API en `CashSessionController` para gestionar el ciclo de vida de la caja.
- Frontend: interfaces actualizadas en `services.ts` y terminal táctil en `PosPage.vue` con restricción de apertura de caja, movimientos de efectivo, arqueos y cobros con billetes rápidos dominicanos.

**Validado**
- Pest: 4 pruebas de integración en `POSCashRegisterTest.php` validando el ciclo de caja, egresos, cobros multimoneda, cambios y aislamiento. Total 101 tests en verde.
- npm checks: `npm run lint` y `npm run build` compilando al 100% de forma limpia.

### 2026-07-10 — Fase 7: POS Touch y órdenes de venta
**Agregado**
- Tablas en migración `create_pos_order_tables`: `orders` y `order_items`.
- Modelos Eloquent backend: `Order` y `OrderItem` bajo el dominio `App\Modules\POS\Models`.
- Servicio central de idempotencia `IdempotencyService` en el core para evitar cobros/ventas duplicadas asociadas a la cabecera `Idempotency-Key` (persiste en caché por 24 horas).
- Lógica de cálculo en `CreateOrderAction` que gestiona subtotales, neto, ITBIS (18%) y la propina de ley dominicana (10% exenta de ITBIS) para negocios gastronómicos.
- Integración en `CreateOrderAction` con `InventoryService` para descontar existencias físicas en ventas directas (`completed`).
- Permisos del catálogo ampliados para el POS en `PermissionCatalog.php` (`pos.view`, `pos.sell`).
- Frontend: interfaces TypeScript actualizadas en `services.ts` y servicio local de almacenamiento IndexedDB en `indexeddb.ts` para persistencia sin conexión (offline).
- Interfaz gráfica responsiva táctil del POS en `PosPage.vue` con buscador de productos, carrito interactivo, teclado, modal de cobro con almacén/cliente/propina y sincronizador offline automático.

**Validado**
- Pest: 5 pruebas de integración en `POSOrderTest.php` validando protección de ruta, cálculos de ITBIS/Propina, reverso de stock, prevención de duplicados con idempotencia y fallos por stock insuficiente. Total 97 tests en verde.
- npm checks: `npm run lint` (ESLint) y `npm run build` (Vite) compilando al 100% de forma limpia.

### 2026-07-10 — Fase 6: inventario avanzado y flujo de compras
**Agregado**
- Tablas adicionales en migración `create_inventory_and_purchase_tables`: `suppliers`, `warehouses`, `inventory_stock`, `inventory_batches`, `inventory_movements`, `purchases` y `purchase_items`.
- Modelos Eloquent backend: `Supplier`, `Warehouse`, `InventoryStock`, `InventoryBatch`, `InventoryMovement`, `Purchase` y `PurchaseItem` bajo `App\Modules\Inventory\Models`.
- Incorporación del trait `Auditable` para el registro automático de auditoría en los modelos principales del inventario.
- `InventoryService` centralizado que gestiona entradas, salidas con métodos **FEFO** (fecha de expiración), **FIFO** (fecha de creación) e **Costo Promedio** transaccional con bloqueos de concurrencia `lockForUpdate()`.
- Acción de confirmación de compras `PurchaseConfirmAction` para la carga atómica al inventario y asignación de lotes.
- Permisos del catálogo ampliados para proveedores, almacenes, compras e inventario en `PermissionCatalog.php`.
- Descubrimiento de rutas de API configurado a través del manifiesto `module.json` en `Inventory`.
- Frontend: interfaces TypeScript, axios wrapper en `services.ts` y enrutador configurado con middleware de protección por módulo.
- Interfaz gráfica responsiva `InventoryPage.vue` con pestañas interactivas para existencias agregadas con despliegue de **Kardex**, CRUD de almacenes, CRUD de proveedores y registro/confirmación de compras a proveedores con lotes.

**Validado**
- Pest: 6 pruebas de integración y funcionales en `InventoryAdvancedTest.php` validando el cálculo de costo promedio, consumo FEFO, FIFO, validación de stock insuficiente, confirmación de compras y aislamiento tenant. Total 92 tests en verde.
- npm checks: `npm run lint` (ESLint) y `npm run build` (Vite) compilando al 100% de forma exitosa.

### 2026-07-10 — Fase 5: catálogo de productos con variantes, modificadores y combos
**Agregado**
- Tablas adicionales en migración `create_catalog_tables`: `product_variants`, `product_modifiers`, `product_modifier_options` y `product_combos`.
- Modelos Eloquent backend: `ProductVariant`, `ProductModifier`, `ProductModifierOption` y `ProductCombo`.
- Relaciones Eloquent `variants()`, `modifiers()` y `combos()` en modelo `Product`.
- Validación y persistencia atómica/transaccional en `StoreProductRequest`, `UpdateProductRequest` y `SaveProductAction`.
- Formateo y exposición de variantes, modificadores y combos en `ProductResource`.
- Frontend: interfaces TypeScript actualizadas en `types.ts`, payload mapeado en `services.ts`, y paneles interactivos en `ProductListPage.vue` para agregar/editar variantes, modificadores y componentes de combo.

**Validado**
- Pest: 6 pruebas avanzadas cubriendo creación/edición de variantes, modificadores y combos, reglas de no auto-ciclado y aislamiento multi-tenant en `ProductAdvancedCatalogTest.php`. Total 86 pruebas en verde.
- npm checks: `npm run typecheck` (vue-tsc), `npm run lint` (ESLint) y `npm run format:check` (Prettier) en verde.

### 2026-07-10 — Fase 3 (cierre): settings por grupo y tasas de cambio
**Agregado**
- Tabla `settings` (clave-valor por compañía/sucursal/grupo). `SettingsSchema` define grupos POS/inventario/facturación/impresión/seguridad/backup con tipos y valores por defecto; `SettingsService` valida y castea (bool/int/decimal/enum).
- API `GET/PUT /settings/{group}` (esquema + valores efectivos) y `GET/POST /exchange-rates` (histórico de tasas por moneda y fecha).
- Frontend: sección de tasas de cambio (tabla + alta) y "Preferencias operativas" con tarjetas de grupo renderizadas desde el esquema (checkbox/select/número) en la pantalla de Configuración.

**Validado**
- Pest: 6 pruebas nuevas (defaults por grupo, persistencia tipada, enum inválido, grupo inexistente, alta/listado de tasas, permisos). Suite total 71 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build sin errores.
- Navegador real (Playwright): edición de "método de salida" a FIFO persiste tras recargar; alta de tasa USD 60.5 con fecha 2026-07-13; consola sin errores.

### 2026-07-10 — Fase 4: módulo de clientes
**Agregado**
- Tablas `customers`, `customer_addresses`, `customer_contacts`, `customer_credit_accounts`.
- Módulo `Customer`: modelo con crédito/balance, `CustomerCreditService` (cargos/abonos atómicos con `lockForUpdate`, control de límite), acciones de alta/edición, `ProvisionGenericCustomer` (Consumidor Final por compañía).
- Validación fiscal dominicana: `DominicanTaxId` (RNC módulo 11, cédula Luhn) + regla `ValidTaxId`.
- API `/customers` (búsqueda, paginación), `/customers/{id}`, `/customers/{id}/credit`; Policy y permisos `customers.view/manage/credit`.
- Frontend: directorio de clientes con búsqueda en vivo y formulario de alta/edición, enlazado al menú (módulo `customer`).

**Validado**
- Pest: 7 pruebas nuevas (dígitos verificadores RNC/cédula, genérico por compañía, alta con RNC válido, rechazo de RNC inválido, crédito con límite, aislamiento tenant, permisos). Suite total 65 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build sin errores.
- Navegador real (Playwright): consumidor final presente, RNC inválido rechazado con mensaje, alta de "Distribuidora XYZ SRL (RNC 131793916)", búsqueda filtrando.

### 2026-07-10 — Fase 3: base de configuración fiscal (RD)
**Agregado**
- Tablas: `currencies`, `company_currencies`, `exchange_rates`, `taxes`, `payment_methods`, `document_types`, `ncf_sequences`.
- `FiscalCatalog` (monedas DOP/USD/EUR, tipos de comprobante NCF/e-CF, impuestos y métodos de pago por defecto) + `ConfigurationSeeder` (catálogos globales).
- `ProvisionCompanyConfiguration`: al crear una compañía se provisionan moneda base, ITBIS 18/16/exento, propina legal 10% y métodos de pago (efectivo/tarjeta/transferencia/crédito).
- `NcfSequenceService::reserve()`: reserva atómica de NCF/e-NCF con `lockForUpdate()`, formato de 8 (NCF) o 10 dígitos (e-NCF), control de vencimiento, agotamiento y alertas.
- API bajo `/api/v1`: `GET /settings/fiscal`, `POST /taxes`, `PATCH /taxes/{id}`, `POST /payment-methods`, `GET/POST /ncf-sequences` (permisos `settings.view`/`settings.manage`).
- Frontend: pantalla de Configuración fiscal (impuestos, métodos de pago, secuencias NCF con alta) enlazada al menú dinámico (módulo `setting`).

**Validado**
- Pest: 12 pruebas nuevas (NCF: consecutivos únicos, e-NCF 10 dígitos, agotamiento, vencimiento, inexistente, aislamiento, alertas; provisión por compañía; API de configuración y permisos). Suite total 58 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build sin errores.
- Navegador real (Playwright): pantalla de Configuración renderiza, selector con los 12 tipos DGII, alta de secuencia B02 (1–1000) visible en tabla, consola sin errores.

### 2026-07-10 — Fase 2: sistema de módulos y onboarding
**Agregado**
- Tablas del sistema de módulos: `business_types`, `system_modules`, `module_dependencies`, `business_type_modules`, `subscription_plans`, `plan_modules`, `company_subscriptions`, `company_modules`, `branch_modules`, `module_audit_logs`.
- `ModuleCatalog` como fuente de verdad (40 módulos, 14 tipos de negocio, 24 dependencias, presets §9) proyectada por `ModuleSystemSeeder`; 3 planes con límites.
- `ModuleManagerService`: núcleo siempre activo, validación de dependencias y plan, aplicación de presets ordenada por dependencias, auditoría de cambios y caché por compañía.
- Middleware `module:<código>` (403 `MODULE_DISABLED`) registrado como alias.
- API bajo `/api/v1`: `GET /modules`, `POST /modules/{code}/enable|disable`, `GET /business-types`, `POST /onboarding`, protegidas por `modules.view`/`modules.manage`.
- Frontend: `useModuleStore`, guard `requiresModule` con redirección a onboarding, menú dinámico por módulos activos, pantalla de gestión de módulos y wizard de onboarding (tipo de negocio → módulos).

**Validado**
- Pest: 13 pruebas nuevas (servicio + API): núcleo, dependencias, presets, desactivación con dependientes, aislamiento tenant, permisos. Suite total 46 verde.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.
- Navegador real (Playwright): registro → compañía → onboarding con preset de colmado → menú dinámico (POS/Inventario/Clientes) → bloqueo de desactivación de Inventario por dependiente activo.

### 2026-07-09 — Fase 1: Policies de recursos SaaS
**Agregado**
- Policies explícitas para compañía, sucursal, rol y bitácora; los controladores las aplican además de los middleware de ruta.
- Método único de autorización por compañía para membresía, propietario y permisos, reutilizado por middleware y Policies.

**Corregido**
- La consulta de roles durante una decisión de Policy deja de depender del global scope del contexto y se limita explícitamente al tenant del recurso.

**Validado**
- Pest cubre permisos por recurso, denegación entre compañías y cuenta inactiva; la suite completa de calidad se ejecuta al cierre.

### 2026-07-09 — Fase 1: interfaz de sucursales
**Agregado**
- Ruta `/sucursales` para listar, crear y actualizar puntos de operación de la empresa activa.
- Formulario con nombre, código, teléfono, dirección y estado; la sucursal principal se identifica y no puede desactivarse desde la UI.

**Validado**
- Vitest cubre la protección visual de la sucursal principal. Navegador real verificó alta de sucursal secundaria y la protección de la principal sin errores de consola.
- Typecheck, ESLint, Prettier y build se ejecutaron al cierre del cambio.

### 2026-07-09 — Fase 1: interfaz de usuarios por compañía
**Agregado**
- Ruta `/usuarios` para provisionar una cuenta existente o modificar su acceso en la empresa activa.
- Selección de sucursales activas, sucursal predeterminada y roles; no permite enviar una asignación sin sucursal.
- El propietario se distingue y queda deshabilitado en la UI, consistente con el bloqueo del API.
- Los roles del sistema se excluyen de la UI y el servicio los rechaza para impedir escalamiento de privilegios.

**Validado**
- Vitest cubre la carga de datos y la validación de sucursal; Pest cubre el rechazo de roles del sistema.
- Navegador real verificó la ruta con sesión y contexto tenant, incluida la exclusión del rol propietario. Typecheck, ESLint, Prettier y build se ejecutaron al cierre del cambio.

### 2026-07-09 — Fase 1: interfaz de roles y permisos
**Agregado**
- Ruta `/roles` con listado, editor de permisos por código, creación, edición y desactivación de roles personalizados.
- Estados de carga/error y controles deshabilitados para roles del sistema, consistentes con Kinetic Enterprise.

### 2026-07-09 — Fase 1: CRUD seguro de roles
**Agregado**
- Catálogo API de permisos por código y actualización/desactivación lógica de roles personalizados.
- El contrato de roles usa `permission_codes`, sin exponer IDs internos; se impide alterar roles del sistema o desactivar roles asignados.

**Validado**
- Pest cubre permisos públicos, actualización, auditoría, propietario del sistema y desactivación segura.

### 2026-07-09 — Fase 1: acceso de usuarios por compañía
**Agregado**
- Listado y provisión de cuentas ya registradas, con sincronización de sucursales, roles y sucursal predeterminada por compañía.
- Protección contra referencias de otro tenant, preservación de accesos externos, bloqueo de edición del propietario y auditoría de cambios.

**Validado**
- Pest cubre provisión, aislamiento multicompañía, datos ajenos y regla de propietario.

### 2026-07-09 — Fase 1: administración de sucursales
**Agregado**
- Endpoints de listado, creación y actualización de sucursales bajo compañía activa y permiso `company.manage`.
- Validación de código único por compañía, auditoría de altas/cambios, asignación automática del administrador y bloqueo de desactivación de la sucursal principal.

**Validado**
- Pest cubre aislamiento de tenant, permisos, auditoría, actualización y la regla de sucursal principal.

### 2026-07-09 — Fase 1: bitácora de auditoría consultable
**Agregado**
- Módulo `Audit` con endpoints paginados de listado y detalle, protegidos por compañía y `audit.view`.
- ULID público e índices de consulta para `audit_logs`; los valores potencialmente secretos se redactan antes de exponerlos.
- Pantalla `/auditoria` con filtros de módulo/fecha, estados de carga, vacío y error, paginación y detalle del evento.

**Validado**
- Pest cubre aislamiento de tenant, autorización heredada de `audit.view`, redacción y filtros. La verificación completa de calidad y navegador se registra al cierre de este cambio.

### 2026-07-09 — Corrección de navegación SPA
**Corregido**
- Las rutas directas del cliente (por ejemplo `/ingresar`) entregan el shell Vue en lugar de responder 404.
- El fallback de la SPA excluye `/api/*`, por lo que un endpoint API inexistente ya no devuelve HTML con HTTP 200 ni oculta errores de autorización.

**Validado**
- Pest cubre la carga directa de ruta SPA.

### 2026-07-09 — Fase 1: acceso y contexto operativo
**Agregado**
- UI Vue para login, registro, creación inicial de compañía y pase de turno compañía/sucursal.
- Store Pinia persistente y cliente API que envía token Sanctum y cabeceras tenant por solicitud.

**Validado**
- Playwright verificó las pantallas de acceso y registro en navegador real.
- Pest 19/63, Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Fase 1: autenticación API segura
**Agregado**
- Módulo `Auth` con registro, login, logout y perfil actual bajo `/api/v1/auth`.
- Tokens Sanctum por dispositivo, revocación del token actual, rate limits, reglas de contraseña y bloqueo de cuentas inactivas.
- Auditoría de registro, login, fallo de login, bloqueo y logout.

**Validado**
- Pest: 19 pruebas / 63 aserciones; Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Fase 1: RBAC por compañía
**Agregado**
- Tablas `permissions`, `roles`, `permission_role` y `role_user`, con restricciones únicas e índices de consulta por compañía.
- Catálogo mínimo de permisos, rol de propietario aprovisionado al crear una compañía y middleware `permission:<código>`.
- API de roles protegida: `GET/POST /api/v1/roles`.

**Validado**
- Pest: 15 pruebas / 43 aserciones, incluyendo permiso otorgado, denegación y alta de rol.
- Pint, Larastan, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

### 2026-07-09 — Fase 1: aislamiento multiempresa base
**Agregado**
- Módulo `Company` con compañías, sucursales, membresías y ULID público para selección segura.
- Acción transaccional que crea compañía, sucursal principal, membresías de propietario y auditoría.
- `GET/POST /api/v1/companies`, Form Request, Resources, `CurrentCompany` y middleware `company`/`branch`.

**Validado**
- Migraciones aplicadas a la base exclusiva `omnipos`.
- Pest: 12 pruebas / 33 aserciones; Pint, Larastan nivel 6, typecheck, ESLint, Prettier, Vitest y build PWA sin errores.

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
