# LAT v3 — Roadmap: Modulare Analyse-Plattform

**Erstellt:** 05. Juni 2026  
**Basis:** Konzept-Session vom 05.06.2026 (PAGE360-Diskussion)  
**Ziel:** LAT entwickelt sich vom Single-File-SQEG-Analyzer zur vollständigen 6-Modul-Analyseplattform für einzelne URLs/Landingpages.

---

## Überblick: Die 6 Analyse-Module

| Modul | Frage | Datenquellen | Status |
|---|---|---|---|
| **M1 — SQEG** | Ist der Content qualitativ & vertrauenswürdig? | LLM (Anthropic/OpenAI) | ✅ Vorhanden (LAT v2) |
| **M2 — Technical SEO** | Ist die Seite technisch korrekt & indexierbar? | PageSpeed, HTML-Parsing | ⚠️ Teile vorhanden |
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

| # | Frage |
|---|---|
| OE-4 | `ux.php`: Ein Proxy-Call der beide Screenshots + beide PSI-Calls zurückgibt, oder zwei separate Calls? |
| OE-5 | Demo-Modus: Synthetische deterministische Befunde für beide Devices — ähnlich wie in M2 |

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
