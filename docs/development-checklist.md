# Development Checklist — LAT

> Vor und nach **jeder** Änderung durchgehen. Adaptiert an die Projekt-Realität
> (PHP + Vanilla JS/CSS, kein Build-Step, kein TypeScript).

## Vor jeder neuen Entwicklung prüfen

- [ ] Gibt es bereits eine passende UI-Komponente? (→ `docs/component-inventory.md`)
- [ ] Kann eine bestehende Komponente erweitert werden, statt eine neue zu bauen?
- [ ] Passt die Änderung zum bestehenden Designsystem? (Tokens, Themes, Spacing)
- [ ] Sind neue Dependencies wirklich notwendig? (Standard: nein)
- [ ] Bleiben bestehende LAT-Funktionen unberührt?
- [ ] Muss ein JSON-(Prompt-)Schema oder eine Render-Funktion angepasst werden?
- [ ] Muss ein Proxy (`app/proxies/*`) angepasst werden? (Auth → CSRF → `session_write_close()` → cURL → JSON)
- [ ] Muss ein KI-Prompt angepasst werden?
- [ ] Entsteht eine neue Idee, die nicht Teil des Auftrags ist? → als To-do in `docs/roadmap.md`, nicht ungefragt umsetzen.

## Nach jeder abgeschlossenen Entwicklung prüfen

- [ ] Kein JS-Syntaxfehler — `node --check` auf geänderten/kopierten JS-Code.
- [ ] Kein PHP-Fehler — `php -l` auf geänderte PHP-Dateien.
- [ ] Datei-Encoding sauber — **kein BOM, kein CRLF** in `app/index.php` und PHP-Dateien.
- [ ] UI ist konsistent mit dem Designsystem (Light **und** Dark Mode getestet).
- [ ] Neue Komponenten/Varianten dokumentiert → `docs/component-inventory.md`.
- [ ] Neues visuelles Muster dokumentiert → `docs/design-system-usage.md`.
- [ ] Roadmap aktualisiert → `docs/roadmap.md` (Umgesetztes nach „Abgeschlossen").
- [ ] Gelöste Probleme dokumentiert → `docs/known-issues-and-solutions.md`.
- [ ] Keine Secrets committet (API-Keys, Tokens, Passwörter).
- [ ] README / Projektleitlinien weiterhin aktuell.
- [ ] Smoke-Test durchgeführt (lokal via `php -S 0.0.0.0:8080 -t . router.php` **oder** Railway-Preview-Deploy).

## Neues Modul hinzufügen (LAT-spezifisch, alle 4 Stellen!)

- [ ] Sidebar-Nav: `<button class="nav-item" data-view="X">` + `<span id="nav-score-X">`
- [ ] Modul-Kachel: `<div class="module-card" id="mc-X">` mit `mc-X-score`/`-bar`/`-label`
- [ ] View-Panel: `<div class="view-panel" id="view-X">` **innerhalb** `<div class="content-wrap">`
- [ ] JS: `VIEW_META`-Eintrag + Reset in `startAnalysis()` + Reset in `startDemo()` + Aktivierung in `renderResults()`
