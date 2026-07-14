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
Auf der Entwicklungsmaschine ist kein PHP installiert (`php` nicht im PATH).
## Lösung
Test über Railway-Preview-Deploy statt lokal. Bei kritischen Änderungen lokal PHP installieren
oder Preview-Deploy nutzen; Railway hält den vorherigen Deploy für 1-Klick-Rollback bereit.
## Betroffene Dateien
Entwicklungsumgebung (kein Repo-Code).
## Datum
2026-07-14
## Status
workaround
