# Component Inventory — LAT

> Übersicht aller **wiederverwendbaren UI-Komponenten** des Tools. Komponenten existieren als
> **CSS-Klassen** (`app/index.php`, `<style>` Z. 29–846) + **JS-Render-Funktionen** (Z. 1993–6313).
> Design-Tokens (Farben, Typo, Spacing, Shadow, Radius): `Documents/LAT-Design-System.md`.
>
> **Pflege-Regel:** Neue Komponente oder Variante → hier im selben Arbeitsschritt eintragen.

---

## Button

**Zweck:** Aktionen auslösen (Analyse starten, generieren, speichern, sekundäre Aktionen).

**Varianten**
- `primary` — `.btn-start`, `.pv-generate-btn`, `.cf-run-btn` (Accent-Hintergrund)
- `secondary` — `.btn-secondary` (dezent, Border)
- `demo` — `.btn-demo` (Header, Nebenaktion)
- `save` — `.btn-save`
- `refine` — `.pv-refine-btn` (Pipeline-Schritt PV-Generator)
- `sm` — `.btn-sm` (kompakt)

**Props/Konfiguration:** States Default/Hover/Active/Focus/Disabled (siehe Design-System §7.1);
Accent aus `--accent`/`--accent2`; Radius `--radius`.

**Verwendung:** Header (Analyse/Demo), PV-Generator, Content-Finder, Settings.

**Hinweise:** Neue Buttons als Variante der bestehenden Klassen, keine Ad-hoc-Inline-Styles.

**Status:** aktiv

---

## Card / Panel

**Zweck:** Inhaltssektionen als eigenständige, abgesetzte Flächen.

**Varianten**
- `standard` — `.needs-met-block` (Basis-Card der Views, individuell je Section)
- `module` — `.module-card` (Übersichts-Kacheln, Ampel-Left-Border `mc-green/amber/red`)
- `hero` — `.score-hero` (Score-Block oben)
- `exec-summary` — `.exec-summary-card` (KI-Executive-Summary, 3-Spalten-Grid)
- `cluster` — `.cluster-card` (aufklappbar, mit Donut)
- `pv` — `.pv-card`, `.pv-benefit-card`, `.pv-calc-card`, `.pv-solar-card`
- `radar` — `.radar-card`
- `settings` — `.settings-section`
- `stat` — `.stat-box` / `.stat-grid`

**Props/Konfiguration:** `--bg2` Hintergrund, `--border`, `--radius-lg`, `--shadow`;
`.needs-met-label` als Uppercase-Section-Label.

**Verwendung:** Alle Views.

**Hinweise:** **Antipattern vermeiden** — kein großer Outer-Wrapper mit flachen Panels;
jede Section ist eine eigene Card (siehe Design-System §7.12).

**Status:** aktiv

---

## Input / Formularelement

**Zweck:** Nutzereingaben (URL, Kontextfelder, Settings, PV-/CF-Felder).

**Varianten**
- `url` — `.url-input` (primäres Feld, `--bg` weiß)
- `context` — `.ctx-input` / `.ctx-field` / `.ctx-label`
- `settings` — `.settings-input` / `.settings-input-wrap` / `.settings-field`
- `pv` — `.pv-input-grid`, `.pv-hero-field`, `.pv-meta-field`
- `cf` — `.cf-loc`, `.cf-add-row`
- `toggle-switch` — `.toggle-switch` / `.toggle-slider` (Dark-Mode, Optionen)
- `mode-toggle` — `.mode-toggle` / `.mode-btn` (URL/HTML-Umschalter)

**Props/Konfiguration:** Radius `--radius`, Border `--border2`, Focus-Outline `--accent-border`.

**Verwendung:** Header, Settings, PV-Generator, Content-Finder.

**Hinweise:** `.input-card.input-dimmed` (`opacity:.4; pointer-events:none`) während laufender Analyse.

**Status:** aktiv

---

## Badge / Chip / Tag / Label / Statusanzeige

**Zweck:** Kompakte Status-, Kategorie- oder Wertanzeigen.

**Varianten**
- `score` — `.score-chip`, `.score-badge`, `.nav-score` (Sidebar-Score-Chip)
- `level` — `.sqeg-level`, `.score-hero-level` (Pill-Badge)
- `status-chip` — `.score-hero-chips` (✓ grün / ◑ amber / ✗ rot)
- `keyword` — `.pv-kw-pill` (+ `.pv-kw-pill-no-data`, `.pv-kw-pill-vol`)
- `source` — `.pv-source-badge`, `.pv-data-source-tag` (`.gsc`/`.sistrix`/`.dataforseo`/`.pvgis`)
- `placement` — `.pv-placement-badge`
- `cf-badge` — `.cf-badge` (`-exact` / `-synonym` / `-variant`)
- `cf-chip` — `.cf-chip` / `.cf-chip-remove` (entfernbare Such-Chips)
- `priority` — `.top-prio-badge`, `.priority-item`
- `id` — `.crit-id`, `.cluster-crit-id` (Kriterien-IDs)
- `agent` — `.agent-badge`, `.agent-custom-chip`

**Props/Konfiguration:** `--radius-sm`, 11px/600/uppercase (Type-Scale „xs"); System-Farben Green/Amber/Red/Blue.

**Verwendung:** Score-Hero, Sidebar, Tabellen, PV-Generator, Content-Finder.

**Hinweise:** Farbcodierung immer über System-Farben-Tokens, nicht hardcoded.

**Status:** aktiv

---

## Alert / Hinweis / Fehlermeldung

**Zweck:** Nutzer über Fehler, Warnungen oder Kontext informieren.

**Varianten**
- `error` — `.err`, `.err-box`, `.pv-error-box`
- `notice` — `.pv-refine-notice`, `.pv-foundation-note`
- `warning` — `.pv-duplicate-warn`, `.pv-dwd-banner*` (Amber-Banner bei Schätzung)
- `data-hint` — `.pv-data-hint` (grün „Datenquelle" / grau „Perspektivisch")

**Props/Konfiguration:** System-Farben `Red`/`Amber` inkl. `bg`/`border`-Varianten.

**Verwendung:** PV-Generator, Content-Finder, Analyse-Fehler.

**Status:** aktiv

---

## Tabs

**Zweck:** Umschalten zwischen inhaltlichen Ebenen innerhalb eines Views.

**Varianten**
- `pv` — `.pv-tabs` / `.pv-tab-btn` / `.pv-tab-panel` (Content / SEO+CRO / Markdown)
- `version` — `.pv-version-bar` / `.pv-version-btn` (Roh/Geschärft/Conversion)
- `filter` — `.filter-bar` / `.filter-btn`

**Props/Konfiguration:** Aktiver Tab via Accent; JS `pvSwitchTab()`, `pvSwitchVersion()`.

**Verwendung:** PV-Generator.

**Status:** aktiv

---

## Accordion / Collapsible / Panel

**Zweck:** Details auf Klick ein-/ausklappen; Übersicht kompakt halten.

**Varianten**
- `log` — `.log-wrap` / `.log-wrap.collapsed` (Analyse-Log, `toggleLog()`)
- `cluster` — `.cluster-card` / `.cluster-card-toggle` (aufklappbar mit Kriterien)
- `criteria-row` — `.crit-row` / `.crit-detail` (Click-to-Expand Kriterien-Tabelle)
- `context` — `.context-fields` / `.context-toggle` („Analyse verfeinern")
- `settings` — `.settings-toggle-btn`

**Props/Konfiguration:** Chevron-Rotation via `.log-chevron`/`.crit-chevron`.

**Verwendung:** SQEG-View, Technical, Header, Settings, Log.

**Status:** aktiv

---

## Navigation

**Zweck:** Wechsel zwischen den Dashboard-Views.

**Varianten**
- `sidebar` — `.sidebar` / `.sidebar-brand` / `.sidebar-nav` / `.sidebar-footer`
- `nav-item` — `.nav-item` / `.nav-item.active` (+ `.nav-score` Chip)
- `section-label` — `.nav-section-label` (Gruppen-Trenner, z. B. „Tools")

**Props/Konfiguration:** 220px feste Breite; aktiver Zustand `--accent-bg`/`--accent`;
JS `showView(id)`.

**Verwendung:** Globale Sidebar.

**Hinweise:** Neues Modul → alle 4 Reset-Stellen aktualisieren (siehe `Documents/MUST_READ.md`).

**Status:** aktiv

---

## Progress / Loading / Skeleton

**Zweck:** Fortschritt und Ladezustände visualisieren.

**Varianten**
- `progress-bar` — `.progress-bar` / `.progress-bar-bg` / `.progress-pct` (Zeit+% oben rechts)
- `loader` — `.loader-dots` / `.loader-dot`
- `skeleton` — `.skeleton` (+ `-score`, `-stat`, `-cluster`) — langsames Fade, kein Shimmer
- `pv-loading` — `.pv-loading` / `.pv-loading-spinner`
- `cf-progress` — `.cf-progress-outer` / `.cf-progress-inner` / `.cf-substep-bar`

**Props/Konfiguration:** `skel-pulse` 3s Fade; Progressbar-Design siehe Design-System §7.4/§7.7.

**Verwendung:** Analyse, PV-Generator, Content-Finder.

**Status:** aktiv

---

## Datenvisualisierung

**Zweck:** Scores und Verteilungen grafisch darstellen (ohne externe Chart-Library).

**Varianten**
- `donut` — `.cluster-card-donut` (SVG 48×48, Arc-Berechnung in JS)
- `radar` — `.radar-wrap` / `.radar-card` (Modul-Scores)
- `bar` — `.module-card-bar` / `.score-hero-bar` / `.agent-bar`

**Props/Konfiguration:** Farbe nach Score (grün ≥70 / amber ≥50 / rot <50); reines SVG/CSS.

**Verwendung:** Übersicht, SQEG-Cluster, Score-Hero.

**Hinweise:** Keine Chart-Library einführen — bestehende SVG-Berechnung wiederverwenden.

**Status:** aktiv

---

## Tabelle

**Zweck:** Strukturierte Daten (Kriterien, Keywords, Crawl-Treffer).

**Varianten**
- `criteria` — `.criteria-table` / `.criteria-table-wrap`
- `cf` — `.cf-table`
- `pv-archive` — `.pv-archive-table`
- `data-row` — `.pv-data-row` / `.pv-meta-row`

**Props/Konfiguration:** Table-Header `--bg3`; Mono-Font für URLs/IDs.

**Verwendung:** SQEG-Detailanalyse, Content-Finder, PV-Generator.

**Status:** aktiv

---

## Modal / Overlay

**Zweck:** Fokussierte Detailansicht über der Oberfläche.

**Varianten**
- `agent` — `.agent-modal` / `.agent-modal-overlay` / `-header` / `-body` / `-footer`

**Props/Konfiguration:** Overlay abdunkeln; `--shadow-lg`; Schließen via `.agent-modal-close`.

**Verwendung:** Agent-/Prompt-Detailansicht.

**Status:** aktiv

---

## Seitenvorschau (Screenshot-Karte)

**Zweck:** Vorschau der analysierten Seite (UX/CRO).

**Varianten**
- `page-preview` — `.page-preview-card` / `-bar` / `-dots` / `-img-wrap` / `-url-wrap` / `-footer`

**Verwendung:** UX/CRO-View.

**Status:** aktiv
