# LAT — Must Read

> **Pflege-Regel:** Nach jedem Deploy, der Design, Struktur, Roadmap oder kritischen Code betrifft,
> müssen dieses Dokument UND `/memories/repo/must-read.md` aktualisiert werden.

---

## Projekt

| | |
|---|---|
| **Repo** | https://github.com/julingn/LAT-Landingpage-Analyse-Tool |
| **Branch** | `main` → auto-deploy Railway |
| **Stack** | PHP 8.3 CLI Alpine, kein Framework |
| **Kern** | `app/index.php` (~2600 Zeilen — PHP + HTML + CSS + JS) |
| **Letzter Deploy** | `f210d5d` — SQEG UX-Überarbeitung (09.06.2026) |

---

## API-Key-Verwaltung — KRITISCH

### Regel: Wer bekommt ein Settings-UI?

| Typ | Settings-UI | Railway ENV |
|---|---|---|
| Anthropic API-Key | ✅ | ✅ |
| OpenAI API-Key / Modell | ✅ | ✅ |
| Login-Passwort | ✅ | ✅ |
| **DataForSEO** | ❌ | ✅ `DATAFORSEO_LOGIN` / `DATAFORSEO_PASSWORD` |
| **PageSpeed** | ❌ | ✅ `PAGESPEED_API_KEY` |
| **Google Search Console** | ❌ | ✅ `GSC_SERVICE_ACCOUNT_JSON` / `GSC_SITE_URL` |
| **Sistrix** | ❌ | ✅ `SISTRIX_API_KEY` |

**→ Alle externen Datenquellen laufen ausschließlich über Railway-Umgebungsvariablen.**

### Config-Muster (`app/config.php`)

```php
define('CFG_XYZ', cfg('ENV_KEY', 'settings_json_key'));
```

`cfg()` priorisiert: **ENV → settings.json → default**

---

## PHP-Proxy-Muster

Alle API-Calls laufen serverseitig — der Browser sieht nie einen API-Key.

**Struktur jedes Proxys:**
1. `session_start()` + Login-Check → 401
2. CSRF-Token-Validierung → 403
3. `require_once config.php` → Credentials aus `CFG_*`
4. cURL-Request → JSON-Response

**Bestehende Proxys:**

| Datei | Zweck |
|---|---|
| `app/dataforseo.php` | SERP + Backlinks |
| `app/gsc.php` | Google Search Console |
| `app/pagespeed.php` | PageSpeed Insights |
| `app/sistrix.php` | URL-Sichtbarkeit + Keywords (DE) |
| `app/keywords.php` | Keyword-Fit: Sistrix `keyword.seo.searchintent` (parallel cURL) |

**Referenz-Template:** `app/dataforseo.php`

---

## Daten-Flow in `app/index.php`

```js
// 1. Globale Variablen (Zeile ~1209)
let gscData=null, serpData=null, backlinkData=null, psiData=null, sistrixData=null, geoData=null, kwData=null;

// 2. Reset bei jedem Start (startAnalysis + startDemo)
gscData=null; serpData=null; backlinkData=null; psiData=null; sistrixData=null; geoData=null; kwData=null;

// 3. Parallel fetchen
const [gscRes, serpRes, blRes, psiRes, sistrixRes, geoRes] = await Promise.allSettled([
  fetchGscData(url),
  fetchSerpData(keyword),
  fetchBacklinkData(url),
  fetchPageSpeedData(url),
  fetchSistrixData(url),
]);

// 4. Rendern
renderResults() → rendert alle Panels
```

---

## Settings-Panel Struktur (`app/index.php`)

1. Anthropic API-Key
2. KI-Modell (Anthropic / OpenAI)
3. Login-Passwort ändern
4. **API-Verbindungen** — Verbindungstest für alle 5 APIs (KI, DataForSEO, GSC, Sistrix, PageSpeed)
5. Darstellung (Dark Mode)
6. Entwickler-Optionen (Demo-Button)

**→ Keine neuen Sections ohne explizite Anfrage hinzufügen.**

---

## CSS-Design-System

- **Nur `var(--*)` verwenden** — keine hardcodierten Farben
- **Light-Mode** (`:root`): `--bg`, `--bg2`, `--bg3`, `--bg4`, `--border`, `--border2`, `--text`, `--text2`, `--text3`, `--accent`, `--accent2`, `--accent-bg`, `--accent-border`
- **Dark-Mode** (`[data-theme="dark"]`): Navy-Palette — `--bg:#0D1525`, `--bg2:#172035`, `--bg3:#09111D` etc. + `--green`, `--amber`, `--red`, `--blue`
- **Fonts:** Inter (UI), Geist Mono (Mono/Code)
- **FOUC-Prevention:** Inline-`<script>` im `<head>` liest `lat_theme` aus localStorage vor erstem Paint

---

## Ergebnisstruktur (Results-Section)

| Reihenfolge | Element | ID/Klasse |
|---|---|---|
| 1 | Gesamtscore | `.score-hero` |
| 2 | KI-Executive Summary | `.exec-summary-card` |
| 3 | Stat-Grid (Grün/Amber/Rot) | `.stat-grid` — 3 Spalten, kein PQ-Erweitert |
| 4 | Cluster-Übersicht | `#cluster-overview` |
| 5 | SQEG-Scale | `.sqeg-scale` |
| 6 | Needs-Met-Block | `.needs-met-block` |
| 7 | GSC-Keywords | `#gsc-panel` |
| 8 | Sistrix URL-Sichtbarkeit | `#sistrix-panel` |
| 9 | Priority-Matrix | `#priority-matrix` |
| 10 | Kriterien-Tabelle | `#criteria-table` |

---

## Roadmap / Offene Punkte

- [x] Visuelle Hierarchie Runde 2 — `aa34eba`
- [x] Dark Mode (Navy-Tokens) — `d08c4bb`
- [x] Input Hero (sticky, kondensiert) — `cb41b2b`
- [x] PQ-Erweitert entfernt (Duplikat) — `b2414b3`
- [x] Sistrix Integration — `e6890ff` → KEY `SISTRIX_API_KEY` in Railway setzen
- [x] Session-Lock-Fix (`session_write_close`) in allen Proxys — `76f498d` / `9f0cbdf`
- [x] Progressbar-Redesign (Zeit+% prominent) + API-Verbindungstest in Einstellungen — `ff3b675`
- [x] Eingabebereich vollständig in Header integriert — `0d1b9bb`
- [x] Sistrix API korrekt eingebunden (domain.visibilityindex + keyword.domain.seo) — `d1c20c0`
- [ ] ...

---

## Header-Eingabebereich

Der Eingabebereich ist vollständig im sticky `<header>` integriert (kein separater `input-hero`-Block mehr).

**Struktur:**
```
[Header top row]  SQEG Analyzer | Google SQEG
[workspace-header-form #header-form]
  Zeile 1: URL-Input  +  [URL][HTML]-Switch
  Zeile 2: ▾ Analyse verfeinern  (auf-/zuklappbar)
           [Keyword] [Conversion-Ziel] [Zielgruppe]
  Zeile 3: [▶ Analyse starten]  [Demo]
```

- `input-dimmed` wird auf `#header-form` angewendet (nicht mehr auf `#panel-sqeg > .input-card`)
- Kein Scroll-Listener mehr (`condensed`-Logik entfernt)
- Kein `input-hero`-Div mehr im DOM

---

## Sistrix API — Korrekte Endpunkte & JSON-Struktur

| Endpunkt | Parameter | Response-Pfad |
|---|---|---|
| `domain.visibilityindex` | `domain=mvv.de&country=de` | `answer[0]['sichtbarkeitsindex'][0]['value']` |
| `domain.kwcount.seo` | `domain=mvv.de&country=de` | `answer[0]['kwcount.seo'][0]['value']` |
| `keyword.domain.seo` | `url=https://...&country=de&limit=20` | `answer[0]['result'][n]['kw'/'position'/'traffic']` |

**Wichtig:**
- `domain.overview` → **nicht verwenden** — liefert für URL-Level immer "no result"
- Felder haben **kein `@`-Prefix** (JSON-Format, nicht XML)
- Domain aus URL: `preg_replace('/^www\./i', '', parse_url($url)['host'])`
- `keyword.domain.seo` wird mit der **vollen URL** aufgerufen, die anderen mit der **Domain**

---

## Progressbar-Design

```html
<!-- Struktur -->
<div class="progress-header">
  <span class="progress-label" id="progress-label">Analyse startet…</span>
  <span style="display:flex;align-items:center;gap:14px">
    <span class="progress-timer-stat" id="progress-timer"></span>  <!-- z.B. 12.3s -->
    <span class="progress-pct" id="progress-pct">0%</span>       <!-- 26px, accent -->
  </span>
</div>
<div class="progress-bar-bg"><div class="progress-bar" id="progress-bar"></div></div>
```

- **Zeit + Prozent** stehen oben rechts, prominent, ÜBER der Bar
- `.progress-pct`: `font-size:26px; font-weight:700; color:var(--accent); font-family:Geist Mono`
- `.progress-bar-bg`: `height:8px` — dünn, dezent

---

## API-Verbindungstest

Jeder Proxy hat einen `?action=test` GET-Handler (kein POST/CSRF):

| Proxy | Test-Endpunkt | Was er prüft |
|---|---|---|
| `api.php` | `?action=test` | Mini-Call (3 Tokens) an Anthropic/OpenAI |
| `dataforseo.php` | `?action=test` | `/v3/appendix/user_data` → Guthaben |
| `gsc.php` | `?action=list` | Service-Account + Properties auflisten |
| `sistrix.php` | `?action=test` | Credits-Endpoint |
| `pagespeed.php` | `?action=test` | Prüft nur ob Key konfiguriert (kein echter Call — zu langsam) |

JS-Funktionen: `testApiConn(name)` + `testAllApis()`

---

## Bekannte Fallstricke

| Problem | Lösung |
|---|---|
| Dark Mode FOUC | Inline-`<script>` im `<head>` — **vor** CSS-Load |
| Settings-UI für Datenquellen | **Nicht machen** — nur Railway ENV |
| Hardcoded Farben | **Nicht machen** — immer `var(--)` |
| PQ-Erweitert war Duplikat | Entfernt — war nur Re-Render von Cluster 5 |
| **PHP Session + concurrent API calls** | `session_write_close()` SOFORT nach Auth-Check in JEDEM Proxy — sonst hält PHP die Session-Datei-Lock für die gesamte API-Call-Dauer → 401 für alle gleichzeitigen Batches |
