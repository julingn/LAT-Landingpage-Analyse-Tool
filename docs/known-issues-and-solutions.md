# Known Issues & Solutions — LAT

> **Regel:** Sobald bei der Entwicklung ein Bug, Integrations-, Build-, API-, Validierungs-,
> Prompt-, UI- oder Designsystem-Problem auftritt **und gelöst wird**, wird es hier dokumentiert.
> Neuste Einträge oben. Historische Kurzfassungen stehen zusätzlich in `Documents/MUST_READ.md`.

**Eintrags-Schema:**

```
## Problem
Kurze Beschreibung.
## Ursache
Warum ist es aufgetreten?
## Lösung
Konkret umgesetzte Lösung.
## Betroffene Dateien
Welche Dateien/Module.
## Datum
YYYY-MM-DD.
## Status
gelöst | workaround | offen | erneut prüfen
```

---

## Problem
Im Modul „Wissensabdeckung" zeigte nach der Analyse nur der Chancen-Tab Inhalte; Übersicht,
Themenabdeckung, Wettbewerber und Graph blieben (nahezu) leer.
## Ursache
Zwei Ursachen: (a) die KI (`analyze`) lieferte teils Opportunities, aber eine leere `coverage`
(die anderen Tabs speisen sich aus `coverage`/Extraktionen); (b) bei JS-gerenderten Seiten liefert
der einfache `fetch.php`-Abruf wenig Text → kaum extrahierte Entitäten.
## Lösung
1. **Coverage-Fallback** in `app/proxies/knowledge.php` (`knowBuildCoverageFallback()`): ist die
   KI-`coverage` leer, wird sie deterministisch aus den Extraktionen synthetisiert (eigene vs. beste
   Wettbewerber-Abdeckung je Element) → Themenabdeckung/Wettbewerber/Graph bekommen Daten.
2. **Resiliente Darstellung**: `kgRenderAll()` rendert jeden Tab in eigenem try/catch — eine
   fehlerhafte Ansicht leert nicht mehr die anderen, sondern zeigt einen Hinweis in ihrem Tab.
3. **Diagnose-Zeile** in der Übersicht (Anzahl extrahierter Themen eigen/Wettbewerber, Coverage-
   Einträge, Chancen) + Amber-Hinweis, wenn kaum Themen extrahiert wurden (JS-Rendering).
4. Analyze-Prompt geschärft: `coverage` immer aus den Extraktionen füllen.
5. **Serverseitiges Rendern (Puppeteer)**: Liefert `fetch.php` zu wenig Text (< 400 Zeichen, typisch
   bei JS-gerenderten Seiten wie mvv.de), rendert `knowRenderText()` die Seite mit demselben
   Extraktor wie der Content Finder (`contentfinder_extract.mjs`) serverseitig und extrahiert daraus.
   Das Frontend ruft `extract` auch dann mit URL auf, wenn der direkte Abruf fehlschlägt. Antwort-Flag
   `rendered` zeigt es im Teilergebnis an. (Nur auf Railway wirksam — lokal kein Node/Chromium.)
## Betroffene Dateien
`app/proxies/knowledge.php`, `app/index.php`, `app/prompts/knowledge_analyze.php`.
## Datum
2026-09-09
## Status
gelöst

---

## Problem
JS-Änderungen im Monolithen (`app/index.php`) sollen wie vorgeschrieben mit `node --check`
geprüft werden, aber `node` ist auf der Entwicklungsmaschine nicht im PATH verfügbar.
## Ursache
Node/Puppeteer läuft in Produktion im Container; lokal ist keine Node-Installation vorhanden.
## Lösung
JS-Block aus dem `<script>` in eine temporäre `.js`-Datei extrahieren und die Syntax über den
**JS-Sprachserver von VS Code** prüfen (Diagnostics/„Get Errors") — funktional äquivalent zu
`node --check` für Syntaxfehler. Zusätzlich Smoke-Test via lokalem PHP-Server
(`php -S 127.0.0.1:8099 -t . router.php`) + Browser-Login (Default-Passwort) + View öffnen:
Empty-/Fehler-/Validierungszustände und Light-/Dark-Mode ohne Konsolenfehler verifizieren.
Temporäre `.js`-Datei danach löschen (nicht committen).
## Betroffene Dateien
Entwicklungsumgebung / QS-Prozess (kein Repo-Code).
## Datum
2026-09-09
## Status
gelöst

---

## Problem
Nach dem Speichern von `app/index.php` mit einem externen Editor schlägt `session_start()`
mit „headers already sent" fehl.
## Ursache
UTF-8 BOM (Bytes `EF BB BF`) am Dateianfang — PHP gibt das BOM als Output aus, bevor Header gesetzt werden.
## Lösung
BOM entfernen; für PowerShell-Writes ausschließlich `[System.Text.UTF8Encoding]::new($false)`
verwenden. Nach jedem Schreibvorgang prüfen: BOM- und CR-Count müssen 0 sein. Bevorzugt
`replace_string_in_file` statt PowerShell-Writes (ändert Encoding nicht).
## Betroffene Dateien
`app/index.php` (und generell alle PHP-Dateien).
## Datum
2026-07-14 (dokumentiert)
## Status
gelöst

---

## Problem
Bei parallelen Batch-Calls liefern Proxies 401 (Nicht autorisiert).
## Ursache
PHP File-based Session-Locking: solange eine Session offen ist, blockieren nachfolgende Requests.
## Lösung
In **jedem** Proxy direkt nach dem Auth-Check `session_write_close()` aufrufen.
## Betroffene Dateien
`app/proxies/*.php`.
## Datum
2026-07-14 (dokumentiert)
## Status
gelöst

---

## Problem
Demo/Analyse „tut nichts", keine Fehlermeldung — der gesamte `<script>`-Block ist tot.
## Ursache
JavaScript-SyntaxError: überschüssiges `}` nach einer Funktion **oder** doppelt deklariertes
`const X` in derselben Funktion (z. B. durch PowerShell-Writes duplizierte Blöcke).
## Lösung
Stray `}` bzw. Duplikat-Block entfernen. Nach jeder JS-Änderung `node --check` auf den
ausgelagerten/kopierten JS-Code ausführen. Beim Löschen von Duplikat-Blöcken Grenzen prüfen,
damit keine echten Funktionen mitgelöscht werden.
## Betroffene Dateien
`app/index.php` (`<script>`-Block). Referenz-Commits: `4da0d25`, `pvWidgetConfigHtml`-Fix.
## Datum
2026-07-14 (dokumentiert)
## Status
gelöst

---

## Problem
Doppelte `</div>` im HTML bricht `content-wrap` — nachfolgende Views rendern full-width.
## Ursache
Ein überschüssiges schließendes `</div>` schließt `content-wrap` zu früh.
## Lösung
Überschüssiges `</div>` entfernen; sicherstellen, dass alle `.view-panel` **innerhalb**
`<div class="content-wrap">` liegen.
## Betroffene Dateien
`app/index.php`. Referenz-Commit: `6eafea2`.
## Datum
2026-07-14 (dokumentiert)
## Status
gelöst

---

## Problem
Lokaler Smoke-Test von PHP nicht möglich.
## Ursache
Auf der Entwicklungsmaschine war kein PHP installiert (`php` nicht im PATH).
## Lösung
PHP 8.3 lokal installiert (`winget install PHP.PHP.8.3`) → passend zur Production (PHP 8.3 Alpine).
Smoke-Test-Harness: `php -l` für Syntax + `php -S 127.0.0.1:8099 -t . router.php` + Browser-Login
(Default-Passwort) + Demo-Modus validiert den kompletten JS-Block ohne externe APIs.
Hinweis: Die winget-PHP-Build hat **curl nicht aktiviert** → echte API-Analysen lokal nur mit
aktivierter curl-Extension; Demo-Modus genügt für JS-Integritätstests.
## Betroffene Dateien
Entwicklungsumgebung (kein Repo-Code).
## Datum
2026-07-14
## Status
gelöst
