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
| **M4 — GEO / AEO** | Wie präsent ist die Seite in KI-Antworten? | Sistrix `ai.*`-Endpunkte | ❌ Neu |
| **M5 — UX / CRO** | Wie erlebt ein Nutzer diese Seite? | LLM + Vision, Screenshot | ❌ Geplant (spätere Phase) |
| **M6 — Keyword Fit** | Targetet die Seite die richtigen Keywords? | Sistrix, DataforSEO, GSC | ❌ Neu |

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

### Schritt 2.2 — Neue Dateistruktur anlegen
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

### Schritt 2.3 — `router.php` anpassen
- Alle Proxy-Pfade von `app/*.php` auf `app/proxies/*.php` aktualisieren
- Neue Module-Endpunkte registrieren

### Schritt 2.4 — Tests auf Preview-Deploy
- Alle bestehenden Funktionen auf dem Feature-Branch testen
- Erst nach grünem Test: PR → main → Auto-Deploy auf Railway Production

---

## Phase 3 — Neue Module implementieren

> **Ziel:** M6 (Keyword Fit) + übergreifende KI-Synthese  
> **Aufwand:** Groß  
> **Von mir benötigt:** Nichts — reine Implementierung nach Architektur aus Phase 2

### Schritt 3.1 — M6: Keyword-Fit-Modul
- **Was:** Bewertet ob die analysierte Seite die richtigen Keywords targetet
- **Datenquellen:**
  - GSC-Daten (bereits vorhanden): aktuelle Rankings
  - Sistrix `domain.opportunities`: verpasste Chancen
  - Sistrix `keyword.seo.searchintent`: passt Intent zur Seite?
  - DataforSEO `search_volume/live`: Volumen der GSC-Top-Keywords
- **Output:** Score 0–100 + Liste: "Seite targetet X, sollte aber Y targetieren"

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

### Schritt 3.3 — Score-Hero erweitern
- Übergreifender Gesamtscore aus gewichteten Modul-Scores
- Modul-Scores als Radar-Chart oder Balken-Übersicht
- Gewichtung: SQEG 30%, Technical 25%, Performance 20%, GEO 15%, Keywords 10%

---

## Phase 4 — M5: UX/CRO-Modul

> **Ziel:** Analyse der Seite aus Nutzerperspektive  
> **Aufwand:** Sehr groß (Vision-LLM, Screenshot-API)  
> **Von mir benötigt:** Entscheidung über Screenshot-API (z.B. Browserless.io, ScreenshotOne)

### Schritt 4.1 — Screenshot-Integration
- Seiten-Screenshot via externer API oder Headless Chrome (Railway-Container)
- Screenshot wird als Base64 an Vision-LLM (GPT-4o / Claude) übergeben

### Schritt 4.2 — UX-Analyse-Prompt
- Bewertet: Klarheit des Value Propositions, CTA-Sichtbarkeit, Trust-Signale, visuelle Hierarchie, mobile Darstellung
- Output: Score 0–100 + 5 konkrete UX-Findings mit Screenshots-Markierungen (falls machbar)

---

## Offene Entscheidungen (blockieren jeweils den nächsten Schritt)

| # | Frage | Betrifft |
|---|---|---|
| ~~OE-1~~ | ~~GEO-Panel: Entity-Name als Eingabefeld im Header oder automatisch aus Domain ableiten?~~ | ✅ Automatisch aus Domain — entschieden 08.06.2026 |
| OE-2 | Railway Preview-Deploy einrichten (Dashboard-Klick) | Phase 2, Schritt 2.1 |
| OE-3 | Screenshot-API für UX-Modul auswählen | Phase 4, Schritt 4.1 |

---

## Changelog

| Datum | Version | Änderung |
|---|---|---|
| 08.06.2026 | v2.x | Sistrix-Erweiterungen Phase 1 komplett: domain.opportunities, domain.competitors.seo, GEO-Panel (ai.entity.prompts + sources), SERP-Feature-Badges in GSC-Tabelle (AI/FS/KG) |
| 22.05.2026 | v2.x | Sistrix API Parsing finalisiert — `d1c20c0` |
| 22.05.2026 | v2.0 | Header-Eingabebereich, Progressbar-Redesign, API-Verbindungstest — `ff3b675` |
| 21.05.2026 | v2.0 | UI-Redesign: Inter/Geist, Slate-Palette, Score-Hero, Expand-Rows, Skeleton — `f61c90e` |
| 21.05.2026 | v1.x | YMYL-Multiplikator, Schema.org, Wortanzahl, GSC Branded-Queries, Batch-Calls, Timer |
