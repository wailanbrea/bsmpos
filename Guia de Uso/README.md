# Guía de Uso — OmniPOS

Guía operativa con imágenes reales del sistema. Cada carpeta cubre el **ciclo
completo** de un tipo de negocio, desde el acceso hasta la emisión del
comprobante o el cierre del flujo.

## Antes de empezar

1. Lee primero **[Acceso al sistema](_comun/GUIA.md)** — es común a todos los giros: iniciar sesión y elegir la compañía/sucursal donde vas a operar.
2. Luego abre la guía de tu tipo de negocio.

## Guías por tipo de negocio

| Giro | Guía | Módulos que verás | Cuándo usarla |
|------|------|-------------------|----------------|
| 💈 Salón / Barbería | [Salon-Barberia/GUIA.md](Salon-Barberia/GUIA.md) | Empleados, Agenda de citas, POS | Servicios por cita con barbero/estilista y comisión |
| 🔧 Ferretería | [Ferreteria/GUIA.md](Ferreteria/GUIA.md) | Productos, Inventario, POS, Reportes | Venta al detalle de materiales con control de existencias |
| 🍽️ Restaurante | [Restaurante/GUIA.md](Restaurante/GUIA.md) | Mesas, Cocina (KDS), POS | Servicio en mesa, comandas a cocina |
| 🚗 Taller mecánico | [Taller/GUIA.md](Taller/GUIA.md) | Vehículos, Órdenes de trabajo | Diagnóstico, servicios, repuestos y mano de obra por vehículo |
| 🛒 Supermercado | [Supermercado/GUIA.md](Supermercado/GUIA.md) | Inventario avanzado (lotes/vencimientos), POS | Venta al detalle con control de lotes y caducidad (FEFO) |

## Cuentas de demostración

Todas las cuentas usan la contraseña **`Password123!`**. Cada una entra a una
empresa ya configurada para su giro, con datos de muestra.

| Giro | Correo | Empresa |
|------|--------|---------|
| Colmado / Minimarket | `demo@omnipos.test` | OmniPOS Demo |
| Restaurante | `demo.restaurante@omnipos.test` | Restaurante El Fogón |
| Salón / Barbería | `demo.barberia@omnipos.test` | Barbería La Navaja |
| Taller mecánico | `demo.taller@omnipos.test` | Taller AutoMax |
| Cafetería | `demo.cafeteria@omnipos.test` | Cafetería Aroma |
| Supermercado | `demo.super@omnipos.test` | Supermercado La Económica |
| Ferretería | `demo.ferreteria@omnipos.test` | Ferretería El Tornillo |
| Distribuidora | `demo.distribuidora@omnipos.test` | Distribuidora del Cibao |
| Servicios profesionales | `demo.servicios@omnipos.test` | Consultores Pro |

> El menú lateral y las pantallas se adaptan **solos** según el giro: por
> ejemplo, "Mesas" y "Cocina KDS" solo aparecen en el restaurante, y
> "Vehículos" y "Órdenes" solo en el taller.

## Idea clave: módulos activables

OmniPOS es modular. Cada empresa enciende solo los módulos que necesita
(POS, inventario, citas, mesas, etc.) desde **Configuración → Módulos**.
Apagar un módulo nunca borra datos históricos. Por eso una barbería ve la
Agenda y una ferretería ve el Inventario, aunque sea la misma aplicación.
