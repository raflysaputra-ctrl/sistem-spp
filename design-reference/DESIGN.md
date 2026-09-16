---
name: Academic Ledger
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#444653'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#757684'
  outline-variant: '#c4c5d5'
  surface-tint: '#3755c3'
  primary: '#00288e'
  on-primary: '#ffffff'
  primary-container: '#1e40af'
  on-primary-container: '#a8b8ff'
  inverse-primary: '#b8c4ff'
  secondary: '#505f76'
  on-secondary: '#ffffff'
  secondary-container: '#d0e1fb'
  on-secondary-container: '#54647a'
  tertiary: '#611e00'
  on-tertiary: '#ffffff'
  tertiary-container: '#872d00'
  on-tertiary-container: '#ffa583'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dde1ff'
  primary-fixed-dim: '#b8c4ff'
  on-primary-fixed: '#001453'
  on-primary-fixed-variant: '#173bab'
  secondary-fixed: '#d3e4fe'
  secondary-fixed-dim: '#b7c8e1'
  on-secondary-fixed: '#0b1c30'
  on-secondary-fixed-variant: '#38485d'
  tertiary-fixed: '#ffdbce'
  tertiary-fixed-dim: '#ffb59a'
  on-tertiary-fixed: '#380d00'
  on-tertiary-fixed-variant: '#802a00'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
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
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  mono-data:
    fontFamily: Courier Prime
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  container-max: 1440px
  sidebar-width: 260px
  gutter: 24px
  margin-mobile: 16px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 24px
---

## Brand & Style

The design system is engineered for school administrative environments where clarity, speed, and accuracy are paramount. The brand personality is rooted in institutional trust and operational efficiency, aiming to reduce the cognitive load on staff managing high volumes of financial transactions.

The visual style follows a **Modern Corporate** approach, blending high-utility minimalism with a structured information hierarchy. It prioritizes data integrity over decorative flair. The aesthetic utilizes generous whitespace to prevent "data fatigue," ensuring that critical status indicators (like payment arrears) are immediately identifiable. The interface feels stable, professional, and authoritative, fostering a sense of reliability for both the staff and the parents they serve.

## Colors

The palette is anchored by "Education Blue," a deep, stable primary color that evokes institutional authority. 

- **Primary (#1E40AF):** Used for primary actions, active navigation states, and key headers.
- **Secondary / Admin Gray (#64748B):** Used for auxiliary text, icons, and non-critical UI elements.
- **Semantic Statuses:** 
    - **Success (#10B981):** Exclusively for 'Lunas' (Paid) indicators and positive balance confirmations.
    - **Warning (#F59E0B):** For 'Menunggu Konfirmasi' or upcoming due dates.
    - **Danger (#EF4444):** Reserved for 'Belum Bayar' (Arrears) and critical overdue alerts.
- **Surface & Background:** A clean, cool neutral palette (utilizing Slate/Gray scales) provides a non-distracting canvas for financial tables.

## Typography

This design system uses **Inter** for all functional UI elements due to its exceptional legibility in data-heavy environments and tall x-height. 

Typography is used to create a clear "read-path":
- **Financial Data:** Use `mono-data` for invoice numbers or currency amounts to ensure numerical alignment in tables.
- **Hierarchy:** Headings use tighter letter spacing and heavier weights to stand out against white backgrounds.
- **Labels:** Small caps or uppercase labels are used for table headers and form descriptors to differentiate them from the user's input.

## Layout & Spacing

The layout utilizes a **Fixed Sidebar + Fluid Content** model. 

1. **Sidebar:** A persistent 260px left-hand navigation allows for rapid switching between "Daftar Siswa," "Tagihan," and "Laporan Keuangan."
2. **The Grid:** A 12-column grid system is used for the main content area. Data tables should ideally span all 12 columns, while "Summary Cards" (Total Collected, Total Arrears) should be arranged in 3 or 4-column groupings.
3. **Spacing Rhythm:** An 8px base unit is used throughout. 24px (stack-lg) is the standard padding for containers and cards to ensure the interface feels airy despite the density of information.
4. **Mobile Adaptability:** On mobile devices, the sidebar collapses into a bottom navigation bar or a hamburger menu, and the 12-column grid collapses into a single-column stack.

## Elevation & Depth

This design system uses a **Low-Contrast Outline** strategy to maintain a professional, flat aesthetic. 

- **Surfaces:** Use flat white backgrounds for cards with a subtle 1px border (`#E2E8F0`). 
- **Shadows:** Avoid heavy, dark shadows. Use a single "Elevation-1" shadow (very diffused, 4% opacity) only for floating elements like dropdown menus or modals to separate them from the base layer.
- **Tonal Layers:** The main application background uses `neutral_color_hex` (#F8FAFC) to make white content cards "pop" without requiring deep shadows. This creates a clean, tiered look that feels modern and lightweight.

## Shapes

The design system uses a **Soft (0.25rem)** roundedness profile. This subtle rounding softens the "industrial" feel of a data-heavy system while maintaining the precision expected of financial software.

- **Standard Buttons & Inputs:** 4px (0.25rem) radius.
- **Content Cards:** 8px (0.5rem) radius for a slightly softer container appearance.
- **Status Badges:** 100px (Pill-shaped) to distinguish them clearly from interactive buttons.

## Components

### Data Tables
The core of the system. Tables must feature:
- Sticky headers for long student lists.
- 1px horizontal dividers only; avoid vertical lines to reduce visual noise.
- Row highlighting on hover using a very faint blue tint.

### Status Badges
High-contrast indicators for payment status:
- **Lunas:** Green background (10% opacity) with dark green text.
- **Belum Bayar:** Red background (10% opacity) with dark red text.
- **Cicilan:** Orange background (10% opacity) with dark orange text.

### Professional Cards
Summary cards at the top of the dashboard should display large currency values. Use a vertical accent bar (4px wide) on the left side of the card colored by its category (e.g., Blue for Total, Green for Collected).

### Buttons
- **Primary:** Solid "Education Blue" with white text.
- **Secondary:** Transparent with a gray border for "Download Receipt" or "Edit" actions.
- **Ghost:** No border or background, used for "Cancel" or auxiliary table actions.

### Input Fields
Strict, rectangular fields with 1px gray borders. On focus, the border transitions to Primary Blue with a subtle 2px outer glow. Labels must always remain visible (no floating labels that disappear on click).