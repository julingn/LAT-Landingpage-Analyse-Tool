---
name: ui-ux-pro-max
description: "Comprehensive design guide for web and mobile applications. Contains 67 styles, 161 color palettes, 57 font pairings, 99 UX guidelines, and 25 chart types across 15 technology stacks. Searchable database with priority-based recommendations."
---

# UI/UX Pro Max — Design Intelligence

Comprehensive design guide for web and mobile applications. Contains 67 styles, 161 color palettes, 57 font pairings, 161 product types with reasoning rules, 99 UX guidelines, and 25 chart types across 15 technology stacks. Searchable database with priority-based recommendations.

## When to Apply

This skill should be used when the task involves **UI structure, visual design decisions, interaction patterns, or user experience quality control**.

### Must Use
- Designing new pages (Landing Page, Dashboard, Admin, SaaS, Mobile App)
- Creating or refactoring UI components (buttons, modals, forms, tables, charts, etc.)
- Choosing color schemes, typography systems, spacing standards, or layout systems
- Reviewing UI code for user experience, accessibility, or visual consistency
- Implementing navigation structures, animations, or responsive behavior
- Making product-level design decisions (style, information hierarchy, brand expression)
- Improving perceived quality, clarity, or usability of interfaces

### Skip
- Pure backend logic development
- Only involving API or database design
- Performance optimization unrelated to the interface
- Infrastructure or DevOps work
- Non-visual scripts or automation tasks

---

## Rule Categories by Priority

| Priority | Category | Impact | Key Checks | Anti-Patterns |
|----------|----------|--------|------------|---------------|
| 1 | Accessibility | CRITICAL | Contrast 4.5:1, Alt text, Keyboard nav, Aria-labels | Removing focus rings, Icon-only buttons without labels |
| 2 | Touch & Interaction | CRITICAL | Min size 44×44px, 8px+ spacing, Loading feedback | Reliance on hover only, Instant state changes (0ms) |
| 3 | Performance | HIGH | WebP/AVIF, Lazy loading, Reserve space (CLS < 0.1) | Layout thrashing, Cumulative Layout Shift |
| 4 | Style Selection | HIGH | Match product type, Consistency, SVG icons (no emoji) | Mixing flat & skeuomorphic randomly, Emoji as icons |
| 5 | Layout & Responsive | HIGH | Mobile-first breakpoints, Viewport meta, No horizontal scroll | Horizontal scroll, Fixed px container widths, Disable zoom |
| 6 | Typography & Color | MEDIUM | Base 16px, Line-height 1.5, Semantic color tokens | Text < 12px body, Gray-on-gray, Raw hex in components |
| 7 | Animation | MEDIUM | Duration 150–300ms, Motion conveys meaning, Spatial continuity | Decorative-only animation, Animating width/height, No reduced-motion |
| 8 | Forms & Feedback | MEDIUM | Visible labels, Error near field, Helper text, Progressive disclosure | Placeholder-only label, Errors only at top |
| 9 | Navigation Patterns | HIGH | Predictable back, Bottom nav ≤5, Deep linking | Overloaded nav, Broken back behavior |
| 10 | Charts & Data | LOW | Legends, Tooltips, Accessible colors | Relying on color alone to convey meaning |

---

## Quick Reference

### 1. Accessibility (CRITICAL)
- `color-contrast` — Minimum 4.5:1 ratio for normal text (large text 3:1)
- `focus-states` — Visible focus rings on interactive elements (2–4px)
- `alt-text` — Descriptive alt text for meaningful images
- `aria-labels` — aria-label for icon-only buttons
- `keyboard-nav` — Tab order matches visual order; full keyboard support
- `form-labels` — Use label with for attribute
- `skip-links` — Skip to main content for keyboard users
- `heading-hierarchy` — Sequential h1→h6, no level skip
- `color-not-only` — Don't convey info by color alone (add icon/text)
- `reduced-motion` — Respect prefers-reduced-motion; reduce/disable animations

### 2. Touch & Interaction (CRITICAL)
- `touch-target-size` — Min 44×44pt (Apple) / 48×48dp (Material)
- `touch-spacing` — Minimum 8px gap between touch targets
- `hover-vs-tap` — Use click/tap for primary interactions; don't rely on hover alone
- `loading-buttons` — Disable button during async operations; show spinner
- `error-feedback` — Clear error messages near problem
- `cursor-pointer` — Add cursor-pointer to clickable elements (Web)

### 3. Performance (HIGH)
- `image-optimization` — Use WebP/AVIF, responsive images (srcset/sizes), lazy load
- `image-dimension` — Declare width/height or use aspect-ratio to prevent CLS
- `font-loading` — Use font-display: swap/optional to avoid FOIT
- `bundle-splitting` — Split code by route/feature
- `lazy-loading` — Lazy load non-hero components
- `virtualize-lists` — Virtualize lists with 50+ items

### 4. Style Selection (HIGH)
- `style-match` — Match style to product type
- `consistency` — Use same style across all pages
- `no-emoji-icons` — Use SVG icons (Heroicons, Lucide), not emojis
- `color-palette-from-product` — Choose palette from product/industry
- `effects-match-style` — Shadows, blur, radius aligned with chosen style
- `dark-mode-pairing` — Design light/dark variants together

### 5. Layout & Responsive (HIGH)
- `viewport-meta` — width=device-width initial-scale=1 (never disable zoom)
- `mobile-first` — Design mobile-first, then scale up
- `breakpoint-consistency` — Use systematic breakpoints (375 / 768 / 1024 / 1440)
- `readable-font-size` — Minimum 16px body text on mobile
- `horizontal-scroll` — No horizontal scroll on mobile
- `spacing-scale` — Use 4pt/8dp incremental spacing system
- `container-width` — Consistent max-width on desktop (max-w-6xl / 7xl)
- `z-index-management` — Define layered z-index scale

### 6. Typography & Color (MEDIUM)
- `line-height` — Use 1.5–1.75 for body text
- `line-length` — Limit to 65–75 characters per line
- `font-pairing` — Match heading/body font personalities
- `font-scale` — Consistent type scale (12 14 16 18 24 32)
- `color-semantic` — Define semantic color tokens, not raw hex in components
- `color-dark-mode` — Dark mode uses desaturated/lighter tonal variants
- `color-accessible-pairs` — 4.5:1 (AA) or 7:1 (AAA)

### 7. Animation (MEDIUM)
- `duration-timing` — 150–300ms for micro-interactions; complex ≤400ms
- `transform-performance` — Use transform/opacity only; avoid width/height
- `loading-states` — Show skeleton or progress when loading >300ms
- `easing` — ease-out entering, ease-in exiting
- `exit-faster-than-enter` — Exit ~60–70% of enter duration
- `spring-physics` — Prefer spring/physics curves for natural feel
- `reduced-motion` — Always honor prefers-reduced-motion

### 8. Forms & Feedback (MEDIUM)
- `input-labels` — Visible label per input (not placeholder-only)
- `error-placement` — Show error below the related field
- `submit-feedback` — Loading then success/error state on submit
- `inline-validation` — Validate on blur, not keystroke
- `error-clarity` — Error messages must state cause + how to fix
- `focus-management` — After submit error, auto-focus first invalid field

### 9. Navigation Patterns (HIGH)
- `bottom-nav-limit` — Bottom navigation max 5 items; use labels with icons
- `back-behavior` — Back navigation must be predictable and consistent
- `modal-escape` — Modals must offer clear close/dismiss affordance
- `nav-hierarchy` — Primary vs secondary nav clearly separated
- `state-preservation` — Navigating back must restore scroll position and state

### 10. Charts & Data (LOW)
- `chart-type` — Match chart type to data (trend → line, comparison → bar)
- `color-guidance` — Avoid red/green only pairs for colorblind users
- `legend-visible` — Always show legend near the chart
- `tooltip-on-interact` — Tooltips on hover/tap showing exact values
- `empty-data-state` — Meaningful empty state when no data exists

---

## How to Use This Skill

Use this skill when the user requests any of the following:

| Scenario | Trigger Examples | Start From |
|----------|-----------------|------------|
| **New project / page** | "Build a landing page", "Build a dashboard" | Step 1 → Step 2 |
| **New component** | "Create a pricing card", "Add a modal" | Step 3 (style, ux) |
| **Choose style / color / font** | "What style fits a fintech app?" | Step 2 |
| **Review existing UI** | "Review this page for UX issues" | Quick Reference checklist |
| **Fix a UI bug** | "Button hover is broken", "Layout shifts on load" | Quick Reference |
| **Improve / optimize** | "Make this faster", "Improve mobile experience" | Step 3 (ux) |
| **Add charts / data viz** | "Add an analytics dashboard chart" | Step 3 (chart) |

### Step 1: Analyze User Requirements

Extract key information from user request:
- **Product type**: SaaS, E-commerce, Healthcare, Fintech, Portfolio, etc.
- **Target audience**: Consumer vs. professional; age group; usage context
- **Style keywords**: playful, vibrant, minimal, dark mode, content-first, premium, etc.
- **Stack**: React, Next.js, Vue, Svelte, HTML+Tailwind, SwiftUI, React Native, Flutter, etc.

### Step 2: Generate Design System (REQUIRED)

**Always start with `--design-system`** to get comprehensive recommendations:

```bash
python3 .github/prompts/ui-ux-pro-max/scripts/search.py "<product_type> <industry> <keywords>" --design-system [-p "Project Name"]
```

This command:
1. Searches domains in parallel (product, style, color, landing, typography)
2. Applies reasoning rules from `ui-reasoning.csv` to select best matches
3. Returns complete design system: pattern, style, colors, typography, effects
4. Includes anti-patterns to avoid

**Example:**
```bash
python3 .github/prompts/ui-ux-pro-max/scripts/search.py "beauty spa wellness service" --design-system -p "Serenity Spa"
```

### Step 2b: Persist Design System (Master + Overrides Pattern)

```bash
python3 .github/prompts/ui-ux-pro-max/scripts/search.py "<query>" --design-system --persist -p "Project Name"
```

Creates:
- `design-system/MASTER.md` — Global Source of Truth
- `design-system/pages/` — Folder for page-specific overrides

### Step 3: Supplement with Detailed Searches

```bash
python3 .github/prompts/ui-ux-pro-max/scripts/search.py "<keyword>" --domain <domain>
```

| Need | Domain | Example |
|------|--------|---------|
| Product type patterns | `product` | `--domain product "saas b2b"` |
| More style options | `style` | `--domain style "glassmorphism dark"` |
| Color palettes | `color` | `--domain color "fintech vibrant"` |
| Font pairings | `typography` | `--domain typography "elegant modern"` |
| Chart recommendations | `chart` | `--domain chart "real-time dashboard"` |
| UX best practices | `ux` | `--domain ux "animation accessibility"` |
| Landing structure | `landing` | `--domain landing "hero social-proof"` |

### Step 4: Stack Guidelines

```bash
python3 .github/prompts/ui-ux-pro-max/scripts/search.py "<keyword>" --stack <stack>
```

Available stacks: `react`, `nextjs`, `vue`, `nuxtjs`, `svelte`, `astro`, `html-tailwind`, `shadcn`, `swiftui`, `react-native`, `flutter`, `jetpack-compose`, `nuxt-ui`, `angular`, `threejs`

---

## Pre-Delivery Checklist

- [ ] No emojis as icons (use SVG: Heroicons/Lucide)
- [ ] cursor-pointer on all clickable elements
- [ ] Hover states with smooth transitions (150–300ms)
- [ ] Light mode: text contrast 4.5:1 minimum
- [ ] Focus states visible for keyboard nav
- [ ] prefers-reduced-motion respected
- [ ] Responsive: 375px, 768px, 1024px, 1440px
- [ ] No horizontal scroll on mobile
- [ ] All form fields have visible labels
- [ ] Error messages state cause + how to fix
- [ ] Touch targets ≥ 44×44pt / 48×48dp
- [ ] Dark mode contrast tested independently

---

## Prerequisites (Python Search Script)

The design system search requires Python 3:

```powershell
# Check
python3 --version

# Install on Windows
winget install Python.Python.3.12
```

> **Note:** The search script at `.github/prompts/ui-ux-pro-max/scripts/` must be installed separately.  
> Run `npm install -g uipro-cli && uipro init --ai copilot` in your project root to install the full database.
