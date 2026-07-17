# LAT Design System
**LAT · SQEG Analyzer · MVV Energie AG**
**Stand:** 18. Juni 2026 · Version 2.4
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

### 2.4 Accent (Brand — MVV Technical Blue)

| Token | Hex | Verwendung |
|-------|-----|------------|
| `--accent` | `#0049EC` | CTAs, Active States (MVV Technical Blue, Pantone 2387) |
| `--accent2` | `#263FCC` | Hover (dunkelste Technical-Blue-Stufe) |
| `--accent-bg` | `#E8EFFD` | Accent-Flächen (hellste Technical-Blue-Stufe) |
| `--accent-border` | `#BACEFA` | Accent-Border |

**Markenbezug:** Technical Blue ist laut MVV Corporate Design Manual die *primäre Interaktionsfarbe* (alle primären Buttons, Links, wichtigen Interaktionen). Dark Mode nutzt hellere Stufen (`--accent:#477CF1`).

### 2.5 System-Farben (WCAG 2.1 AA · an MVV-Digitalpalette angeglichen)

| Status | Hex | bg | border | Herkunft |
|--------|-----|-----|--------|----------|
| Green | `#12A150` | `#F4FDF7` | `#BCF1CE` | MVV Grass Green (textsicher abgedunkelt) |
| Amber | `#D97706` | `#FFFBEB` | `#FDE68A` | LAT-Eigenwert (MVV hat kein Amber) |
| Red | `#E90C3C` | `#FDECEF` | `#F8C2CD` | MVV Red (Spezialfarbe „Fehler") |
| Blue | `#0087C9` | `#ECF9FD` | `#9FE2F7` | MVV Sky Blue (textsicher abgedunkelt) |

### 2.6 MVV Sekundärfarben (Tags/Labels/Icons)

| Token | Hex | Verwendung |
|-------|-----|------------|
| `--purple` / `--purple-bg` / `--purple-border` | `#8E3FD4` / `#F7F0FD` / `#E0C2F7` | Energizing Purple — **textsicher** (wie System-Farben); z.B. Sistrix-Datenquellen-Tag |
| `--sky` | `#40C5EF` | Sky Blue — Fill/Icon (bright) |
| `--grass` | `#1ED05C` | Grass Green — Fill/Badge (bright, dunkler Text); z.B. Pipeline-„done"-Nummer |
| `--cool-grey` | `#B6C5CD` | Cool Grey — neutrale Tags |
| `--hero-gradient` | `linear-gradient(135deg,#8FEBA4,#51F8A4)` | Hero Gradient für besonders wichtige CTAs |

**Hinweis:** `--purple` ist textsicher abgedunkelt (Tag-Text), `--sky`/`--grass`/`--cool-grey` bleiben bright für Fill/Badge/Icon.

**PV-Datenquellen-Tags** (distinkte Hues): DWD = Sky Blue (`--blue`), GSC = Technical Blue (`--accent`), DataForSEO = Grass Green (`--green`), Sistrix = Purple (`--purple`), PVGIS = Amber (`--amber`).


---

## 3 · Typografie

### 3.1 Schriftarten

| Rolle | Familie | Quelle |
|-------|---------|--------|
| UI / Body | **Manrope** | Google Fonts (OFL) |
| Monospace | **Geist Mono** | Vercel (CDN: `r2.vercel-storage.com`) |
| *(Entfernt)* | ~~Inter~~ | Ersetzt durch Manrope (markennäher an MVV Circular XX) |
| *(Entfernt)* | ~~Bricolage Grotesque~~ | Ersetzt |
| *(Entfernt)* | ~~DM Sans / DM Mono~~ | Ersetzt |

**Markenbezug:** MVV-Hausschrift ist **Circular XX** (geometrische Grotesk, Futura-Tradition, lizenzpflichtig/Lineto — keine Web-Lizenz im Tool). **Manrope** ist die nächste freie, geometrische Entsprechung mit hoher Screen-Lesbarkeit und Tabular-Figures (`font-feature-settings:'tnum'`) — geeignet für Zahlenspalten/Scores im Analyse-Tool. Fallback-Stack: `'Manrope', system-ui, sans-serif`. Ist eine MVV-Web-Lizenz für Circular XX vorhanden, kann sie als erste Familie im Stack ergänzt werden.

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

### 7.11 Multi-View Dashboard (ab v3.0)

Das UI ist ein **Single-Page Multi-View Dashboard** mit Sidebar-Navigation. Alle Views liegen im DOM, nur der aktive ist sichtbar.

#### Layout-Struktur

```
<body>
  <header>             ← sticky, Eingabemaske
  <div class="app-shell">
    <nav class="sidebar">      ← 220px fix, scrollbar
    <main class="main-content">
      <div class="content-wrap">   ← max-width:960px; margin:0 auto; padding:24px 32px 48px
        <div class="view-panel" id="view-overview">
        <div class="view-panel" id="view-sqeg">
        <div class="view-panel" id="view-technical">
        <div class="view-panel" id="view-performance">
        ...
      </div>
    </main>
  </div>
```

**KRITISCH:** Alle `view-panel`-Divs müssen **innerhalb** von `<div class="content-wrap">` liegen. Fehlt ein View außerhalb, rendert er full-width ohne max-width/centering. Ursache war historisch ein überschüssiges `</div>` das `content-wrap` zu früh schloss.

```css
.content-wrap { max-width:960px; margin:0 auto; padding:24px 32px 48px; }
.view-panel   { display:none; }
.view-panel.active { display:block; }
```

#### Sidebar-Navigation

```css
.sidebar { width:220px; ... }
.nav-item { display:flex; align-items:center; justify-content:space-between; ... }
.nav-item.active { background:var(--accent-bg); color:var(--accent); }
.nav-score { font-size:11px; font-family:'Geist Mono'; color:var(--text3); margin-left:auto; }
```

Jeder Nav-Eintrag hat rechtsbündig einen Score-Chip (`nav-score`), der nach der Analyse gesetzt wird:
```html
<button class="nav-item" data-view="technical" onclick="showView('technical')">
  [SVG-Icon] Technical SEO
  <span class="nav-score" id="nav-score-technical" style="display:none"></span>
</button>
```

#### Modul-Kacheln (`#view-overview`)

Grid aus Karten, eine pro Modul. Jede Kachel zeigt: Name · Sub-Label · Score · Balken · Status-Label.

```css
.module-card        { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-lg);
                      padding:20px; cursor:pointer; transition:all .15s; }
.module-card:hover  { box-shadow:var(--shadow-md); border-color:var(--border2); }
.module-card.mc-green  { border-left:3px solid var(--green); }
.module-card.mc-amber  { border-left:3px solid var(--amber); }
.module-card.mc-red    { border-left:3px solid var(--red); }
.module-card-score      { font-size:24px; font-weight:700; }
.module-card-score.green { color:var(--green); }
.module-card-score.neutral { color:var(--text3); }
.module-card-bar-bg   { height:4px; background:var(--bg4); border-radius:2px; margin:8px 0 4px; }
.module-card-bar      { height:4px; border-radius:2px; transition:width .4s; }
.module-card-bar.green { background:var(--green); }
.module-card-label    { font-size:11px; color:var(--text3); }
```

---

### 7.12 Card-System der Views (`.needs-met-block`)

Jede thematische Sektion in einem View ist eine **eigenständige Card** — kein übergreifender Outer-Wrapper.

```css
.needs-met-block {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 24px;
  margin-bottom: 20px;
  display: none;           /* initial hidden — per JS auf block gesetzt */
  box-shadow: var(--shadow);
}
.needs-met-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text3);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 14px;
}
```

**Antipattern — nicht so:**
```html
<!-- FALSCH: ein großer Outer-Wrapper mit flachen Panels drin -->
<div id="perf-results" class="needs-met-block" style="display:none">
  <div id="gsc-panel">...</div>
  <div id="sistrix-panel">...</div>
</div>
```

**Korrekt:**
```html
<!-- RICHTIG: jede Section eigene Card -->
<div id="perf-results" style="display:none; margin-top:28px">
  <div class="needs-met-block" id="gsc-panel" style="display:none">...</div>
  <div class="needs-met-block" id="sistrix-panel" style="display:none">...</div>
</div>
```

`margin-top:28px` auf dem results-Container (`#perf-results`, `#geo-results` etc.) gibt Abstand zum Header.

---

### 7.13 Technical SEO Check-Liste (deterministisches Modul)

Layout für Prüfpunkte ohne KI-Call. Jeder Check zeigt: Status-Icon · ID-Badge · Name · Befund · Detail · Fix-Box.

```html
<!-- Check-Row -->
<div style="display:flex; align-items:flex-start; gap:14px; padding:14px 0; border-bottom:1px solid var(--border)">
  <!-- Status-Circle -->
  <div style="width:28px; height:28px; border-radius:50%; background:var(--green-bg);
              border:1px solid var(--green-border); color:var(--green); font-size:12px; font-weight:700;
              display:flex; align-items:center; justify-content:center; flex-shrink:0">✓</div>
  <div style="flex:1; min-width:0">
    <!-- ID + Name -->
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:3px">
      <span style="font-size:10px; font-weight:600; color:var(--text3); font-family:'Geist Mono'">T3</span>
      <span style="font-size:13px; font-weight:600; color:var(--text)">Title-Tag</span>
    </div>
    <!-- Befund -->
    <div style="font-size:12px; color:var(--text2); line-height:1.5">...</div>
    <!-- Detail (optional) -->
    <div style="font-size:11px; color:var(--text3); margin-top:4px; line-height:1.4">...</div>
    <!-- Fix-Box (nur wenn nötig) -->
    <div style="font-size:11px; color:var(--accent); margin-top:5px; padding:4px 8px;
                background:var(--accent-bg); border-radius:var(--radius-sm);
                border:1px solid var(--accent-border); line-height:1.4">
      <strong>Fix:</strong> ...
    </div>
  </div>
</div>
```

**Score-Header der Checklist:**
- Große Score-Zahl (36px, farbkodiert) + Status-Label + Quelle
- Chip-Row: `✓ N` (green), `◑ N` (amber), `✗ N` (red) als Pills mit `border-radius:999px`

---

## 8 · Icons

**Bibliothek:** Lucide Icons (Inline SVG, `stroke-width: 1.75`, konsistente `16×16px` Größe)
CDN: `https://unpkg.com/lucide@latest`

---

### 7.14 Standard-Layout einer Modulseite (ab v3.6)

**ALLE Analyse-Module** folgen exakt dieser vierstufigen Struktur — keine Abweichungen:

```
┌──────────────────────────────────────────────────────────┐
│ 1. SCORE HERO (.score-hero)                              │
│    Große Score-Zahl · Level-Badge · Interpretation       │
│    Progress-Bar · Chips (✓ N  ◑ N  ✗ N  + Prüfpunkte)   │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ 2. EXECUTIVE SUMMARY (.exec-summary-card)                │
│    2-spaltig: [Bewertung] [Top-Probleme]                 │
│    Volle Breite: [Empfohlene nächste Schritte]           │
│    → max. 3 Schritte als horizontale Cards (3-col-Grid)  │
└──────────────────────────────────────────────────────────┘
  ——— CLUSTER-ÜBERSICHT ———
┌──────────────────────────────────────────────────────────┐
│ 3. CLUSTER-CARDS (.cluster-overview)                     │
│    Aufklappbare Karten mit Donut-Chart                   │
│    Cluster mit roten Checks öffnen sich automatisch      │
│    Jede Row: Status-Dot · ID-Badge · Name · Befund · Fix │
└──────────────────────────────────────────────────────────┘
```

**Score Hero — IDs der DOM-Elemente (Namensschema: `{modul}-score-*`):**

| Modul | Prefix | Beispiel-IDs |
|---|---|---|
| SQEG | `score-hero-*` | `score-hero-num`, `score-hero-level`, `score-hero-bar` |
| Technical SEO | `tech-score-*` | `tech-score-num`, `tech-score-level`, `tech-score-bar` |
| UX/CRO | `ux-score-*` | `ux-score-num` |
| (neue Module) | `{kürzel}-score-*` | analog |

**Executive Summary — deterministisch vs. KI:**

| Modul | Quelle |
|---|---|
| SQEG | KI-generiert (Anthropic/OpenAI LLM-Call) |
| Technical SEO | Deterministisch — aus Check-Ergebnissen berechnet |
| UX/CRO | Deterministisch + optionaler LLM-Kommentar |
| (neue Module) | Deterministisch bevorzugen; KI nur wenn nötig |

**Section-Divider zwischen Executive Summary und Clusters:**
```html
<div class="section-divider">
  <div class="section-divider-line"></div>
  <span class="section-divider-label">Cluster-Übersicht</span>
  <div class="section-divider-line"></div>
</div>
```

---

### 7.15 Prüfpunkt-ID-Konvention

Jeder Prüfpunkt hat eine **eindeutige, modulgebundene ID** die im `.cluster-crit-id`-Badge angezeigt wird.

**Format:** `{MODUL-KÜRZEL}{NUMMER}` — kein Leerzeichen, kein Unterstrich.

| Modul | Kürzel | Format | Beispiele |
|---|---|---|---|
| SQEG | `SQ` | `SQ{Cluster}.{Nr}` | `SQ1.1`, `SQ3.2`, `SQ8.4` |
| Technical SEO | `T` | `T{Nr}` | `T1`, `T13`, `T25` |
| UX / CRO | `U` | `U{Nr}` | `U1`, `U5` |
| Performance | `P` | `P{Nr}` | `P1`, `P4` |
| GEO / AEO | `G` | `G{Nr}` | `G1`, `G3` |
| Keyword Fit | `K` | `K{Nr}` | `K1`, `K6` |

**SQEG-Sonderregel:** Die IDs kommen vom LLM zurück (`"1.1"`, `"3.2"`) — im UI wird `SQ` vorangestellt:
```js
`SQ${escHtml(r.id)}`  // "1.1" → "SQ1.1"
```

**Neue Module:** Kürzel vorab festlegen und hier eintragen, bevor Prüfpunkte implementiert werden.

---

## 9 · Changelog

| Version | Datum | Änderungen |
|---------|-------|------------|
| 2.4 | 18.06.2026 | Standard-Layout Modulseiten (7.14): Score Hero → Executive Summary → Cluster-Übersicht. Prüfpunkt-ID-Konvention (7.15): SQ1.1, T13, U1 etc. Technical SEO 7.13 überholt durch neues Layout. |
| 2.3 | 10.06.2026 | Multi-View Dashboard Layout (7.11): content-wrap KRITISCH, Sidebar-Nav, Modul-Kacheln. Card-System `.needs-met-block` als eigenständige Cards — kein Outer-Wrapper (7.12). Technical SEO Check-Liste Layout (7.13). |
| 2.2 | 22.05.2026 | Executive Summary Card (7.9), Score-Interpretation 5-Stufen (7.10), Eingabe-Card aktualisiert (7.6): Demo-Button in Header, `url-input bg→--bg`, `card-sub` kein Mono, `.input-dimmed`, Context-Separator, Label-Updates |
| 2.1 | 22.05.2026 | Eingabe-Card (7.6), Log-Collapse (7.7), Cluster-Donuts (7.8), Skeleton Slow-Pulse (7.4), Container-Padding 96→32px, Rebranding MVV/LAT |
| 2.0 | 21.05.2026 | Initialer Entwurf: Slate-Palette, Inter/Geist Mono, Score-Hero, Expand-Rows, Skeleton |
