# LAT v3 — Roadmap: Modulare Analyse-Plattform

**Erstellt:** 05. Juni 2026  
**Basis:** Konzept-Session vom 05.06.2026 (PAGE360-Diskussion)  
**Ziel:** LAT entwickelt sich vom Single-File-SQEG-Analyzer zur vollständigen 6-Modul-Analyseplattform für einzelne URLs/Landingpages.

---

## Überblick: Die 6 Analyse-Module

| Modul | Frage | Datenquellen | Status |
|---|---|---|---|
| **M1 — SQEG** | Ist der Content qualitativ & vertrauenswürdig? | LLM (Anthropic/OpenAI) | ✅ Vorhanden (LAT v2) |
| **M2 — Technical SEO** | Ist die Seite technisch korrekt & indexierbar? | PageSpeed (Mobile + Desktop), HTML-Parsing, Sitemap | ✅ Vollständig implementiert (18.06.2026) |
| **M3 — Performance** | Wie sichtbar ist die Seite aktuell? | GSC, Sistrix, DataforSEO | ✅ Vorhanden (LAT v2) |
| **M4 — GEO / AEO** | Wie präsent ist die Seite in KI-Antworten? | Sistrix `ai.*`-Endpunkte | ✅ Vorhanden (Phase 1.3) |
| **M5 — UX / CRO** | Wie erlebt ein Nutzer diese Seite? | LLM + Vision, Screenshot | ✅ Implementiert (09.06.2026) |
| **M6 — Keyword Fit** | Targetet die Seite die richtigen Keywords? | Sistrix, DataforSEO, GSC | ✅ Implementiert (09.06.2026) |

**KI-Synthese (übergreifend):** Ein LLM-Call mit allen Modul-Outputs → priorisierte Handlungsempfehlung über alle Dimensionen hinweg.

---

## Phase 1 — Sistrix-Erweiterungen im bestehenden LAT

> **Ziel:** Bestehende Codebase erweitern. Kein Umbau, kein Risiko. Validiert den GEO-Ansatz.  
> **Aufwand:** Mittel (3–4 Coding-Sessions)  
> **Von mir benötigt:** Nichts — reine Implementierung

### Schritt 1.1 — `domain.opportunities` im Sistrix-Panel ✅
- **Was:** Zweite Tabelle im `#sistrix-panel` mit Keywords auf Position 4–20 inkl. `gain`-Score (0–100)
- **Datei:** `app/sistrix.php` (in `url_data` integriert) + `app/index.php` (UI + JS)
- **Sistrix-Endpunkt:** `domain.opportunities?domain=X&limit=20&country=de`
- **Output:** Tabelle "Quick Wins" mit Keyword / Position / Gain-Score / Wettbewerb
- **Kosten:** 1 Credit pro Eintrag

### Schritt 1.2 — `domain.competitors.seo` im Sistrix-Panel ✅
- **Was:** Zeigt die 5 stärksten organischen Wettbewerber der analysierten Domain
- **Datei:** `app/sistrix.php` (in `url_data` integriert) + `app/index.php`
- **Sistrix-Endpunkt:** `domain.competitors.seo?domain=X&limit=5&country=de`
- **Output:** Pill-Liste im Sistrix-Panel unter Visibility-Wert
- **Kosten:** 1 Credit pro Eintrag

### Schritt 1.3 — GEO-Panel (`#geo-panel`) ✅
- **Was:** Neues Panel nach dem Sistrix-Panel mit KI-Sichtbarkeitsdaten
- **Datei:** `app/sistrix.php` (neuer `action=geo_data` Handler) + `app/index.php` (neues Panel, JS)
- **Sistrix-Endpunkte:**
  - `ai.entity.prompts?entity=DOMAIN&country=de&limit=20` → Welche ChatGPT/Perplexity-Prompts führen zur Marke?
  - `ai.entity.sources?entity=DOMAIN&country=de&limit=10` → Welche Domain-URLs werden als KI-Quellen zitiert?
- **Entity-Ableitung:** Automatisch aus Domain — `preg_replace('/^www\./i', '', parse_url($url)['host'])` ✅
- **Output:** Panel mit Prompt-Liste + Quellen-Liste

### Schritt 1.4 — `keyword.seo.serpfeatures` als Badge in GSC-Tabelle ✅
- **Was:** Pro Top-5 GSC-Keyword prüfen ob AI Overview / Featured Snippet / Knowledge Graph vorhanden
- **Datei:** `app/sistrix.php` (neuer `action=serp_features` Handler) + `app/index.php`
- **Sistrix-Endpunkt:** `keyword.seo.serpfeatures?kw=X&country=de` (5× parallel via cURL Multi)
- **Output:** Icon-Badges in der GSC-Keyword-Tabelle (`AI`, `FS`, `KG`) — async nach Erstrender
- **Kosten:** 1 Credit pro Keyword

---

## Phase 2 — Architektur-Umbau: Modulare Struktur

> **Ziel:** Saubere Trennung der Module. Vorbereitung für Phase 3+.  
> **Aufwand:** Groß (1–2 intensive Sessions, kein neues Feature)  
> **Von mir benötigt:** Git-Branch anlegen + Railway Preview-Deploy aktivieren (einmalig)

### Schritt 2.1 — Feature-Branch anlegen ✅
```bash
git checkout -b feature/v3-modular-structure
```
- Railway: Preview-Deploy eingerichtet ✅ (08.06.2026)

### Schritt 2.2 — Neue Dateistruktur anlegen ✅
```
app/
├── modules/
│   ├── sqeg.php        ← aus index.php extrahiert
│   ├── technical.php   ← aus index.php extrahiert
│   ├── performance.php ← aus index.php extrahiert
│   ├── geo.php         ← aus Phase 1 übernommen
│   └── keywords.php    ← neu (Phase 3)
├── proxies/
│   ├── api.php         ← verschoben
│   ├── dataforseo.php  ← verschoben
│   ├── sistrix.php     ← verschoben
│   ├── gsc.php         ← verschoben
│   └── pagespeed.php   ← verschoben
├── synthesis.php       ← NEU: LLM-Synthese über alle Module
├── storage.php         ← NEU: Analyse-Ergebnisse speichern/laden
├── index.php           ← UI-Shell (stark reduziert, ~500 Zeilen)
└── config.php          ← unverändert
```

### Schritt 2.3 — `router.php` anpassen ✅
- Alle Proxy-Pfade bleiben kompatibel via Shim-Dateien in `app/`
- Direkte Browser-Aufrufe auf `/app/proxies/` werden blockiert (HTTP 403)

### Schritt 2.4 — Tests auf Preview-Deploy ✅
- Getestet, grünes Licht → Merge auf main → Deploy `24243fc`

---

## Phase 3 — Neue Module implementieren

> **Ziel:** M6 (Keyword Fit) + übergreifende KI-Synthese  
> **Aufwand:** Groß  
> **Von mir benötigt:** Nichts — reine Implementierung nach Architektur aus Phase 2

### Schritt 3A — Übergreifende KI-Synthese ✅
- **Was:** Executive Summary erweitert mit Cross-Modul-Kontext (GSC, Sistrix, GEO/AEO)
- **Umsetzung:** Kein zweiter LLM-Call — `generateExecSummary()` bekommt alle externen Datenpunkte als Kontext-Block; System-Prompt instruiert ganzheitliche Priorisierung
- **Eingabe:** SQEG-Findings + GSC Top-Keywords + Sistrix Sichtbarkeit + Quick-Win-Keywords + Wettbewerber + GEO-Prompts
- **Output:** Executive Summary priorisiert jetzt cross-modul (z.B. Quick-Wins vor SQEG-Detailpunkten wenn relevanter)

## Phase 3B+4 — nach UI/UX-Überarbeitung

> UI/UX-Überarbeitung abgeschlossen am 08.06.2026. ✅

### Schritt 3B — M6: Keyword-Fit-Modul ✅
- **Was:** Bewertet ob die analysierte Seite die richtigen Keywords targetet
- **Datenquellen:**
  - GSC-Daten (bereits vorhanden): aktuelle Rankings
  - Sistrix `keyword.seo.searchintent`: Intent-Analyse pro Keyword
- **Output:** Score 0–100 + Intent-Tabelle (Commercial/Transactional/Informational/Navigational) + Mismatch-Warnung
- **Implementiert:** `3182ef5` (09.06.2026) — `app/proxies/keywords.php`, `#view-keywords`, Sidebar-Nav, Demo-Daten

### Schritt 3.2 — Übergreifende KI-Synthese
- **Was:** Ein LLM-Call NACH allen Modulen mit strukturiertem Gesamt-Input
- **Datei:** `app/synthesis.php`
- **Input-Schema:**
  ```json
  {
    "url": "...",
    "scores": { "sqeg": 78, "technical": 45, "performance": 62, "geo": 12, "keywords": 55 },
    "top_findings": { "sqeg": [...], "technical": [...], ... }
  }
  ```
- **Output:** 3 Prioritäten + Kausalanalyse ("Technical blockiert trotz gutem Content die Rankings")
- **Status:** ❌ Noch nicht implementiert (3A bereits fertig als Interim-Lösung)

### Schritt 3.3 — Score-Hero erweitern ✅
- ~~Modul-Scores als Radar-Chart oder Balken-Übersicht~~ → **Radar-Chart implementiert** (`e33efee`)
- Übergreifender Gesamtscore aus gewichteten Modul-Scores → noch offen

---

## Phase 4 — M5: UX/CRO-Modul ✅ (v1, wird ersetzt durch v2)

> **Implementiert:** 09.06.2026  
> **Entscheidung:** Headless Chromium direkt im Railway-Container (kein externer Dienst)
> **Status:** Vollständig implementiert — aber Konzept überarbeitet (siehe Phase 5)

### Schritt 4.1 — Screenshot-Integration ✅
- Chromium in Alpine Dockerfile installiert (`apk add chromium nss freetype harfbuzz ttf-freefont`)
- `app/proxies/ux.php`: Screenshot via `chromium --headless=new --screenshot` → Base64 PNG
- Fallback auf verschiedene Chromium-Pfade (`/usr/bin/chromium`, `/usr/bin/chromium-browser`, etc.)

### Schritt 4.2 — UX-Analyse-Prompt ✅ (v1 — wird durch deterministischen Ansatz ersetzt)
- Vision-LLM: Anthropic Claude (Standard) oder OpenAI GPT-4o — nutzt konfigurierten `AI_PROVIDER`
- Bewertet 5 Kriterien: Value Proposition · CTA · Trust-Signale · Visuelle Hierarchie · Above-the-Fold
- Output: Score 0–100 + Level + 5 Findings (rating: green/amber/red + Befund + Empfehlung) + Sub-Scores
- **Problem:** Reiner LLM-Score ist nicht reproduzierbar — gleiche Seite ergibt unterschiedliche Scores

### Schritt 4.3 — UX-View in Dashboard ✅
- `#view-ux`: Score-Hero (Chips ✓/◑/✗) + Screenshot-Panel + Findings-Karten + Gesamtbewertung
- Sidebar-Nav: „UX / CRO" mit Score-Badge
- Modul-Kachel `#mc-ux` in Übersicht
- Analyse läuft **async** parallel zu SQEG-Calls — Loading-State im View
- Demo-Daten für alle 5 Kriterien (kein echter Screenshot im Demo-Modus)

---

## Phase 5 — M5 UX/CRO v2: Deterministisch + Device-Split

> **Konzept:** 10.06.2026  
> **Ziel:** Reproduzierbare Scores durch HTML-Parsing. LLM nur für Kommentartext, nicht für Score.  
> **Grundlage:** UX-Leitfaden für Landingpages (5 Kernbereiche: Above-the-Fold, Benutzerführung, CTA, Trust, Performance)

### Architektur-Entscheidungen

| Entscheidung | Wert |
|---|---|
| Score-Quelle | **Deterministisch** — HTML-Parsing + PSI-Daten |
| LLM-Rolle | **Nur Kommentartext** — beeinflusst Score nicht |
| Screenshot-Rolle | Visuelle Vorschau + Input für Vision-LLM-Kommentar |
| Device-Split | **Desktop + Mobile** — separate Scores + separate LLM-Kommentare |
| Vision-Calls | 2× (1× Desktop-Screenshot, 1× Mobile-Screenshot) |

### 5 Kriterien (U1–U5)

| ID | Kriterium | Deterministische Signale | LLM-Kommentar |
|---|---|---|---|
| U1 | Above-the-Fold & Nutzenversprechen | H1 vorhanden · Hero-Image vorhanden · Wörter im `<body>`-Anfang (Heuristik) | Ja — Vision |
| U2 | Ablenkungsfreiheit & Benutzerführung | Anzahl `<nav>`-Links · externe Header-Links · Hauptnavigation vorhanden? | Ja — Vision |
| U3 | Call-to-Action | `<button>`/`<a>`-Count mit CTA-Keywords (jetzt, sichern, kaufen, wechseln, starten…) · Touch-Target-Heuristik | Ja — Vision |
| U4 | Trust & Social Proof | Schema.org `AggregateRating` · Trust-Keywords (Bewertung, Zertifikat, TÜV, Siegel, Testimonial…) · Partner-Logos (`<img alt>`) | Ja — Vision |
| U5 | Mobile & Performance | PSI Mobile Score · PSI Desktop Score · LCP · CLS · TBT (aus `psiData` + neuem Desktop-PSI-Call) | Nein — rein deterministisch |

### Score-Berechnung

```
Score pro Device = Durchschnitt(U1–U5) wobei: grün=100, amber=50, rot=0
Gesamtscore = (Desktop-Score + Mobile-Score) / 2
```

### Datenquellen-Stack

| Quelle | Zweck | Neu? |
|---|---|---|
| HTML-Parsing (bestehend) | U1–U4 deterministische Checks | Nein |
| PSI Mobile (bestehend) | U5 Mobile Performance | Nein |
| PSI Desktop | U5 Desktop Performance | **Ja** — zweiter PSI-Call mit `strategy=desktop` |
| Screenshot 1280px | Desktop-Vorschau + Vision-Input | Viewport-Änderung nötig |
| Screenshot 375px | Mobile-Vorschau + Vision-Input | Neu |
| Vision-LLM ×2 | Kommentartext Desktop + Mobile | Ja (2 statt 1 Call) |

### UI-Konzept

```
[Desktop] [Mobile]   ← Tabs im #view-ux
────────────────────────────────────
Score: 74%   Gut
[U1 ✓] [U2 ◑] [U3 ✓] [U4 ◑] [U5 ✗]

U1 — Above-the-Fold           ✓ grün
  Befund: H1 vorhanden, Hero-Image erkannt...
  LLM: "Der Nutzen ist klar kommuniziert, jedoch..."

U2 — Ablenkungsfreiheit        ◑ amber
  ...

Screenshot: [Desktop 1280px Vorschau]
```

### Offene Implementierungsfragen

| # | Frage | Entscheidung |
|---|---|---|
| ~~OE-4~~ | `ux.php`: Ein kombinierter oder zwei separate Proxy-Calls? | ✅ **Option B — zwei separate Calls** — Mobile zuerst, Desktop lädt progressiv nach. Desktop-PSI via `pagespeed.php?strategy=desktop`. |
| ~~OE-5~~ | Demo-Modus: Beide Devices oder nur Mobile? | ✅ **Beide Devices** — synthetische Daten für Desktop + Mobile vollständig simuliert (UI-Review im Demo-Modus ist primärer Zweck). |

---

## Phase 6 — M2 Technical SEO: Vollausbau ✅ (18.06.2026)

> **Basis:** Guide-basierte Analyse (Shortlist Technical SEO Checklist)  
> **Ziel:** 25 deterministische Checks, gegliedert in 5 inhaltliche Cluster — analog zu SQEG.  
> **Commits:** `8e0cd1f` (T12 Sitemap) · `c45d8f5` (T13–T25 + Cluster-Layout)

### Check-Übersicht (25 Prüfpunkte)

| Cluster | IDs | Prüfpunkte |
|---|---|---|
| **A — Indexierbarkeit & Crawling** | T1, T2, T8, T9, T12, T19 | noindex, Canonical, URL-Struktur, HTTPS, Sitemap-Check, Cross-Domain-Canonical |
| **B — On-Page Meta & Markup** | T3, T4, T5, T7, T13, T14, T15, T16 | Title, Meta-Desc, H1, OG-Tags, Viewport, Schema.org, Heading-Hierarchie, Twitter Card |
| **C — Bilder & Ressourcen** | T6, T20, T21, T22, T23 | Alt-Texte, Render-blocking Scripts, Lazy Loading, WebP/AVIF, Mixed Content |
| **D — Performance & Core Web Vitals** | T10, T11, T17, T18 | CWV Mobile (LCP/CLS/TBT), Mobile PSI-Score, Desktop PSI-Score, INP |
| **E — Links & Seitenstruktur** | T24, T25 | Anchor-Texte (Qualität), Link-Anzahl |

### Architektur-Details

| Aspekt | Umsetzung |
|---|---|
| Score-Quelle | **Deterministisch** — HTML-Parsing + PSI Mobile + PSI Desktop |
| Neue Datenquellen | `fetchSitemapData()` (via fetch.php) · `psiDesktopData` (PSI strategy=desktop) |
| pagespeed.php | INP-Feld (`interaction-to-next-paint`) ergänzt |
| Cluster-Layout | Wie SQEG — aufklappbare Donut-Cards, Cluster mit roten Checks öffnen sich automatisch |
| Demo-Daten | `sitemapData` + `psiDesktopData` vollständig simuliert |

---

## Phase 7 — Local PV Generator: Tools-Modul ✅ (30.06.2026)

> **Ziel:** Eigenständiges Werkzeug in der Sidebar (keine URL-Analyse) — generiert fertige SEO- & CRO-Bausteine für lokale Photovoltaik-Landingpages.  
> **Commits:** `3d5ccc4` (Basis) · `17ff120` (OpenAI-Fallback) · `290e90f` (Vertical Card Layout) · `a16d523` (micro/content Zweistufigkeit)

### Architektur

| Aspekt | Umsetzung |
|---|---|
| Eingabe | Stadt / PLZ (Pflicht) + optionale Felder: Haupt-Keyword, LP-URL, Template-Typ |
| Datenquellen | GSC-Kontext, Sistrix-Kontext, DataForSEO-Kontext (optional, aus aktivem Analyse-Ergebnis) |
| Backend | `app/proxies/localpv.php` — Anthropic (primär) / OpenAI (Fallback) |
| Routing | `app/localpv.php` (thin include) → `router.php` |
| Sidebar | Neue „Tools"-Sektion zwischen UX/CRO und System, `data-view="localpv"` |
| Header | `showView()` blendet URL-Header-Form aus (`hf.style.display='none'`) |

### Output-Struktur (JSON)

```json
{
  "input": { "cityOrPostalCode": "...", "primaryKeyword": "...", "landingPageUrl": "..." },
  "meta": { "title": "...", "description": "..." },
  "hero": { "h1": "...", "subline": "...", "primaryCta": "...", "secondaryCta": "..." },
  "sections": {
    "intro":                 { "micro": "1–2 UI-Sätze", "content": "80–150 Wörter SEO-Text" },
    "solarPotential":        { "micro": "...", "content": "..." },
    "benefitsIntro":         { "micro": "...", "content": "..." },
    "statisticsExplanation": { "micro": "...", "content": "..." },
    "projectsIntro":         { "micro": "...", "content": "..." },
    "economicsText":         { "micro": "...", "content": "..." },
    "faqIntro":              { "micro": "...", "content": "..." },
    "formIntro":             { "micro": "...", "content": "..." }
  },
  "faq": [ { "question": "...", "answer": "80–120 Wörter" } ],
  "seoChecklist":  [ { "item": "...", "status": "ok|warning|missing", "note": "..." } ],
  "croChecklist":  [ { "item": "...", "status": "ok|warning|missing", "note": "..." } ],
  "recommendations": [ { "module": "...", "priority": "high|medium|low", "recommendation": "..." } ],
  "exportMarkdown": "vollständiges Markdown aller Bausteine"
}
```

### Zweistufige Section-Darstellung (UI)

Jede Section-Karte zeigt:
- **Micro / UI-Text** — akzentfarbig hinterlegt, sofort als Teaser/UI-Text erkennbar
- **Content / SEO-Text** — darunter, voller SEO-Absatz (80–150 Wörter)
- Kopieren übergibt beide Ebenen: `Micro:\n...\n\nContent:\n...`

### Prompt-Qualitätsregeln (System-Prompt)
- Conversion-Logik: PV-Rechner = primäre CTA, Formular = Backup-Conversion (sekundär)
- Verbotsliste: keine erfundenen Zahlen/Einstrahlungswerte, kein Tourismus-Content, keine Floskeln
- SEO- und CRO-Checklisten werden realistisch bewertet (nicht alles pauschal „ok")
- Ziel: Output direkt in echte Landingpage einbaubar, nicht nach KI klingend

### JS-Funktionen
- `pvGenerate()` — POST zu `localpv.php`, zeigt Loading/Error/Results
- `pvRenderResults(d)` — rendert alle 15 Kacheln in LP-Reihenfolge (Meta → Hero → 8 Sections → FAQ → SEO-CL → CRO-CL → Empfehlungen → Markdown)
- `pvCopySectionText(text, btn)` — Clipboard + visuelles Feedback
- `pvCopySection(key)` — strukturierte Sections (meta, hero, faq, checklists, recommendations)
- `pvChecklistHtml(items)` — Checklisten-HTML

### CSS-Klassen (`.pv-*`)
`.pv-results-list`, `.pv-card`, `.pv-card-label`, `.pv-card-body`, `.pv-copy-btn`, `.pv-data-hint`, `.pv-data-source-tag` (`.gsc` / `.sistrix` / `.dataforseo` / `.pvgis`), `.pv-sec-label`, `.pv-sec-micro`, `.pv-sec-content`, `.pv-faq-*`, `.pv-checklist-*`, `.pv-rec-*`, `.pv-hero-grid`, `.pv-loading`, `.pv-generate-btn`, `.pv-export-area`

### Phase 7.2 — Struktur-Upgrade + Tab-UI + Schärfungs-Pass ✅ (01.07.2026)

> **Commits:** `d444e1b` (P1 Backend) · `8aae31b` (P2 CSS) · `3151c7e` (P3 HTML) · `66289c0` (P4 JS) · `4476abc` (Schärfungs-Pass)

**Umgesetzt:**
- Neues JSON-Schema: `benefits` (4 Kacheln), `ctaStrategy` (primär/sekundär + Micro-CTAs), `placementMap` (11 Module), `processIntro`, `testimonialsIntro`, `hero.calculatorIntro`, `sections.*.placement`, `pvCalculatorInHero: true`
- Tab-UI: Content / Placement Map / SEO+CRO Checks / Markdown Export
- Placement Map als vertikale LP-Skizze mit Modul, Layout-Typ, Feldreferenzen, Einbauhinweis
- Benefits-Grid (4-Kacheln), CTA-Strategie-Karte mit klickbaren Beispielen
- `pvSwitchTab()`, `pvRefine()`, erweitertes `pvCopySection()`
- „Content schärfen"-Button → zweiter KI-Pass (`app/proxies/localpvrefine.php`) mit Refinement-System-Prompt

### Phase 7.2.1 — Level-3 Conversion-Pass + versionierter Speicher ✅ (01.07.2026)

> **Commit:** `cbf66c1`

**Umgesetzt:**
- Neuer Proxy `app/proxies/localpvconvert.php` — Level-3 KI-Pass auf Basis der Level-2-Ausgabe: selektive Conversion-Stärke (nur dort wo echte Schwächen), CTA-Logik, FAQ entscheidungsunterstützend, Micro-CTAs konkret
- Thin include: `app/localpvconvert.php` → `require_once proxies/localpvconvert.php`
- **Versionierter Speicher:** `pvVersions = {raw, sharpened, conversion}` — jede Stufe separat gespeichert
- **Version-Switcher:** Pill-Bar über den Tabs (Rohfassung / Content geschärft / Conversion optimiert) — beim Wechsel alle Tabs neu gerendert, nicht-verfügbare Versionen deaktiviert
- **„Conversion optimieren"-Button** im Tab-Bar neben „Content schärfen" — nur aktiv wenn Level-2 vorhanden
- JS-Funktionen: `pvConvert()`, `pvSwitchVersion()`, `pvUpdateVersionUI()`
- Pipeline: Raw → (Level 2) Content geschärft → (Level 3) Conversion optimiert — jederzeit rückwärts navigierbar

### Phase 7.3: Echtdaten-Kontext aus aktiver Analyse ✅ (14.07.2026)

> **Status:** Implementiert — `pvGenerate()` schickt nun `gscContext`, `sistrixContext`, `dataforseoContext` mit.

**Umgesetzt:**
- `pvGenerate()` liest globale Analyse-Vars (`gscData`, `sistrixData`, `serpData`) aus und schickt sie im POST-Body mit
- `gscContext`: `queries` (Top 10), aggregierte `clicks`/`impressions`/`avgPosition`
- `sistrixContext`: `visibility`, `kw_count`, `keywords` (Top 10)
- `dataforseoContext`: `search_volume` aus `keyword_info`, `serp_features` aus `item_types`
- `smartHint()` in `pvRenderResults()` — zeigt echte Quellen als grüne „Datenquelle:"-Badges, prospektive als graue „Perspektivisch:"-Badges
- DWD-Badge in Sidebar: „PVGIS / DWD (geplant)" → „DWD OpenData"

---

## Phase 7.4 — PV-Generator: PLZ → Stadtname-Auflösung für Keyword-Vorschläge ❌

> **Problem:** Gibt der Nutzer eine PLZ wie `61440` ein, generiert `pvSuggestKeywords()` nur Keywords mit der PLZ (z.B. „Photovoltaik 61440") — die praktisch null Suchvolumen haben. Der Stadtname „Oberursel" wird nie berücksichtigt.  
> **Erwartung:** Das Tool soll PLZs automatisch in Stadtnamen auflösen und Keywords mit dem echten Ortsnamen prüfen und vorschlagen.

### Lösungsarchitektur

| Schritt | Umsetzung |
|---|---|
| PLZ-Erkennung | Regex `^\d{5}$` im Frontend vor dem Keyword-Suggest-Call |
| Auflösung Quelle 1 | DWD-Response: `pvDwdData.station?.name` wird beim Generieren ohnehin abgerufen — Stadtname ggf. daraus ableiten |
| Auflösung Quelle 2 | `zippopotam.us/de/{PLZ}` (kostenlose, CORS-freie REST-API) → `places[0].place name` |
| Auflösung Quelle 3 | Fallback: eigenes PHP-Endpoint oder `openplzapi.org` (deutsche PLZ-Datenbank) |
| Integration | Erkannter Stadtname wird in `pv-city-resolved`-Hinweis unter dem Eingabefeld angezeigt und in `pvSuggestKeywords()` für die Keyword-Kombinationen verwendet |

### Keyword-Generierung nach Auflösung

**Vorher (PLZ):**  
→ „Photovoltaik 61440" (0 Suchvolumen), „PV Anlage 61440" (0 Suchvolumen)

**Nachher (PLZ + aufgelöster Stadtname):**  
→ „Photovoltaik Oberursel" · „PV Anlage Oberursel" · „Solaranlage Oberursel" · „Photovoltaik 61440" (als Ergänzung, mit Hinweis)

### UI-Ergänzungen

- Unter dem Stadtfeld: kleiner Hinweis `→ Erkannter Ort: Oberursel (Taunus)` wenn PLZ aufgelöst
- `pvGenerate()` nutzt den aufgelösten Stadtnamen intern als `resolvedCity` im Body (zusätzlich zu `cityOrPostalCode`)
- Backend `localpv.php`: `resolvedCity` im Prompt verwenden wenn vorhanden (konkreterer Stadtname statt PLZ)
- Keyword-Vorschläge: Kombinationen mit `{Produkt} {resolvedCity}` priorisiert, PLZ-Kombis als Zusatz

### Betroffene Dateien

| Datei | Änderung |
|---|---|
| `app/index.php` | PLZ-Erkennung + Auflösungs-Call + UI-Hinweis + `pvSuggestKeywords()` anpassen |
| `app/proxies/localpv.php` | `resolvedCity`-Feld im Prompt-Context verarbeiten |

---

## Phase 7.5 — PV-Generator: „Conversion optimieren" direkt auf Rohfassung ❌

> **Problem:** Der Conversion-Pass (Level 3) setzt aktuell voraus, dass Level 2 („Content schärfen") bereits durchgeführt wurde — `pvConvert()` prüft `pvVersions.sharpened` und tut nichts wenn null.  
> **Erwartung:** „Conversion optimieren" soll auch direkt auf der Rohfassung (Level 1) funktionieren — der Nutzer soll nicht erst schärfen müssen.

### Aktueller Zustand

```
Raw → [Content schärfen] → [Conversion optimieren]   ← Conversion blockiert wenn kein sharpened
```

### Ziel-Zustand

```
Raw → [Content schärfen] → [Conversion optimieren]   ← weiterhin möglich
Raw →                      [Conversion optimieren]   ← NEU: auch direkt
```

### Umsetzung

| Was | Wie |
|---|---|
| `pvConvert()` | Basis-JSON: `pvVersions.sharpened ?? pvVersions.raw` statt `pvVersions.sharpened` — falls kein sharpened vorhanden, Raw als Input |
| Version-Switcher | „Conversion optimiert"-Button auch aktiv wenn nur `pvVersions.raw` vorhanden (nicht erst nach Level 2) |
| Version-Label | Wenn Conversion direkt auf Raw basiert: Pill-Label z.B. „Conversion (auf Rohfassung)" statt „Conversion optimiert" — damit Nutzer die Basis kennt |
| `pvConvert()` Guard | Statt `if(!pvVersions.sharpened)return;` → `if(!pvVersions.raw)return;` |

### Betroffene Dateien

| Datei | Änderung |
|---|---|
| `app/index.php` | `pvConvert()` Guard + Basis-JSON + Version-Label-Logik + Button-Aktivierung |

---

## Phase 7.6 — PV-Generator: Placement Map Tab entfernen ❌

> **Begründung:** Der Content-Tab zeigt alle Kacheln bereits in LP-Reihenfolge (Meta → Hero → Intro → Solarpotenzial → Kennzahlen → Prozess → Referenzen → Kundenstimmen → FAQ → Formular). Jede Kachel hat zudem einen inline Placement-Hinweis (`.pv-placement-badge`). Die separate Placement Map ist damit redundant.

### Umsetzung

| Was | Wie |
|---|---|
| Tab-Button entfernen | `<button onclick="pvSwitchTab('placement',this)">Placement Map</button>` löschen |
| Tab-Panel entfernen | `<div id="pv-tab-placement">` + Inhalt löschen |
| Render-Code entfernen | `// ── Tab 2: Placement Map ──`-Block in `pvRenderResults()` löschen |
| Prompt-Feld entfernen | `placementMap`-Feld aus dem User-Prompt in `localpv.php` und `localpvrefine.php` entfernen → Tokens sparen, kürzere Generierungszeit |
| CSS bereinigen | `.pv-placement-*`-Klassen entfernen |

> **Hinweis:** `placement`-Felder auf einzelnen Section-Karten (`.pv-placement-badge`) können bleiben — die sind nützlich und gehören zu den Content-Karten.

---

## Phase 8 — Content Finder: Vollständige Seitenanalyse nach Begriffen ✅ (09.–10.07.2026)

> **Ziel:** Eigenständiges Tool (keine URL-Analyse) — sucht eine oder mehrere Seiten vollständig nach definierten Begriffen, inkl. JS-Rendering und Bild-OCR.  
> **Commits:** `284c1dd` (Basis) · `a58d7f7` (BFS-Queue / Crawl-Tiefe) · `ba078e2` (Pfad-Filter) · `789b68d` (Ausschluss-Begriffe) · `9997e1c` (Tooltips)

### Anforderungen

| Anforderung | Umsetzung |
|---|---|
| Eine oder mehrere URLs | Manuelle Textarea (eine URL pro Zeile) + CSV-Datei-Upload |
| 100 %-Erfassung | Puppeteer (JS-Rendering, Cookie-Banner-Dismiss, Scroll) + OpenAI Vision OCR |
| Mehrere Suchbegriffe | Chip-UI, unbegrenzt viele Begriffe |
| Synonyme / Varianten | Regelbasiert (Bindestrich, Umlaut, Plural) + KI-Synonyme (OpenAI, gecacht) |
| Ausschluss-Begriffe | Wortkontext-Filter, live ohne Re-Crawl |
| Crawl-Tiefe 1 / 2 | BFS-Queue, Pfad-Filter (nur Unterseiten des Seed-Pfads) |
| Export | CSV (UTF-8-BOM) + JSON |

### Architektur

```
Browser → app/contentfinder.php (thin include)
           → app/proxies/contentfinder.php
               ├── action=synonyms  → regelbasierte Varianten + OpenAI (gecacht)
               └── action=crawl_url → contentfinder_extract.mjs (Puppeteer)
                                       → OpenAI Vision (Bild-OCR, optional)
                                       → PHP Regex-Suche in allen Blöcken
                                       → {hits, links} zurück ans Frontend
```

### Dateien

| Datei | Inhalt |
|---|---|
| `app/contentfinder_extract.mjs` | Node.js: Puppeteer-Extraktion — Text, Alt, ARIA, SVG, Tooltips, interne Links |
| `app/proxies/contentfinder.php` | PHP: Auth, `synonyms`-Action, `crawl_url`-Action, OCR via Vision API, `buildVariants()`, `searchBlocks()` |
| `app/contentfinder.php` | Thin include |
| `app/index.php` | View `#view-content-finder` + CSS `.cf-*` + JS `cf*()` |

### Content-Extraktion (vollständig)

Puppeteer extrahiert nach JS-Rendering und Scroll:

| Typ | Quelle |
|---|---|
| Meta-Title | `<title>` |
| Meta-Description | `<meta name="description">` |
| OG-Title | `<meta property="og:title">` |
| Überschriften | `<h1>`–`<h6>` |
| Fließtext | `<p>`, `<li>`, `<td>`, `<th>`, `<blockquote>` |
| Interaktiv | `<button>`, `<a>`, `<label>` |
| ARIA | `[aria-label]` |
| Tooltips | `[title]` |
| SVG | `<svg title>` |
| Bilder (Text) | `alt`-Attribut + OpenAI Vision für Bilder ohne Alt |
| Interne Links | Alle `<a href>` auf gleichem Origin (für BFS-Crawl) |

### Varianten-Generierung

Für jeden Suchbegriff werden automatisch generiert:

| Typ | Beispiel |
|---|---|
| **Exakt** | `BEG Förderung` |
| **Bindestrich** | `BEG-Förderung` |
| **Umlaut** | `BEG Foerderung`, `BEG-Foerderung` |
| **Plural** | `BEG Förderungen`, `BEG Förderunge`, … |
| **Singular** | (Endung entfernen, wenn erkannt) |
| **KI-Synonym** | `Bundesförderung effiziente Gebäude`, `BAFA Förderung`, `KfW Heizungsförderung` |

### Crawl-Tiefe & Pfad-Filter

- Tiefe 0: Nur die eingegebenen URLs
- Tiefe 1: + alle Links, die auf der Seite gefunden werden und **denselben Pfad-Präfix** haben
- Tiefe 2: + eine Ebene weiter
- Pfad-Filter: `https://domain.de/strom` → folgt nur Links unter `/strom/`
- Domain-Grenze: Nur Links auf derselben Domain wie die erste Seed-URL
- Sicherheitslimit: max. 200 URLs pro Lauf

### Ausschluss-Begriffe

- Eingabe per Chip-UI (like Suchbegriffe, aber rot)
- Logik: Das vollständige Wort rund um den Treffer wird gegen alle Ausschluss-Begriffe geprüft
- Beispiel: Suche `BEG`, Ausschluss `GewerBEGas` → Treffer in „GewerBEGas" wird unterdrückt
- Live-Filter: Kein Re-Crawl, wirkt sofort auf alle Ergebnisse
- Export berücksichtigt Ausschluss-Filter

### UI-Elemente

- **Step 1:** URL-Eingabe (Textarea / CSV-Upload) + Crawl-Tiefe
- **Step 2:** Suchbegriffe (Chips) + Varianten-Vorschau + Ausschluss-Begriffe
- **Step 3:** Erfassungsoptionen (8 Toggles mit Tooltips)
- **Fortschritt:** Sub-Schritte je URL (Abruf → JS → OCR → Suche) + dynamische Liste
- **Ergebnisse:** Stat-Grid (4 Kacheln) + filterbare Tabelle + Export
- **Tooltips:** Auf allen nicht-selbsterklärenden Elementen (`data-tip`)

### JS-Funktionen

| Funktion | Aufgabe |
|---|---|
| `cfAddTerm(term)` | Begriff als Chip hinzufügen, Varianten vom Backend holen |
| `cfFetchVariants(term)` | `synonyms`-Action aufrufen, Ergebnis in `cfTerms` speichern |
| `cfShowVariantPreview(term)` | Varianten-Vorschau-Box aktualisieren |
| `cfAddExclude()` | Ausschluss-Begriff hinzufügen + Tabelle sofort neu rendern |
| `cfIsExcluded(hit)` | Prüft ob Treffer durch Ausschluss unterdrückt werden soll |
| `cfStart()` | BFS-Queue initialisieren, Analyse starten |
| `cfCrawlUrl(url, idx)` | Eine URL crawlen, Ergebnis inkl. Links zurückgeben |
| `cfAppendCrawlItem(url, idx)` | URL dynamisch zur Crawl-Liste hinzufügen |
| `cfFinish()` | Stats berechnen, Filter-Dropdowns füllen, Tabelle rendern |
| `cfRenderTable()` | Treffel-Tabelle mit allen aktiven Filtern + Ausschluss rendern |
| `cfExport(format)` | CSV (UTF-8-BOM) oder JSON-Download |

---

## Phase 8 — Agenten-System: transparente KI-Spezialisten

> **Ziel:** Jeder KI-Modul-Call ist einem benannten Agenten zugeordnet. User kann Agenten anklicken, System-Prompt lesen, bearbeiten und dauerhaft in settings.json speichern.  
> **Entschieden:** 02.07.2026

### Architektur

| Agent | Modul | Prompt editierbar |
|---|---|---|
| 🔍 SQEG-Analyst | M1 SQEG | ✅ |
| 📐 UX-Experte | M5 UX/CRO | ✅ |
| 🏗️ PV-Content-Stratege | Local PV Generator | ✅ |
| ⚙️ Technical-Checker | M2 Technical SEO | ❌ (Regelwerk, kein LLM) |
| 📊 Performance-Reader | M3 Performance | ❌ (Datenquelle) |
| 🎯 Keyword-Analyst | M6 Keyword Fit | ❌ (Datenquelle) |

**Speicherung:** Custom-Prompts → `settings.json` unter `agent_prompt_<id>`  
**Proxy:** `settings_save.php?action=save_agent_prompt` (POST, CSRF-geschützt)  
**Status-Tracking:** `idle → running → done | error` mit farbigem Dot im Badge  
**Modal:** System-Prompt (editierbar) + letzter Raw-Output (read-only, collapsible)

### Schritt 8.1 — SQEG-Analyst ✅ (02.07.2026)
- Agent-Badge im SQEG-View
- Modal: Prompt-Textarea + letzter Output + Speichern + Reset
- `runMiniCall()` nutzt custom Prompt aus settings.json (Fallback: Default)
- Status-Tracking: `idle → running → done/error`

### Schritt 8.2 — UX-Experte ❌ (noch nicht implementiert)
### Schritt 8.3 — PV-Content-Stratege ❌ (noch nicht implementiert)

---

## Offene Entscheidungen (blockieren jeweils den nächsten Schritt)

| # | Frage | Betrifft |
|---|---|---|
| ~~OE-1~~ | ~~GEO-Panel: Entity-Name als Eingabefeld im Header oder automatisch aus Domain ableiten?~~ | ✅ Automatisch aus Domain — entschieden 08.06.2026 |
| ~~OE-2~~ | ~~Railway Preview-Deploy einrichten~~ | ✅ Erledigt |
| ~~OE-3~~ | ~~Screenshot-API für UX-Modul auswählen~~ | ✅ Headless Chromium im Railway-Container — entschieden 09.06.2026 |

---

## Changelog

| Datum | Version | Änderung |
|---|---|---|
| 01.07.2026 | v3.11 | Local PV Generator: Level-3 Conversion-Pass (`localpvconvert.php`, `pvConvert()`), versionierter Speicher (raw/sharpened/conversion), Version-Switcher UI — `cbf66c1` |
| 01.07.2026 | v3.10 | Local PV Generator: Content-Schärfungs-Pass (`localpvrefine.php`, `pvRefine()`-Button) — `4476abc` |
| 01.07.2026 | v3.9 | Local PV Generator: Struktur-Upgrade (benefits, ctaStrategy, placementMap, Tab-UI, neue Sections) — P1–P4 `d444e1b`·`8aae31b`·`3151c7e`·`66289c0` |
| 30.06.2026 | v3.8 | Local PV Generator: Prompt-Upgrade mit micro/content Zweistufigkeit pro Section, neuer System-Prompt mit Conversion-Logik + Verbotsliste — `a16d523` |
| 30.06.2026 | v3.7 | Local PV Generator: Tools-Modul (Sidebar, `#view-localpv`, `app/proxies/localpv.php`), Anthropic/OpenAI-Fallback, vertikales Card-Layout, Datenquellen-Badges — `3d5ccc4`·`17ff120`·`290e90f` |
| 18.06.2026 | v3.6 | M2 Technical SEO v2: 14 neue Checks (T12–T25), 5 Cluster-Layout (wie SQEG), Desktop PSI + INP, Sitemap-Check — `c45d8f5` |
| 18.06.2026 | v3.5 | M2 Technical SEO: T12 Sitemap-Check (LP-URL in sitemap.xml via fetch.php) — `8e0cd1f` |
| 10.06.2026 | v3.4 | M2 Technical SEO Modul (deterministisch, 11 Checks, HTML-Parsing) — `107f4d8` |
| 10.06.2026 | v3.4 | Phase 5 Konzept: M5 UX/CRO v2 — deterministisch + Device-Split (Desktop/Mobile) — Konzept dokumentiert, noch nicht implementiert |
| 09.06.2026 | v3.3 | Phase 4: M5 UX/CRO-Modul — Headless Chromium, Vision-LLM, `#view-ux`, Modul-Kachel, Nav-Item, `app/proxies/ux.php` — (aktueller Commit) |
| 09.06.2026 | v3.2 | UX-Überarbeitung SQEG: Detailanalyse eingeklappt by default (Toggle-Button), Exec Summary 2+1 Layout (Gesamtbewertung+Probleme oben, Schritte volle Breite) — `f210d5d` |
| 09.06.2026 | v3.2 | Cluster-Übersicht: 1 Spalte, Kriterien aufklappbar pro Cluster, Bewertungstext score-abhängig — `cc9154e` |
| 09.06.2026 | v3.2 | Prioritäten-Matrix entfernt (redundant zu Exec Summary + Cluster) — `00ebbbb` |
| 09.06.2026 | v3.2 | Score-Hero: Kriterien-Zähler als farbige Chips (✓ / ◑ / ✗), Stat-Grid entfernt — `6214659` |
| 09.06.2026 | v3.2 | M6 Keyword-Fit-Modul: `app/proxies/keywords.php`, Intent-Analyse, neuer View + Sidebar, Demo-Daten — `3182ef5` |
| 09.06.2026 | v3.2 | Demo-Modus erweitert: GSC, Sistrix, GEO, Keyword-Fit Demo-Daten — `bebcac4` |
| 08.06.2026 | v3.1 | Score-Radar-Chart (SVG, 3 Achsen: SQEG/Performance/GEO) in Übersicht — `e33efee` |
| 08.06.2026 | v3.1 | Settings: ENV-Quellen-Transparenz — Felder gesperrt wenn Railway ENV aktiv, Status-Leiste — `e310563` |
| 08.06.2026 | v3.1 | Settings vollständig: DataforSEO, Sistrix, PageSpeed, OpenAI, GSC + Domain-Verwaltung; Tooltips für SERP-Badges/Score-Chips/GSC-Header — `5502147` |
| 08.06.2026 | v3.0 | Multi-View Dashboard: Übersicht/SQEG/Performance/GEO/Einstellungen mit Sidebar-Navigation, Modul-Kacheln, Top-Prioritäten — `7050e28` |
| 08.06.2026 | v2.x | Phase 3A: Cross-Modul KI-Synthese im Executive Summary — `f7556ff` |
| 08.06.2026 | v2.x | Phase 2: Modulare Architektur, Proxies, router.php Blockierung — `24243fc` |
| 08.06.2026 | v2.x | Sistrix-Erweiterungen Phase 1 komplett: domain.opportunities, domain.competitors.seo, GEO-Panel (ai.entity.prompts + sources), SERP-Feature-Badges in GSC-Tabelle (AI/FS/KG) |
| 22.05.2026 | v2.x | Sistrix API Parsing finalisiert — `d1c20c0` |
| 22.05.2026 | v2.0 | Header-Eingabebereich, Progressbar-Redesign, API-Verbindungstest — `ff3b675` |
| 21.05.2026 | v2.0 | UI-Redesign: Inter/Geist, Slate-Palette, Score-Hero, Expand-Rows, Skeleton — `f61c90e` |
| 21.05.2026 | v1.x | YMYL-Multiplikator, Schema.org, Wortanzahl, GSC Branded-Queries, Batch-Calls, Timer |
