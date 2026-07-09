---
name: Kinetic Enterprise
colors:
  surface: '#fcf8ff'
  surface-dim: '#dcd8e5'
  surface-bright: '#fcf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f2ff'
  surface-container: '#f0ecf9'
  surface-container-high: '#eae6f4'
  surface-container-highest: '#e4e1ee'
  on-surface: '#1b1b24'
  on-surface-variant: '#464555'
  inverse-surface: '#302f39'
  inverse-on-surface: '#f3effc'
  outline: '#777587'
  outline-variant: '#c7c4d8'
  surface-tint: '#4d44e3'
  primary: '#3525cd'
  on-primary: '#ffffff'
  primary-container: '#4f46e5'
  on-primary-container: '#dad7ff'
  inverse-primary: '#c3c0ff'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#960014'
  on-tertiary: '#ffffff'
  tertiary-container: '#bc1d25'
  on-tertiary-container: '#ffd0cc'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#0f0069'
  on-primary-fixed-variant: '#3323cc'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#ffdad7'
  tertiary-fixed-dim: '#ffb3ad'
  on-tertiary-fixed: '#410004'
  on-tertiary-fixed-variant: '#930013'
  background: '#fcf8ff'
  on-background: '#1b1b24'
  surface-variant: '#e4e1ee'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-lg:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.02em
  numeric-pos:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  touch-target-min: 44px
  touch-target-relaxed: 56px
  gutter: 16px
  margin-mobile: 16px
  margin-desktop: 32px
  container-gap: 24px
---

## Brand & Style

The design system is engineered for **OmniPOS**, a high-performance environment where speed, reliability, and clarity are non-negotiable. The brand personality is **Professional, Systematic, and Resilient**. It balances the clinical precision of an enterprise SaaS with the tactile responsiveness required for physical interaction.

The chosen style is **Tactile Modernism**. It leverages a "physical digital" approach: elements have a subtle sense of weight and depth to satisfy touch-based interactions, while the overall aesthetic remains clean and minimalist to prevent cognitive overload. High contrast is utilized strategically to ensure legibility under harsh retail lighting or low-light restaurant settings. The UI should evoke a sense of absolute control and immediate responsiveness.

## Colors

The color palette is functionally driven, prioritizing semantic clarity over decorative flair.

- **Primary (Deep Indigo):** Used for brand identity, global navigation, and administrative "power" actions.
- **Success (Vibrant Green):** Reserved for "Paid," "Available," and "Active" states. High saturation ensures visibility on busy grid views.
- **Danger (Sharp Red):** Indicates "Occupied," "Error," or "Out of Stock." It demands immediate attention.
- **Warning (Warm Amber):** Used for "Pending" transactions or "Low Stock" alerts.
- **Info (Corporate Blue):** Used for secondary interactions, help tooltips, and informational tags.
- **Neutral:** A sophisticated scale of grays from `#F9FAFB` (Surface) to `#111827` (Text). Neutral tones are cool-leaning to maintain a modern, professional feel.

In high-glare environments, use the `Neutral-900` for primary text on `Neutral-50` backgrounds to maximize the Contrast Ratio.

## Typography

**Inter** is the sole typeface for this design system, chosen for its exceptional legibility on digital screens and neutral character.

- **Readability:** Use `body-lg` for product names in checkout lists to ensure they are readable at arm's length.
- **Hierarchy:** Use `title-lg` for card headers and modal titles.
- **Data Entry:** The `numeric-pos` style is specifically for price displays and keypad entries, featuring high weight and increased letter spacing to prevent misreading digits.
- **Case:** Labels should use sentence case for maximum readability, except for short, high-priority status tags which may use uppercase with `label-md`.

## Layout & Spacing

This design system utilizes a **Hybrid Fluid Grid**.

- **Desktop/Tablet:** A 12-column grid with 24px gutters. Administrative views use a fixed-width sidebar (280px) for navigation, with a fluid content area.
- **Mobile:** A 4-column fluid grid. Navigation shifts to a fixed bottom bar with 4-5 primary touch points.
- **Spacing Rhythm:** Based on an 8px scale. All padding and margins must be multiples of 8.
- **Touch Targets:** A strict minimum of 44px for all interactive elements. In high-velocity checkout screens (e.g., Quick-Add buttons), use the `touch-target-relaxed` (56px) standard.
- **Safe Areas:** Ensure a 16px margin on mobile devices to prevent accidental touches near the bezel.

## Elevation & Depth

Elevation in this design system is used to communicate interactivity and focus. It employs **Tonal Layering** combined with **Ambient Shadows**.

- **Level 0 (Base):** The background layer (`#F9FAFB`).
- **Level 1 (Cards/Surface):** White surface with a subtle 1px border (`#E5E7EB`). No shadow. Used for secondary information.
- **Level 2 (Interactive):** White surface with a soft, diffused shadow (0px 4px 6px -1px rgba(0,0,0,0.1)). Used for primary product cards and buttons.
- **Level 3 (Overlay/Modals):** High-elevation shadows (0px 20px 25px -5px rgba(0,0,0,0.1)). Use a 40% opacity black backdrop blur (8px) to recede the background content.

**Tactile Feedback:** When pressed, buttons and cards should visually "sink" by removing the shadow and applying a subtle scale transform (98%), providing a physical sense of confirmation.

## Shapes

The shape language is **Rounded**, strike a balance between friendly and professional.

- **Standard Radius:** 8px (`0.5rem`) for cards, input fields, and buttons. This provides a modern look that feels accessible without looking "childish."
- **Large Radius:** 16px (`1rem`) for modals and major container groupings to clearly encapsulate complex information.
- **Pill Shapes:** Used exclusively for status chips (e.g., "Paid," "Pending") to differentiate them from interactive buttons.
- **Focus States:** 2px solid offset border using the `Primary-color`.

## Components

- **Buttons:** Primary buttons use a solid `#4F46E5` background with white text. Secondary buttons use a `1px` border with primary text. High-visibility "Pay" buttons should use the Success color (`#10B981`) and the `touch-target-relaxed` height.
- **Cards:** Product cards in the POS grid must include a clear price label in the bottom right using `label-lg`. The entire card area is a hit target.
- **Lists:** Transaction lists should use alternating row colors (`#F9FAFB` and `#FFFFFF`) for legibility. Each row must have a minimum height of 64px for easy selection.
- **Input Fields:** Use large, clear labels above the field. Inputs must have a 16px internal padding. On mobile, trigger the appropriate keyboard type (numeric vs. text) automatically.
- **Chips:** Small, pill-shaped indicators for status. Use low-opacity tints of the semantic colors for the background with high-contrast text (e.g., Success Chip: light green background, dark green text).
- **Keypads:** The numeric keypad should occupy at least 40% of the screen height on mobile to ensure speed and accuracy during manual entry.