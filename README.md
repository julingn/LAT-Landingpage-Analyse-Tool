# LAT – Landingpage-Analyse-Tool

Tool zur vollständigen Analyse einer einzelnen Landingpage/URL nach festgelegten
Qualitätsmetriken (u. a. Google SQEG / E-E-A-T, Technical SEO, Performance, GEO/AEO,
UX/CRO, Keyword-Fit) inkl. zweier Standalone-Werkzeuge (Local-PV-Generator, Content-Finder).

## Stack

PHP 8.3 (CLI, Alpine, kein Framework) · Vanilla JS/CSS · Headless Chromium (Screenshots) ·
Node.js (Puppeteer). Deployment über Railway (`git push origin main` → Auto-Deploy).

## Lokal starten

```bash
cp .env.example .env      # Credentials eintragen (siehe .env.example)
php -S 0.0.0.0:8080 -t . router.php
```

Aufruf im Browser: `http://localhost:8080/` → Login (`login.php`).

## Weiterführende Dokumentation

Das vollständige Betriebs- und Entwicklerwissen (API-Key-Regeln, Proxy-Muster,
View-Struktur, Daten-Flow, bekannte Bugs & Fixes) liegt in
**[Documents/MUST_READ.md](Documents/MUST_READ.md)** — vor jeder Code-Änderung lesen.

### Entwicklungs-Governance (verbindlich)

Vor jeder Weiterentwicklung gelten die Projektleitlinien und die Designsystem-Regeln:

- [docs/project-guidelines.md](docs/project-guidelines.md) – zentrale Entwicklungsregeln
- [docs/design-system-usage.md](docs/design-system-usage.md) – verpflichtende Designsystem-Nutzung
- [docs/component-inventory.md](docs/component-inventory.md) – wiederverwendbare UI-Komponenten
- [docs/development-checklist.md](docs/development-checklist.md) – Checkliste vor/nach Änderungen
- [docs/known-issues-and-solutions.md](docs/known-issues-and-solutions.md) – Problem/Lösung-Log
- [docs/roadmap.md](docs/roadmap.md) – aktive Roadmap (Abgeschlossen / In Arbeit / Geplant / Backlog)

Weitere Dokumente:

- [Documents/ROADMAP.md](Documents/ROADMAP.md) – Feature-Roadmap & Umsetzungsstand
- [Documents/LAT-Design-System.md](Documents/LAT-Design-System.md) – Farben, Typografie, CSS-Tokens
- [Documents/criteria-matrix.md](Documents/criteria-matrix.md) – SQEG-Kriterienkatalog

