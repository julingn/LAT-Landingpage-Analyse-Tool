# Prompt für Claude Sonnet 4.6 Agent — evalu-pro Rebuild

Übergib diesen Text vollständig an den Agenten:

---

## Aufgabe

Erstelle ein internes Web-Tool namens **evalu-pro** als selbstständig lauffähige PHP-Anwendung (kein Framework, kein Build-Step).  
Das Tool besteht aus **drei Dateien** die du vollständig und sofort ausführbar generieren sollst:

1. `login.php` — Login-Seite mit Session-Auth
2. `app/index.php` — Haupt-App mit Sidebar + SQEG Analyzer  
3. `app/api.php` — KI-Proxy (Anthropic Claude)

Außerdem: `app/settings.json` (initiale leere Konfigurationsdatei, nur JSON-Struktur).

---

## Design-System (exakt einzuhalten)

```css
/* Fonts via Google Fonts */
Bricolage Grotesque (Überschriften, Branding)
DM Sans (Body, UI)
DM Mono (Code, URLs, Keys)

/* CSS-Variablen */
--bg:#f8f7f5; --bg2:#ffffff; --bg3:#f2f1ef;
--border:#e3e2df; --border2:#d0ceca;
--text:#1a1917; --text2:#4a4845; --text3:#908d8a;
--accent:#4338ca; --accent2:#3730a3;
--accent-bg:rgba(67,56,202,.07); --accent-border:rgba(67,56,202,.18);
--green:#15803d; --green-bg:#f0fdf4; --green-border:#bbf7d0;
--amber:#b45309; --amber-bg:#fffbeb; --amber-border:#fde68a;
--red:#dc2626; --red-bg:#fef2f2; --red-border:#fecaca;
--radius:10px; --radius-lg:14px;
```

---

## 1. `login.php`

Anforderungen:
- Session-basierter Login (`session_start()`)
- Passwort wird in `app/settings.json` unter dem Key `login_password_hash` als `password_hash(..., PASSWORD_DEFAULT)` gespeichert
- Default-Passwort falls kein Hash hinterlegt: `evalupro2025`
- Bei erfolgreichem Login: Redirect auf `app/index.php`
- Bereits eingeloggt? Direkt redirect.
- Logout via `?logout=1`
- Design: zentrierte Karte, weißer Hintergrund, Indigo-Akzent, Brand-Logo oben  
  Logo: `<div>` mit Gradient (--accent → --accent2) + SVG-Hexagon-Icon + `evalu-pro` in Bricolage Grotesque
- Fehlermeldung bei falschem Passwort (rot, dezent)
- Kein Username — nur Passwort
- CSRF-Token im Hidden-Field (Token in Session speichern, bei POST prüfen)

HTML-Struktur Login-Karte:
```
[ Logo + Markenname    ]
[ "Internes SEO-Tool"  ]
[                      ]
[ Passwort: [________] ]
[ [Anmelden →]         ]
[ Fehlermeldung        ]
```

---

## 2. `app/index.php`

### Session-Guard
```php
session_start();
if (empty($_SESSION['logged_in'])) {
    header('Location: ../login.php'); exit;
}
```

### Layout: App-Shell

```
┌──────────────────────────────────────────────────┐
│ SIDEBAR (216px fix, links, scrollbar)             │
│  ┌────────────────────────────────────────────┐  │
│  │ Logo + Brand                               │  │
│  │ ─────────────────────────────────────────  │  │
│  │ [nav] SQEG Analyzer        (aktiv = .active)│  │
│  │ ─────────── System ────────────────────── │  │
│  │ [nav] Einstellungen                        │  │
│  │                                            │  │
│  │ [footer] evalu-pro v1.0 · Abmelden         │  │
│  └────────────────────────────────────────────┘  │
│                                                    │
│ MAIN-CONTENT (margin-left:216px)                  │
│  ┌──────────────────────────────────────────────┐ │
│  │ TOP-BAR (fixed, height:72px, z-index:99)    │ │
│  │  URL-Input | [URL / HTML] Toggle | [Start]  │ │
│  └──────────────────────────────────────────────┘ │
│                                                    │
│  CONTAINER (max-width:900px, padding:92px 28px)   │
│  [TOOL PANELS — nur aktives sichtbar]             │
│                                                    │
└──────────────────────────────────────────────────┘
```

**Sidebar CSS:**
```css
.app-shell { display:flex; min-height:100vh; }
.sidebar {
  width:216px; flex-shrink:0; position:fixed;
  top:0; left:0; bottom:0; z-index:100;
  background:#fff; border-right:1px solid var(--border);
  box-shadow:2px 0 8px rgba(26,25,23,.04);
  display:flex; flex-direction:column; overflow-y:auto;
}
.sidebar-logo { 
  padding:0 16px; display:flex; align-items:center; gap:10px;
  border-bottom:1px solid var(--border); height:72px; flex-shrink:0; 
}
.nav-item {
  display:flex; align-items:center; gap:10px; width:100%;
  padding:9px 10px; border:none; border-radius:8px; background:none;
  cursor:pointer; text-align:left; color:var(--text2); margin-bottom:1px;
  transition:background .15s,color .15s; font-family:inherit; font-size:13px; font-weight:600;
}
.nav-item:hover { background:var(--bg3); color:var(--text); }
.nav-item.active { background:var(--accent-bg); color:var(--accent); font-weight:700; }
.main-content { margin-left:216px; flex:1; min-width:0; }
.container { max-width:900px; margin:0 auto; padding:92px 28px 32px; }
.tool-panel { display:none; }
.tool-panel.active { display:block; }
```

**Top-Bar:**
- `position:fixed; top:0; left:216px; right:0; z-index:99; background:var(--bg2); border-bottom:1px solid var(--border); height:72px; padding:0 28px;`
- Enthält: URL-Input (DM Mono, `placeholder="https://www.beispiel.de/seite"`), Modus-Toggle (URL / HTML einfügen), Start-Button
- Bei "HTML einfügen": Textarea darunter ausklappen (`position:fixed; left:216px; right:0; z-index:98; top:72px; background:var(--bg2); border-bottom:1px solid var(--border); padding:12px 28px`)

**Nav-Panels:**
- `#panel-sqeg` (Standard aktiv)
- `#panel-settings`

JS-Routing: `data-tool="sqeg"` → `document.getElementById('panel-sqeg').classList.add('active')`

---

## 3. SQEG Analyzer (in `app/index.php`)

### 3a. Eingabe-Bereich (`#panel-sqeg`)

```html
<div class="input-card">
  <!-- Header mit Icon, Titel, URL-Anzeige, Start-Button -->
  <!-- Optionale Kontext-Felder (ausgeklappt via Toggle): Keyword, Conversion-Ziel, Zielgruppe -->
</div>
```

**`input-card` CSS:**
```css
.input-card { 
  background:var(--bg2); border:1px solid var(--border); 
  border-radius:var(--radius-lg); padding:24px; margin-bottom:20px; 
  box-shadow:0 1px 3px rgba(26,25,23,.06); 
}
.btn-start { 
  height:44px; padding:0 24px; background:var(--accent); color:#fff; 
  border:none; border-radius:var(--radius); font-size:14px; font-weight:600; 
  cursor:pointer; transition:all .2s; font-family:'DM Sans',sans-serif; 
  box-shadow:0 2px 8px rgba(67,56,202,.25); 
}
.btn-start:hover { background:var(--accent2); transform:translateY(-1px); }
```

### 3b. Fortschritts-Anzeige

```html
<div id="progress-section" style="display:none">
  <!-- Fortschritts-Label + Prozentzahl -->
  <!-- Fortschrittsbalken (animated gradient) -->
  <!-- Loader-Dots Animation -->
  <!-- Status-Meldung -->
  <!-- Log-Box (font: DM Mono, height:180px, overflow:auto) -->
</div>
```

### 3c. Ergebnis-Bereich

```html
<div id="results-section" style="display:none">

  <!-- ZONE: SCHNELLÜBERBLICK -->
  <!-- Score-Badge (green/amber/red), YMYL-Badge, Re-Analyse, Export -->
  <!-- Stat-Grid: Bestanden / Verbesserbar / Fehlerhaft / PQ-Erweitert -->
  <!-- SQEG-Skala: Lowest › Low › Medium › Medium+ › High › Highest -->
  <!-- Needs-Met Block (e8) -->
  <!-- Prioritäten-Matrix (Quadranten: Sofort / Quick Wins / Mittelfristig) -->

  <!-- ZONE: DETAILANALYSE -->
  <!-- Filter-Buttons: Alle / Bestanden / Verbesserbar / Fehlerhaft / PQ-Erweitert -->
  <!-- Tabelle mit c1–c29 -->
  <!-- Manuelle PQ-Erweitert Karten e1–e7 -->

</div>
```

**Score-Badge-Farben:**
- ≥ 75% bestanden → `.score-badge.green`
- ≥ 50% → `.score-badge.amber`
- < 50% → `.score-badge.red`

---

## 4. SQEG KI-Analyse-Logik (JavaScript)

### Analyse-Flow

```
1. URL via fetch.php fetchen (oder HTML aus Textarea nehmen)
2. DataForSEO PageSpeed + SERP (optional, graceful fallback)
3. 15 parallele Mini-Calls an api.php (je 2 Kriterien = 30 Kriterien, davon 29 PQ + 1 optional)
4. 1 Call für e1–e7 (PQ-Erweitert)
5. 1 Call für e8 / Needs Met (nur wenn Keyword vorhanden)
6. YMYL-Klassifizierung (separater kleiner Claude-Call)
7. Ergebnisse zusammenführen, Gewichtungen berechnen, rendern
```

**fetch.php** (eigene Datei, ruft URL ab und gibt HTML zurück):
```php
<?php
header('Content-Type: application/json; charset=utf-8');
$url = $_GET['url'] ?? '';
if (!filter_var($url, FILTER_VALIDATE_URL)) { echo json_encode(['error'=>'Invalid URL']); exit; }
$ctx = stream_context_create(['http'=>['timeout'=>20,'user_agent'=>'Mozilla/5.0']]);
$html = @file_get_contents($url, false, $ctx);
if ($html === false) { echo json_encode(['error'=>'Fetch failed']); exit; }
echo json_encode(['html'=>$html,'length'=>strlen($html)]);
```

### SQEG-Kriterien (vollständige Liste für den AI-Prompt)

**Kategorie A: Seitenzweck**
- c1 · Klar erkennbarer Seitenzweck (Beneficial Purpose) · Sek. 2.2
- c2 · MC klar identifizierbar und vom Rest abgegrenzt · Sek. 2.4.1
- c3 · YMYL-Einordnung & erhöhte Qualitätsstandards · Sek. 2.3
- c4 · Seitentyp-angemessene Qualitätserwartung erfüllt · Sek. 3.1

**Kategorie B: E-E-A-T**
- c5 · Experience – Ersthand-Erfahrung des Content-Creators · Sek. 3.4
- c6 · Expertise – Fachkompetenz (formal & informal) · Sek. 3.4
- c7 · Authoritativeness – Autorität der Website im Themenfeld · Sek. 3.4
- c8 · Trust – Gesamtvertrauenswürdigkeit (wichtigstes Element) · Sek. 3.4
- c9 · YMYL: Experience vs. Expertise korrekt eingesetzt · Sek. 3.4.1

**Kategorie C: MC-Qualität**
- c10 · Effort – Menschlicher Aufwand bei Content-Erstellung · Sek. 3.2
- c11 · Originality – Einzigartiger, nicht-kopierbarer Content · Sek. 3.2
- c12 · Talent & Skill – Handwerkliche Qualität der Ausführung · Sek. 3.2
- c13 · Accuracy – Faktische Korrektheit & Expertenkonsens · Sek. 3.2
- c14 · Kein Filler-Content – MC steht prominent vorne · Sek. 5.2.2
- c15 · Kein Scaled/AI Content Abuse · Sek. 4.6.5

**Kategorie D: Reputation & Transparenz**
- c16 · Reputation der Website · Sek. 3.3.1
- c17 · Reputation des Content-Creators erkennbar · Sek. 3.3.4
- c18 · Verantwortlichkeit – Wer steckt hinter der Seite? · Sek. 2.5.2
- c19 · About-Seite / Impressum / Rechtliche Angaben · Sek. 2.5.3 + 4.5.1
- c20 · Kontakt & Kundenservice · Sek. 2.5.3 + 5.5
- c21 · Kein offensichtlicher Interessenkonflikt ohne Offenlegung · Sek. 3.4

**Kategorie E: Lowest Quality Signals**
- c22 · Kein täuschendes Design / täuschender Seitenzweck · Sek. 4.5.3
- c23 · MC nicht durch Ads/SC verdeckt oder obstruiert · Sek. 4.5.4
- c24 · Kein Verdacht auf Scam oder schädliches Verhalten · Sek. 4.5.5

**Kategorie F: UX & SC**
- c25 · Supplementary Content unterstützt Seitenzweck sinnvoll · Sek. 2.4.2
- c26 · Seitentitel beschreibend und nicht irreführend · Sek. 3.1
- c27 · Mobile-Nutzbarkeit & Page Experience · Sek. 7.0

**Kategorie G: Freshness**
- c28 · Aktualität: Freshness für zeitkritische Themen · Sek. 18.0
- c29 · Content-Vollständigkeit & Tiefe · Sek. 4.1

**Kategorie H: PQ-Erweitert (e1–e7, separater Call)**
- e1 · Externe Reputation der Website · Sek. 3.3.1–3.3.4
- e2 · Deceptive Design & Creator-Verifikation · Sek. 4.5.3
- e3 · Harmful to Self or Others · Sek. 4.2
- e4 · Harmful to Specified Groups · Sek. 4.3
- e5 · Harmfully Misleading Information · Sek. 4.4
- e6 · Interessenkonflikt & Transparenz · Sek. 3.4
- e7 · Seitentyp-Sonderregeln · Sek. 9.0–9.3

**e8 / Needs Met (nur mit Keyword)**
- e8 · Needs Met – Suchanfrage erfüllt · Sek. 13.0  
  Skala: `FullyM` (100) | `HighlyM` (80) | `ModeratelyM` (55) | `SlightlyM` (30) | `FailsM` (10)

### KI-System-Prompt (Basis)

```
Du bist ein Google Search Quality Evaluator. Bewerte diese Seite anhand der SQEG November 2025.

Eingabedaten:
URL: {url}
HTML-Ausschnitt (erste 12.000 Zeichen): {html_snippet}
PageSpeed Mobile Score: {pagespeed}
Seitentyp: {page_type}
{ymyl_hint}

Antworte AUSSCHLIESSLICH als JSON-Array. Kein Text davor oder danach.
Format je Objekt:
{"id":"c1","category":"A: Seitenzweck","criterion":"Name","sqeg_ref":"Sek. X.X","status":"green|amber|red","finding":"Beleg: [Signal aus HTML] | Regel: [WENN-Bedingung] | Bewertung: [Urteil]","improvement":"[konkreter Verbesserungsvorschlag, leer wenn green]","confidence":80}

Zu bewertende Kriterien in diesem Call:
{criteria_list}
```

### Kriterien-Aufteilung (15 Calls á 2)
```
Call 1:  c1,c2    Call 6:  c11,c12   Call 11: c21,c22
Call 2:  c3,c4    Call 7:  c13,c14   Call 12: c23,c24
Call 3:  c5,c6    Call 8:  c15,c16   Call 13: c25,c26
Call 4:  c7,c8    Call 9:  c17,c18   Call 14: c27,c28
Call 5:  c9,c10   Call 10: c19,c20   Call 15: c29 (allein)
```

### YMYL-Klassifizierung (separater Call, vor den 15 Calls)

```
Klassifiziere: "clear_ymyl" | "mixed_ymyl" | "none"
YMYL-Kategorien laut SQEG: Finanzen, Medizin/Gesundheit, Recht, Sicherheit, 
große Kaufentscheidungen, Neuigkeiten/gesellschaftliche Themen, Kinderschutz.
```

YMYL-Badge: `clear_ymyl` → rot "YMYL: Erhöhter Maßstab" | `mixed_ymyl` → amber | `none` → grün "Kein YMYL"

### Gewichtungen (für gewichteten Durchschnitt)

```js
const WEIGHTS = {
  // Gewicht 4 (Kernkriterien Trust)
  c8:4, c24:4, c22:4,
  // Gewicht 3
  c3:3, c5:3, c6:3, c7:3, c9:3, c13:3,
  c18:3, c19:3, c20:3, c21:3, c23:3, e3:3, e4:3, e5:3, e6:3,
  // Gewicht 2 (Standard)
  default: 2,
  // Gewicht 1.5
  c10:1.5, c11:1.5, c12:1.5, c14:1.5, c15:1.5, c28:1.5, c29:1.5
};
// score: green=100, amber=50, red=0
// Gewichteter Durchschnitt → SQEG-Stufe:
// ≥87 → Highest, ≥73 → High, ≥60 → Medium+, ≥47 → Medium, ≥30 → Low, <30 → Lowest
```

### Prioritäten-Matrix (nach Ergebnis)

3 Quadranten:
1. **Sofort angehen** (red, Gewicht ≥ 3): roter Dot, Aufwand-Badge
2. **Quick Wins** (amber, Gewicht ≥ 2 oder red, Gewicht < 3): amber Dot
3. **Mittelfristig** (amber, Gewicht < 2): blauer Dot

---

## 5. `app/api.php`

```php
<?php
set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$settings = [];
$sf = __DIR__ . '/settings.json';
if (file_exists($sf)) $settings = json_decode(file_get_contents($sf), true) ?? [];

$apiKey = getenv('ANTHROPIC_API_KEY') ?: ($settings['anthropic_api_key'] ?? '');
if (empty($apiKey)) {
    http_response_code(503);
    echo json_encode(['error'=>['type'=>'no_key','message'=>'Kein API-Key hinterlegt.']]);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!$body || empty($body['messages'])) {
    http_response_code(400); echo json_encode(['error'=>'messages fehlt']); exit;
}

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $raw,
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

if ($err) { http_response_code(502); echo json_encode(['error'=>$err]); exit; }
http_response_code($httpCode);
echo $response;
```

---

## 6. Settings-Panel (`#panel-settings`)

Einfaches Formular zum Hinterlegen des Anthropic API-Keys:
- Aktueller Key: maskiert angezeigt (erste 10 + `***` + letzte 4 Zeichen)
- Eingabefeld: `type="password"`, Toggle "Anzeigen/Verbergen"
- Speichern via Fetch POST an `settings_save.php` (lege diese Datei an):
  - Validierung: Key muss mit `sk-ant-` beginnen
  - Speichert in `settings.json`
- Login-Passwort ändern: Neues PW + Bestätigung, min. 8 Zeichen, speichert als `password_hash()`

---

## 7. Einstellungs-Datei `app/settings.json` (initial)

```json
{
  "anthropic_api_key": "",
  "login_password_hash": "",
  "ai_model": "claude-sonnet-4-5"
}
```

---

## 8. Technische Anforderungen

- **Kein externes Framework** — reines PHP 8.x, vanilla JavaScript (ES2020), kein npm
- **Kein Build-Step** — Dateien direkt aufrufbar
- **Session-Sicherheit**: `session_regenerate_id(true)` nach Login, CSRF-Token, HttpOnly-Cookie
- **API-Key-Sicherheit**: Key nur serverseitig, nie im JS-Response
- **JS-Fehlerbehandlung**: try/catch für alle API-Calls, Fehlermeldung in roter `.err-box`
- **Claude-Modell**: `claude-sonnet-4-5` mit `max_tokens: 2000` pro Mini-Call
- **Parallelität**: Alle 15 SQEG-Mini-Calls mit `Promise.allSettled()` parallel ausführen
- **Fallback**: Wenn fetch.php fehlschlägt, klare Fehlermeldung + Hinweis auf HTML-Modus
- **Tabelle responsive**: Auf Mobile (< 768px) Sidebar als Top-Nav, Spalten reduzieren

---

## 9. Dateistruktur (Ergebnis)

```
/
├── login.php
├── app/
│   ├── index.php          ← Haupt-App (Sidebar + SQEG + Settings)
│   ├── api.php            ← Claude-Proxy
│   ├── fetch.php          ← HTML-Fetch-Proxy
│   ├── settings_save.php  ← Settings speichern
│   └── settings.json      ← Konfiguration (initial leer)
```

---

## 10. Visuelle Details für den SQEG-Output

**Status-Dot:**
```css
.status-dot { width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; }
.status-dot.green { background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); } /* ✓ */
.status-dot.amber { background:var(--amber-bg); color:var(--amber); border:1px solid var(--amber-border); } /* ◑ */
.status-dot.red   { background:var(--red-bg);   color:var(--red);   border:1px solid var(--red-border);   } /* ✗ */
```

**Kriterium-Zelle:**
```
[Status-Dot]  [Kriteriumnummer + Name fett]
              [Kategorie klein, grau]
              [SQEG-Referenz klein, indigo]
```

**Befund-Zelle (finding):**  
3-teiliger Text: `Beleg: ... | Regel: ... | Bewertung: ...`  
→ Im Render aufsplitten und optisch trennen (Beleg in grauem Badge, Regel kursiv, Bewertung fett)

**Verbesserungsvorschlag:**
```css
.suggest { margin-top:8px; padding:8px 12px; background:rgba(67,56,202,.08); 
  border-left:2px solid var(--accent); border-radius:0 6px 6px 0; font-size:12px; }
```

**SQEG-Skala-Bar** (nach der Score-Badge):
```
SQEG: [Lowest] › [Low] › [Medium] › [Medium+] › [High] › [Highest]
```
Aktive Stufe: `background:var(--accent); color:#fff;`
Inaktive Stufen: transparent, grauer Text.

---

## 11. Stat-Grid

```html
<div class="stat-grid"> <!-- grid-template-columns: repeat(4,1fr) -->
  <div class="stat-box green"><div class="stat-num" id="cnt-g">0</div><div class="stat-lbl">✓ Bestanden</div></div>
  <div class="stat-box amber"><div class="stat-num" id="cnt-a">0</div><div class="stat-lbl">◑ Verbesserungswürdig</div></div>
  <div class="stat-box red">  <div class="stat-num" id="cnt-r">0</div><div class="stat-lbl">✗ Fehlerhaft</div></div>
  <div class="stat-box blue"> <div class="stat-num">7</div>          <div class="stat-lbl">☐ PQ-Erweitert</div></div>
</div>
```

---

## 12. Export-Funktion

Button "↓ HTML-Bericht": Öffnet neues Fenster mit vollständiger Ergebnis-HTML (inline CSS, druckoptimiert).  
Button "⎙ PDF": `window.print()`

---

Generiere jetzt alle Dateien vollständig und sofort ausführbar. Keine Platzhalter, keine "TODO"-Kommentare. Jede Datei muss vollständig funktionieren.
