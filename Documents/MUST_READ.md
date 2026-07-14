# LAT — Must Read

> **Pflege-Regel:** Nach jedem Deploy, der Design, Struktur, Roadmap oder kritischen Code betrifft,
> müssen dieses Dokument UND `/memories/repo/must-read.md` aktualisiert werden.
>
> **Entwicklungs-Governance:** Vor jeder Weiterentwicklung gelten die Regeln in `docs/project-guidelines.md`
> (Designsystem-Nutzung, Component Inventory, Checkliste, Known-Issues, Roadmap).

---

## Projekt

| | |
|---|---|
| **Repo** | https://github.com/julingn/LAT-Landingpage-Analyse-Tool |
| **Branch** | `main` → auto-deploy Railway |
| **Stack** | PHP 8.3 CLI Alpine, kein Framework |
| **Kern** | `app/index.php` (~6060 Zeilen — Monolith: CSS ~820 + HTML ~1150 + JS ~4320) |
| **Letzter Deploy** | `9c09638` — Phase A Repo-Hygiene (14.07.2026) |

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
| `app/contentfinder.php` | Content Finder: Seitensuche nach Begriffen (JS-Rendering, OCR, Synonyme) |

**Referenz-Template:** `app/dataforseo.php`

---

## Daten-Flow in `app/index.php`

```js
// 1. Globale Variablen (Suchanker: `let gscData=null`)
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
| Local PV Generator | `#view-localpv` | Standalone-Tool, kein URL-Header, `showView()` blendet header-form aus |
| **Content Finder** | `#view-content-finder` | Standalone-Tool, kein URL-Header, `showView()` blendet header-form aus |
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
4. `VIEW_META` Objekt (Suchanker: `const VIEW_META={`)

---

## Bekannte Bugs & Fixes

- **PHP Session-Lock**: IMMER `session_write_close()` direkt nach Auth-Check in JEDEM Proxy. Sonst 401 bei concurrent Batch-Calls.
- **JSON-Truncation** (`runMiniCall`): max_tokens=2500 + Fallback-Regex. Nicht auf 2000 senken.
- **`sleep` nicht definiert**: Muss als erste Zeile nach `<script>`: `const sleep=ms=>new Promise(r=>setTimeout(r,ms));`. Fehlt → startDemo() friert silent ein. Fix: `107f4d8`
- **Stray `}` in JS**: Ein überschüssiges `}` nach einer Funktion bricht den ganzen `<script>`-Block (alle Funktionen undefined). Symptom: Demo/Analyse tut nichts. Fix immer: stray `}` entfernen. Commits: `4da0d25`.
- **Doppelte `</div>` im HTML**: Bricht content-wrap — alle nachfolgenden Views landen außerhalb. Fix: `6eafea2`.
- **`startDemo()` hat kein try/catch**: Fehler brechen silent ab → immer Browser-Konsole (F12) prüfen.
- **⚠ UTF-8 BOM in index.php**: Falls ein externer Editor die Datei speichert, kann er ein UTF-8 BOM (Bytes `EF BB BF`) vorne einfügen. PHP gibt das BOM als Output aus → `session_start()` schlägt mit "headers already sent" fehl. Fix: `$b=[System.IO.File]::ReadAllBytes("app\index.php"); if($b[0] -eq 0xEF){$nb=$b[3..($b.Length-1)];[System.IO.File]::WriteAllBytes("app\index.php",$nb)}`
- **⚠ CRLF + BOM durch PowerShell-Writes**: `[System.Text.Encoding]::UTF8` (ohne Parameter) schreibt **mit BOM** und **CRLF**. Beides bricht die App auf dem Linux-Server. Einzig sichere Variante: `[System.Text.UTF8Encoding]::new($false)` für den Encoding-Parameter. Danach immer prüfen: `$b=[System.IO.File]::ReadAllBytes("f"); "BOM: $(($b[0..2]|%{$_.ToString('X2')})-join' ')"; "CRs: $(($b|?{$_-eq 0x0D}).Count)"` — beide müssen 0 sein.
- **⚠ NIEMALS PowerShell für Datei-Writes nutzen wenn `replace_string_in_file` reicht** — PowerShell-Writes (WriteAllLines, WriteAllText mit falschem Encoding) führen zu BOM + CRLF + versehentlichen Code-Duplikaten/Löschungen. `replace_string_in_file` ändert nichts am Encoding.
- **⚠ Duplikat-Code nach `return` in Funktion**: PowerShell-Writes können Code-Blöcke duplizieren. Ein `const X` das zweimal in derselben Funktion deklariert wird, erzeugt einen JavaScript SyntaxError → der gesamte `<script>`-Block wird nicht ausgeführt → Sidebar und alle JS-Funktionen broken (13.07.2026, `pvWidgetConfigHtml`).
- **⚠ Beim Löschen von Duplikat-Blöcken**: Immer prüfen ob die Grenzen korrekt sind. Das "Duplikat" kann echte Funktionen enthalten die danach definiert sind (`pvFootnoteHtml` war Teil des Duplikat-Blocks und wurde mitgelöscht).

### PV-Generator spezifische Bugs (14.07.2026)

- **`pv-template` getElementById nach Feld-Entfernung**: Wenn ein Input-Feld aus dem HTML entfernt wird, müssen ALLE JS-Referenzen darauf mitentfernt werden. `null.value` wirft einen stillen `TypeError` → `pvGenerate()` bricht ab ohne Fehlermeldung. Symptom: Klick auf „Bausteine generieren" tut nichts. Fix: `3ee9661`
- **`pvResolvePLZ` Carryover**: `pvDwdData` ist ein globales Objekt das nach jedem Generate-Call gesetzt bleibt. `pvResolvePLZ()` darf `pvDwdData.geocoded` nur nutzen wenn `pvDwdData.location === plz` — sonst wird die Stadt des letzten Aufrufs (z.B. Darmstadt) für eine neue PLZ (61440) zurückgegeben. Fix: `019ec39`
- **`mCtAs` vor Initialisierung in `pvRenderResults()`**: `microCtaBySection`-Lookup wird in `secDefs.forEach()` gebaut, referenziert `mCtAs` — das aber erst im `ctaHtml`-Block weiter unten mit `const mCtAs = ...` definiert ist (`let` würde TDZ-Error geben). Fix: `mCtAs` am Anfang von `pvRenderResults()` aus `d.ctaStrategy?.microCtas` lesen, vor `secDefs`. Commit: `76b05ac`
- **Sonnenstunden-Widget verschwindet bei DWD-Schätzung**: `pvWidgetConfigHtml()` gibt `''` zurück wenn `monthly_avg_sunshine_hours` fehlt. Der DWD-Fallback `dwdEstimateSolarByLat()` lieferte keine Monatswerte. Fix: Fallback berechnet Monatswerte aus Jahreswert × Klimanormal-Saisonprofil; Amber-Banner bei Schätzung. Commits: `c9d6fe3`, `5d9983b`
- **Kopieren-Button Feedback zu subtil**: `.pv-copy-btn.copied` nutzte `background: var(--green-bg)` (hellmintgrün `#F0FDF4` im Light Mode) — kaum sichtbar. Fix: Solid-Green (`background: var(--green); color: #fff`), `transition: 0.15s`, Timeout 2.5s, `execCommand`-Fallback. Commit: `881d7c2`

---

## Roadmap

Siehe `Documents/ROADMAP.md` für aktuelle Roadmap.

**Stand 10.07.2026 — implementierte Module & Tools:**
- ✅ M1 SQEG (LLM, 42 Kriterien, 8 Cluster)
- ✅ M2 Technical SEO (deterministisch, 11 Checks, `107f4d8`)
- ✅ M3 Performance (GSC + Sistrix)
- ✅ M4 GEO/AEO (Sistrix AI)
- ✅ M5 UX/CRO (Vision-LLM + Screenshot)
- ✅ M6 Keyword Fit (Sistrix searchintent)
- ✅ **Local PV Generator** (Tools-Sektion, `cbf66c1`, 30.06.–01.07.2026)
- ✅ **Content Finder** (Tools-Sektion, `9997e1c`, 09.–10.07.2026)

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
