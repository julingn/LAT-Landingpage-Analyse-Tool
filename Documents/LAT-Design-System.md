# LAT Design System
**LAT · SQEG Analyzer · MVV Energie AG**
**Stand:** 22. Mai 2026 · Version 2.2
**Dieses Dokument wird bei jeder Design-Änderung aktualisiert.**

---

## 1 · Philosophie

Professionelles B2B-SaaS-Design: Cool, crisp, datenorientiert. Keine Ablenkung vom Analyseergebnis. Visuelles Gewicht folgt der Informationshierarchie (Score → Prioritäten → Detail).

---

## 2 · Farbpalette

### 2.1 Hintergrund & Oberfläche

| Token | Hex | Verwendung |
|-------|-----|------------|
| `--bg` | `#F8FAFC` | Seitenhintergrund (Slate-50) |
| `--bg2` | `#FFFFFF` | Card-Hintergrund |
| `--bg3` | `#F1F5F9` | Sekundäre Flächen, Table-Header |
| `--bg4` | `#E2E8F0` | Hover-Zustände |

### 2.2 Borders

| Token | Hex | Verwendung |
|-------|-----|------------|
| `--border` | `#E2E8F0` | Standard-Trennlinie (Slate-200) |
| `--border2` | `#CBD5E1` | Inputs, betonte Trennlinie (Slate-300) |

### 2.3 Text

| Token | Hex | Verwendung |
|-------|-----|------------|
| `--text` | `#0F172A` | Primärtext (Slate-900) |
| `--text2` | `#475569` | Sekundärtext (Slate-600) |
| `--text3` | `#94A3B8` | Placeholder, Labels, Meta (Slate-400) |

### 2.4 Accent (Brand)

| Token | Hex | Verwendung |
|-------|-----|------------|
| `--accent` | `#4F46E5` | CTAs, Active States (Indigo-600) |
| `--accent2` | `#4338CA` | Hover (Indigo-700) |
| `--accent-bg` | `#EEF2FF` | Accent-Flächen (Indigo-50) |
| `--accent-border` | `#C7D2FE` | Accent-Border (Indigo-200) |

### 2.5 System-Farben (WCAG 2.1 AA)

| Status | Hex | bg | border |
|--------|-----|-----|--------|
| Green | `#16A34A` | `#F0FDF4` | `#BBF7D0` |
| Amber | `#D97706` | `#FFFBEB` | `#FDE68A` |
| Red | `#DC2626` | `#FEF2F2` | `#FECACA` |
| Blue | `#2563EB` | `#EFF6FF` | `#BFDBFE` |

---

## 3 · Typografie

### 3.1 Schriftarten

| Rolle | Familie | Quelle |
|-------|---------|--------|
| UI / Body | **Inter** | Google Fonts |
| Monospace | **Geist Mono** | Vercel (CDN: `r2.vercel-storage.com`) |
| *(Entfernt)* | ~~Bricolage Grotesque~~ | Ersetzt durch Inter 700 |
| *(Entfernt)* | ~~DM Sans~~ | Ersetzt durch Inter |
| *(Entfernt)* | ~~DM Mono~~ | Ersetzt durch Geist Mono |

### 3.2 Type Scale

| Token | Size | Weight | Line-Height | Verwendung |
|-------|------|--------|-------------|------------|
| h1 | 24px | 700 | 1.25 | Score-Headline, Page Title |
| h2 | 18px | 700 | 1.3 | Card Title |
| h3 | 14px | 600 | 1.4 | Section Label, Table Header |
| body | 14px | 400 | 1.6 | Fließtext, Befund |
| sm | 13px | 400 | 1.5 | Meta, Labels |
| xs | 11px | 600 | 1.4 | Tags, Badges (Uppercase) |
| mono | 12px | 400 | 1.6 | URLs, IDs, Code |

---

## 4 · Spacing

Basis: **8px-Grid** (4px für Micro-Spacing)

| Token | Wert |
|-------|------|
| `--s-1` | 4px |
| `--s-2` | 8px |
| `--s-3` | 12px |
| `--s-4` | 16px |
| `--s-5` | 20px |
| `--s-6` | 24px |
| `--s-8` | 32px |
| `--s-10` | 40px |

Container: `max-width: 960px`, Sidebar: `220px`, Content-Padding: `32px` *(kein Fixed-Top-Bar-Offset mehr)*

---

## 5 · Border-Radius

| Token | Wert | Verwendung |
|-------|------|------------|
| `--radius-sm` | `6px` | Badges, Tags, kleine Chips |
| `--radius` | `8px` | Buttons, Inputs, Standard-Cards |
| `--radius-lg` | `12px` | Haupt-Cards, Panels |
| `--radius-xl` | `16px` | Score-Block, Hero-Cards |

---

## 6 · Shadows (Layering-System)

| Token | Wert | Verwendung |
|-------|------|------------|
| `--shadow-sm` | `0 1px 2px rgba(15,23,42,.05)` | Subtle Cards |
| `--shadow` | `0 1px 4px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.04)` | Standard Cards |
| `--shadow-md` | `0 4px 12px rgba(15,23,42,.10), 0 0 0 1px rgba(15,23,42,.04)` | Hover, Elevated |
| `--shadow-lg` | `0 8px 24px rgba(15,23,42,.12)` | Modals, Popovers |

---

## 7 · Komponenten

### 7.1 Buttons

| State | Beschreibung |
|-------|-------------|
| Default | `bg=--accent`, `shadow-sm`, `radius=8px` |
| Hover | `bg=--accent2`, `translateY(-1px)`, `shadow-md` |
| Active | `bg=#3730A3`, `translateY(0)`, `shadow-sm` |
| Focus | `outline: 3px solid --accent-border`, `outline-offset: 2px` |
| Disabled | `bg=--bg4`, `color=--text3`, `cursor: not-allowed` |

### 7.2 Score-Block (Hero)

Prominente Karte oben in den Ergebnissen:
- Score-Zahl: **64px, Inter 700**, coloriert nach Niveau
- SQEG-Level: **Pill-Badge** rechts vom Score
- Sekundäre Infos: YMYL-Status · Kriterien-Anzahl · Analysezeit
- Progress-Bar unter der Zahl für visuelle Stärke

### 7.3 Kriterien-Tabelle

- Zeilen **standardmäßig kompakt** (nur Status + Kriterienname + Kurzfazit)
- **Click-to-Expand**: Vollständiger Befund (Beleg, Regel, Bewertung, Verbesserungsvorschlag) klappt auf
- Expanded-State: hellblauer Hintergrund, Befund als strukturiertes Layout

### 7.4 Skeleton Screens

Während Analyse läuft: Platzhalter-Blöcke für Stat-Grid und Score-Badge:
```css
.skeleton {
  background: var(--bg4);
  animation: skel-pulse 3s ease-in-out infinite;
}
@keyframes skel-pulse {
  0%, 100% { opacity: .45; }
  50%       { opacity: .8; }
}
```
> **Regel:** Kein schnelles Shimmer (1.4s) bei langen Analysen (2+ Min.) — langsames Fade ist unauffälliger.

### 7.5 Prioritäts-Matrix

- Gewichtungs-Punkte (●) als visuelle Stärke neben jedem Item
- Farbcodierte Spalten-Header
- Effort-Badge rechtsbündig pro Item

---

### 7.6 Eingabe-Card (integriertes Interface)

URL-Input, Mode-Toggle und Start-Button sind **im SQEG-Analyzer-Card** enthalten — kein separater Fixed-Top-Bar.

Layout:
```
┌────────────────────────────────────────────────────────────────┐
│ [🔍] SQEG Analyzer              [Demo]  [URL] [HTML]          │
│      Google Search Quality…                                    │
├────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────┐ [▶ Analyse starten]│
│ │ URL der Landingpage eingeben            │                    │
│ └─────────────────────────────────────────┘                    │
│ ▸ Analyse verfeinern …                                        │
└────────────────────────────────────────────────────────────────┘
```

**Wichtige CSS-Regeln:**
- `.url-input` — `background: var(--bg)` (weiß, nicht `--bg3`) hebt das Feld als primäres interaktives Element hervor
- `.card-sub` — `font-family: inherit` (Inter, **nicht** Geist Mono — ist kein Code)
- `.input-card.input-dimmed` — `opacity: .4; pointer-events: none; transition: opacity .3s` — aktiv während Analyse läuft, entfernt in `renderResults()`
- `.context-fields.visible` — `border-top: 1px solid var(--border); padding-top: 14px` trennt ausgeklappte Felder vom Kern-Input

**Demo-Button:** Sitzt in `.card-actions` im Card-Header (neben Mode-Toggle), **nicht** in der `.input-row`. So bleibt die primäre Aktionslinie sauber: `[Input] [Analyse starten]`.

**Context-Toggle:** Label „Analyse verfeinern" — handlungsorientiert, kein parenthetischer Inhalt.

Mode-Toggle sitzt in `.card-actions` (rechts im Card-Header).
HTML-Textarea in `#html-textarea-wrap` (`display:none` initial, erscheint bei „HTML"-Klick).

---

### 7.7 Kollabierbare Log-Box

Analyse-Log klappt nach Abschluss **automatisch zu** — nur Zusammenfassung im Header sichtbar.

**Verhalten:**
- Analyse startet → Log expandiert (`.log-wrap` ohne `.collapsed`)
- Analyse fertig → klappt zu (`.log-wrap.collapsed` wird gesetzt)
- Klick auf Header → `toggleLog()` — manuell öffnen/schließen

**Zusammengeklappt zeigt der Header:**
`Analyse abgeschlossen` · `100%` · `1:45` · Chevron zeigt nach rechts (►)

```html
<div class="log-wrap" id="log-wrap">
  <div class="log-header" onclick="toggleLog()">
    <span id="progress-label">Analyse-Log</span>
    <span>[timer] · [%] · [chevron ▼/►]</span>
  </div>
  <div class="log-box" id="log-box"></div>
</div>
```

```css
.log-wrap.collapsed .log-box             { display: none; }
.log-wrap.collapsed .log-header .log-chevron { transform: rotate(-90deg); }
```

---

### 7.8 Cluster-Übersicht mit Donut-Charts

8 Karten im 4×2-Grid (zwischen Stat-Grid und SQEG-Scale).

Jede Karte zeigt:
- **Mini-Donut** (SVG 48×48, `r=18`, `stroke-width=5`)
- Startet bei −90° (12-Uhr-Position), läuft im Uhrzeigersinn
- Farbe: grün ≥70% / amber ≥50% / rot <50%
- **Score-Prozent** als Text im Donut-Zentrum (`dominant-baseline="central"`)
- Zählung darunter: `4✓ 2◑ 1✗`

```js
// Donut-Arc-Berechnung (kein externe Library):
const R = 18, circ = 2 * Math.PI * R;   // ≈ 113.1
const dash = (score / 100 * circ).toFixed(1);
// stroke-dasharray="${dash} ${circ.toFixed(1)}"
// transform="rotate(-90 24 24)"
```

Responsiv: 4 Spalten → 2 Spalten bei `≤ 768px`.

---

### 7.9 Executive Summary Card

Erscheint nach Analyse zwischen Score-Hero und Stat-Grid. 3-Spalten-Grid:

```
┌──────────────────┬──────────────────────┬──────────────────────┐
│ Gesamtbewertung  │ Hauptprobleme        │ Nächste Schritte     │
│                  │ ✖ Problem-Titel      │ 1. Maßnahme          │
│ 61/100 –         │ → Kurze Erklärung    │ 2. Maßnahme          │
│ Mittlere Qualität│ ✖ …                  │ 3. Maßnahme          │
│ [Satz]           │ ✖ …                  │                      │
└──────────────────┴──────────────────────┴──────────────────────┘
```

**Datenquellen:**
- Score-Label: deterministisch aus `getScoreInterpretation()` (nicht KI-generiert)
- Problem-Satz, Probleme, Schritte: KI-generiert via `callApi()` (~700 Token)
- Demo-Modus: statische Daten, kein API-Aufruf

**Zwei-Zeilen-Problemstruktur:**
```css
.exec-summary-problem-label  { font-size:12px; font-weight:700; color:var(--text); }
.exec-summary-problem-arrow  { font-size:12px; color:var(--text2); padding-left:14px; }
```

**Loading-State:** `.exec-summary-loading` mit `.loader-dots` während KI-Call läuft.

**System-Prompt-Regeln:** ✖ Titel max. 10–12 Wörter · → Erklärung max. 10–12 Wörter · kein „–" in Sätzen · konsistenter Stil · kein Score-KPI im Fließtext · genau 3 Probleme + 3 Maßnahmen (max. 8–10 Wörter).

---

### 7.10 Score-Interpretation (Score-Hero Subtitle)

Direkt unter dem Level-Badge im Score-Hero, vor dem Progress-Bar.

```css
.score-hero-interp { font-size:12px; color:var(--text2); line-height:1.4; margin:4px 0 8px; }
```

**5-Stufen-Logik (deterministisch, `getScoreInterpretation(score)`):**

| Score | Label | Satz |
|-------|-------|------|
| 90–100 | Sehr gute Qualität | Sehr hohe Qualität mit nur geringem Optimierungsbedarf. |
| 75–89 | Gute Qualität | Gute Qualität mit kleineren Optimierungsmöglichkeiten. |
| 60–74 | Mittlere Qualität | Solide Basis mit relevanten Optimierungspotenzialen. |
| 40–59 | Niedrige Qualität | Deutliche Defizite mit prioritärem Optimierungsbedarf. |
| 0–39 | Sehr niedrige Qualität | Kritischer Zustand mit hohem Handlungsdruck. |

**Konsistenz-Regel:** `getScoreInterpretation()` ist die einzige Quelle für Labels — in Score-Hero **und** Executive Summary. KI überschreibt diesen Wert nie.

---

## 8 · Icons

**Bibliothek:** Lucide Icons (Inline SVG, `stroke-width: 1.75`, konsistente `16×16px` Größe)
CDN: `https://unpkg.com/lucide@latest`

---

## 9 · Changelog

| Version | Datum | Änderungen |
|---------|-------|------------|
| 2.2 | 22.05.2026 | Executive Summary Card (7.9), Score-Interpretation 5-Stufen (7.10), Eingabe-Card aktualisiert (7.6): Demo-Button in Header, `url-input bg→--bg`, `card-sub` kein Mono, `.input-dimmed`, Context-Separator, Label-Updates |
| 2.1 | 22.05.2026 | Eingabe-Card (7.6), Log-Collapse (7.7), Cluster-Donuts (7.8), Skeleton Slow-Pulse (7.4), Container-Padding 96→32px, Rebranding MVV/LAT |
| 2.0 | 21.05.2026 | Initialer Entwurf: Slate-Palette, Inter/Geist Mono, Score-Hero, Expand-Rows, Skeleton |
