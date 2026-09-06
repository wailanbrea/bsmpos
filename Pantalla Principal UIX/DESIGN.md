---
name: Enterprise SaaS Design System
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#464554'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#767586'
  outline-variant: '#c7c4d7'
  surface-tint: '#494bd6'
  primary: '#4648d4'
  on-primary: '#ffffff'
  primary-container: '#6063ee'
  on-primary-container: '#fffbff'
  inverse-primary: '#c0c1ff'
  secondary: '#5f5e61'
  on-secondary: '#ffffff'
  secondary-container: '#e4e1e6'
  on-secondary-container: '#656467'
  tertiary: '#595c5e'
  on-tertiary: '#ffffff'
  tertiary-container: '#727577'
  on-tertiary-container: '#fbfdff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e1e0ff'
  primary-fixed-dim: '#c0c1ff'
  on-primary-fixed: '#07006c'
  on-primary-fixed-variant: '#2f2ebe'
  secondary-fixed: '#e4e1e6'
  secondary-fixed-dim: '#c8c5ca'
  on-secondary-fixed: '#1b1b1e'
  on-secondary-fixed-variant: '#47464a'
  tertiary-fixed: '#e0e3e5'
  tertiary-fixed-dim: '#c4c7c9'
  on-tertiary-fixed: '#191c1e'
  on-tertiary-fixed-variant: '#444749'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  headline-lg:
    fontFamily: Geist
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Geist
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Geist
    fontSize: 16px
    fontWeight: '500'
    lineHeight: 24px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.02em
  code-md:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  sidebar-width: 260px
  bento-gap: 1rem
  grid-margin: 1.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system is engineered for professional enterprise SaaS platforms, targeting analysts, operators, and administrators who demand high information density, zero-latency feedback, and uncompromising clarity. The visual language is rooted in **Minimalism** with high-contrast functional accents, eschewing decorative noise in favor of systematic precision, structured surfaces, and content-first layouts.

The UI evokes an immediate sense of reliability, control, and intellectual calm. By coupling a commanding dark zinc sidebar for structural navigation with a clean, light neutral canvas for dense data work, the interface reduces cognitive load during prolonged enterprise workflows.

## Colors

The color palette is meticulously balanced for high-density enterprise interfaces. The primary indigo (`#6366f1`) anchors interactive states, primary actions, and focused elements. The secondary dark zinc (`#18181b`) commands the persistent navigation sidebar, creating a strong spatial anchor. The light neutral background (`#f8fafc`) optimizes legibility across expansive data grids.

Semantic status colors are strictly reserved for systemic feedback: emerald green (`#10b981`) denotes success and compliance states; amber (`#59e0b`) flags critical warnings requiring attention; crimson (`#ef4444`) signals errors and destructive actions.

## Typography

Typography establishes a strict, utilitarian hierarchy. Geist drives structural headlines with tight tracking for maximum scanning efficiency, while Inter powers body and functional text for optimal readability at small sizes. Monospaced elements utilize JetBrains Mono for data tables, identifiers, and code snippets. Font sizes are scaled conservatively to maximize information density on desktop viewports.

## Layout & Spacing

The layout is built on a high-density **bento grid** architecture combined with a fixed sidebar model. The dark zinc sidebar occupies a fixed width of 260px, while the primary workspace utilizes a fluid 12-column grid with 16px (`1rem`) gutters and generous outer margins. 

Content containers are modular cards that snap into the bento grid, allowing operators to configure dashboards with varying widget spans (1x1, 2x1, 2x2) without breaking visual alignment. Breakpoints strictly govern responsive reflow: Desktop (>1200px) displays full multi-column bento layouts; Tablet (768px - 1199px) collapses multi-span widgets; Mobile (<768px) converts the sidebar into a slide-over drawer and stacks all bento cards into a single-column feed.

## Elevation & Depth

Elevation relies on **low-contrast outlines** and structured tonal layering rather than heavy drop shadows. Surfaces are separated using crisp 1px borders in neutral tones (`#e2e8f0`), creating a flat, architectural aesthetic that prevents visual clutter in high-density workflows. 

Depth is communicated through subtle background shifts (e.g., pure white cards floating on the `#f8fafc` canvas) and intentional hover states that introduce micro-borders or faint tonal shifts. Shadows are strictly reserved for floating elements such as dropdown menus, tooltips, and modal dialogs, utilizing ultra-diffused, low-opacity ambient shadows.

## Shapes

The shape language employs a restrained **Soft** profile (`roundedness: 1`). UI elements feature a foundational 0.25rem radius for inputs, badges, and small components, scaling up to 0.5rem (`rounded-lg`) for bento cards and major structural containers. This subtle rounding softens the dense data environment without sacrificing the crisp, precise feel required of enterprise software.

## Components

All components must adhere strictly to the high-density constraints and token rules established across the system.

- **Buttons:** Primary actions utilize solid vibrant indigo (`#6366f1`) with crisp text and subtle hover darkening. Secondary actions use ghost or outlined styles with neutral borders. Destructive actions trigger crimson (`#ef4444`). All buttons maintain a compact 32px or 36px height for data-dense interfaces.
- **Chips & Badges:** Pill or soft-rectangle tags utilizing semantic background tints with matching deep text colors (e.g., emerald background tint with emerald text for success states).
- **Lists:** Dense, multi-column list items featuring alternating row highlights or clean horizontal dividers with 40px row heights for rapid scanning.
- **Checkboxes & Radio Buttons:** Compact 16px square/circular controls with crisp borders, utilizing primary indigo for checked states.
- **Input Fields:** 36px height text inputs featuring low-contrast outlines, clear placeholder typography, and inline validation states tied to semantic colors (amber for warnings, crimson for errors).
- **Cards (Bento Grid):** White container surfaces with 1px neutral borders and 0.5rem corner rounding, optimized for housing charts, metrics, or data tables.
- **Data Tables:** Core component featuring sticky headers, monospace data columns, dense padding (8px vertical), and inline action menus.