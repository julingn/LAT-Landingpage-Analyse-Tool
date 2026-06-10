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
| **Letzter Deploy** | `107f4d8` — fix: sleep() fehlte (10.06.2026) |

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

## View-Struktur (Multi-View Dashboard)

| View | ID | Inhalt |
|---|---|---|
| Übersicht | `#view-overview` | Modul-Kacheln, Radar-Chart, Top-Prioritäten |
| SQEG | `#view-sqeg` | Score-Hero + Exec Summary + Cluster (aufklappbar) + Detailanalyse (eingeklappt) |
| Technical SEO | `#view-technical` | 11 deterministische Checks (HTML-Parsing, kein KI-Call) |
| Performance | `#view-performance` | GSC-Panel + Sistrix-Panel |
| GEO / AEO | `#view-geo` | Sistrix AI (entity.prompts + entity.sources) |
| Keyword Fit | `#view-keywords` | Intent-Analyse (Sistrix searchintent) |
| UX / CRO | `#view-ux` | Vision-LLM + Screenshot (Headless Chromium) |
| Einstellungen | `#view-settings` | API-Keys, Modell, Passwort |

**Alle Views müssen innerhalb von `<div class="content-wrap">` liegen** — max-width:960px;margin:0 auto

---

## Modul-Kacheln & Scores

Jedes Modul hat:
- **Sidebar-Nav**: `<button class="nav-item" data-view="X">` + `<span id="nav-score-X">`
- **Modul-Kachel**: `<div class="module-card" id="mc-X">` mit `mc-X-score`, `mc-X-bar`, `mc-X-label`
- **results/empty State**: `#X-results` (display:none) + `#X-empty` (display:block)

Beim Hinzufügen eines neuen Moduls müssen **immer** alle 4 Reset-Stellen aktualisiert werden:
1. Reset-Array in `startAnalysis()` (`['sqeg-results','perf-results',...]`)
2. Reset-Array in `startDemo()` (identisch)
3. `renderResults()` — Aktivierungs-Block für das neue Modul
4. `VIEW_META` Objekt (Zeile ~1271)

---

## Roadmap

Siehe `Documents/ROADMAP.md` für aktuelle Roadmap.

**Stand 10.06.2026 — implementierte Module:**
- ✅ M1 SQEG (LLM, 42 Kriterien, 8 Cluster)
- ✅ M2 Technical SEO (deterministisch, 11 Checks, `107f4d8`)
- ✅ M3 Performance (GSC + Sistrix)
- ✅ M4 GEO/AEO (Sistrix AI)
- ✅ M5 UX/CRO (Vision-LLM + Screenshot)
- ✅ M6 Keyword Fit (Sistrix searchintent)

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

## Bekannte Fallstricke & Bugs

| Problem | Ursache | Lösung |
|---|---|---|
| Dark Mode FOUC | CSS wird nach JS geladen | Inline-`<script>` im `<head>` liest `lat_theme` aus localStorage **vor** CSS-Load |
| Settings-UI für Datenquellen | API-Keys im Browser sichtbar | **Nicht machen** — nur Railway ENV |
| Hardcoded Farben | — | **Nicht machen** — immer `var(--)` |
| **PHP Session + concurrent API calls** | PHP hält Session-File-Lock | `session_write_close()` SOFORT nach Auth-Check in JEDEM Proxy — sonst 401 für alle gleichzeitigen Batches |
| **JSON-Truncation** (`runMiniCall`) | max_tokens zu niedrig | max_tokens=2500 + Fallback-Regex für abgeschnittene Arrays. Nicht auf 2000 senken. |
| **Doppelte schließende `</div>`** im HTML | Manuelle Edits im monolithischen index.php | Beim Einfügen von HTML-Blöcken immer mit 3-4 Zeilen Kontext davor/danach arbeiten. Nach jedem größeren HTML-Edit Browser-DOM prüfen. |
| **Stray `}` nach Funktion** | Falscher Insert-Punkt beim Einfügen von JS-Code | Ein überschüssiges `}` nach einer Funktion bricht den gesamten `<script>`-Block. Symptom: **alle** JS-Funktionen sind undefined. Fix: stray `}` entfernen. Commits: `6eafea2` (HTML), `4da0d25` (JS). |
| **`sleep` nicht definiert** | Funktion bei Refactoring verloren | `sleep` muss als `const sleep=ms=>new Promise(r=>setTimeout(r,ms));` direkt nach dem `<script>`-Tag stehen. Symptom: Demo startet (Button disabled), aber friert sofort ein. Commit: `107f4d8`. |
| **`startDemo()` hat kein try/catch** | Async-Fehler ohne Handler | Fehler in `startDemo()` brechen silent ab — keine Fehlermeldung im UI. Bei mysteriösem Einfrieren: Browser-Konsole öffnen (`F12 → Console`). |
| **Neues Modul: Reset-Arrays vergessen** | 4 Stellen müssen synchron sein | Beim Hinzufügen eines Moduls: Reset in `startAnalysis()`, Reset in `startDemo()`, Aktivierung in `renderResults()`, Eintrag in `VIEW_META`. Alle 4 oder keiner. |
