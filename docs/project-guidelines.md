# Projektleitlinien — LAT Landingpage-Analyse-Tool

> **Zweck:** Verbindliche Regeln für jede Weiterentwicklung. Ziel ist ein konsistentes,
> wartbares Tool mit einheitlichem Erscheinungsbild und nachvollziehbarer Dokumentation.
> Diese Datei ist der zentrale Einstieg in die Entwicklungs-Governance.

## Projekt-Realität (wichtig für alle Regeln)

- **Stack:** PHP 8.3 (CLI, kein Framework, **kein Build-Step**), Vanilla JS + CSS, Node/Puppeteer für Screenshots.
- **Kein TypeScript, kein npm-Build.** Regeln, die in generischen Vorlagen „TypeScript-Fehler"
  nennen, gelten hier als: **keine JS-Syntaxfehler** (siehe Checkliste) und **kein `php -l`-Fehler**.
- **Kein separates Komponentenverzeichnis.** UI-Komponenten existieren als **CSS-Klassen**
  (`app/index.php`, `<style>`-Block Z. 29–846) und **JS-Render-Funktionen** (Z. 1993–6313).
  Das Component Inventory (`docs/component-inventory.md`) ist deshalb die maßgebliche Übersicht.

## Dokumentations-Landkarte

| Datei | Rolle |
|---|---|
| `docs/project-guidelines.md` | **Diese Datei** — zentrale Regeln |
| `docs/design-system-usage.md` | Wie das Designsystem verpflichtend genutzt wird |
| `docs/component-inventory.md` | Inventar aller wiederverwendbaren UI-Komponenten |
| `docs/known-issues-and-solutions.md` | Problem/Ursache/Lösung-Log |
| `docs/roadmap.md` | Lebende Roadmap (Abgeschlossen / In Arbeit / Geplant / Backlog) |
| `docs/development-checklist.md` | Checkliste vor/nach jeder Änderung |
| `Documents/LAT-Design-System.md` | **Source of Truth** für Design-Tokens (Farben, Typo, Spacing, Shadows) |
| `Documents/MUST_READ.md` | Betriebswissen: API-Regeln, Proxy-Muster, Daten-Flow, Bug-Historie |
| `Documents/ROADMAP.md` | Historisches Feature-Roadmap-Archiv (Detailhistorie) |
| `Documents/criteria-matrix.md` | SQEG-Kriterienkatalog |

## Die 16 Regeln

1. **Designsystem verwenden.** Farben, Abstände, Typografie, Schatten, Border-Radien, Icons
   und Interaktionszustände immer aus dem bestehenden System (`Documents/LAT-Design-System.md`).
   Nur `var(--*)` — **keine hardcodierten Farben**.
2. **Bestehende Komponenten bevorzugen.** Vor jedem neuen Element prüfen, ob eine passende
   Komponente in `docs/component-inventory.md` existiert oder erweitert werden kann.
3. **Designsystem bei neuen Elementen automatisch aktualisieren.** Neue Komponente/Variante/Muster
   → Doku im selben Arbeitsschritt ergänzen (Inventory + ggf. Design-System-Usage).
4. **Neue Komponenten in `docs/component-inventory.md` dokumentieren** (Name, Zweck, Varianten,
   Props/Optionen, Verwendung, Hinweise, Status).
5. **Keine unnötigen Dependencies.** Keine neuen UI-Libraries installieren, wenn bestehende
   Klassen/Muster ausreichen. Das Projekt ist bewusst dependency-arm (kein Build-Step).
6. **Probleme und Lösungen dokumentieren** in `docs/known-issues-and-solutions.md`.
7. **Roadmap nach jedem abgeschlossenen Schritt aktualisieren** (`docs/roadmap.md`).
8. **Neue Ideen als To-do dokumentieren** — nicht ungefragt umsetzen, sondern in
   „Geplant / To-do" oder „Später / Backlog" festhalten.
9. **Bestehende LAT-Funktionen nicht ohne Grund verändern.** Änderungen minimal und nachvollziehbar.
10. **Neue Funktionen modular und wiederverwendbar entwickeln** (Render-Funktion + CSS-Klasse
    statt Inline-Styles; Proxy-Muster einhalten).
11. **Keine JS-/PHP-Syntaxfehler.** JS via `node --check`, PHP via `php -l` prüfen (kein TS im Projekt).
12. **Output-Schemas und Validatoren bei Änderungen aktualisieren.** JSON-Prompt-Schemas der
    KI-Proxies (`app/proxies/localpv.php`, `localpvrefine.php`, `localpvconvert.php`, `api.php`)
    und die zugehörigen JS-Render-Funktionen konsistent halten.
13. **Keine Secrets ins Repository.** API-Keys nie committen (siehe `.gitignore`).
14. **Environment Variables** nur über `.env` (lokal, ge-gitignored), `.env.example` (Vorlage)
    und Railway-Dashboard dokumentieren. Datenquellen-Keys laufen **ausschließlich** über ENV
    (Regel siehe `Documents/MUST_READ.md`). Hinweis: Das Projekt nutzt Root-`.env`, **nicht** `.env.local`.
15. **Neue UI-Elemente nur, wenn keine passende bestehende Komponente existiert.**
16. **Neue visuelle Muster nur einführen, wenn sie dokumentiert und wiederverwendbar sind.**

## Entwicklungsablauf (Kurzform)

**Vor** einer Änderung: `docs/development-checklist.md` durchgehen (bestehende Komponente? Passt
zum Designsystem? Dependency nötig? LAT-Funktion betroffen? Schema/Proxy/Prompt betroffen?).

**Während** der Änderung: bestehende Komponenten wiederverwenden; neue Muster sofort dokumentieren;
neue, nicht umgesetzte Ideen als To-do in `docs/roadmap.md`.

**Nach** der Änderung: JS/PHP-Lint grün; Doku aktualisiert (Inventory/Usage/Roadmap);
gelöste Probleme in `known-issues-and-solutions.md`; keine Secrets committet.

## Governance für neue UI-Elemente

Bevor ein neues UI-Element entsteht, diese 7 Fragen beantworten:

1. Gibt es bereits eine passende Komponente im Inventory?
2. Kann eine bestehende Komponente erweitert werden?
3. Ist wirklich eine neue Komponente notwendig?
4. Passt das neue Element visuell zum bestehenden Tool?
5. Muss die Designsystem-Dokumentation aktualisiert werden?
6. Muss die Komponente als wiederverwendbar dokumentiert werden?
7. Müssen Beispiele, Varianten oder Usage-Hinweise ergänzt werden?

Ist eine neue Komponente nötig, muss sie: konsistent mit dem Designsystem umgesetzt,
wiederverwendbar gebaut, klar benannt, im Inventory dokumentiert (inkl. Varianten +
Anwendungsfällen) und — falls relevant — in der Roadmap als abgeschlossen vermerkt werden.
