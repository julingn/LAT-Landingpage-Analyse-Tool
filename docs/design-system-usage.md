# Designsystem — Nutzung (verpflichtend)

> **Source of Truth für Tokens:** `Documents/LAT-Design-System.md` (v2.4).
> Diese Datei beschreibt die **verbindliche Nutzung** und wie neue visuelle Muster ergänzt werden.
> Sie dupliziert die Token-Tabellen nicht — dort ist und bleibt die maßgebliche Referenz.

## Grundregel

Das gesamte Tool soll ein **konsistentes Erscheinungsbild** behalten. Bei allen weiteren
Entwicklungen wird immer das vorhandene Designsystem bzw. die vorhandenen UI-Komponenten genutzt.

## Verbindliche Regeln

- **Bestehende UI-Komponenten müssen bevorzugt verwendet werden** (siehe `docs/component-inventory.md`).
- **Neue Komponenten** dürfen nur erstellt werden, wenn keine passende bestehende Komponente existiert.
- Neue Komponenten müssen sich **optisch und technisch** am vorhandenen Designsystem orientieren.
- **Keine neuen UI-Libraries** installieren, wenn bestehende Komponenten ausreichen.
- **Farben, Abstände, Typografie, Schatten, Border-Radien, Icons und Interaktionszustände**
  bleiben konsistent mit dem bestehenden Tool.
- Buttons, Cards, Inputs, Badges, Alerts, Tabs, Accordions und Navigationselemente werden
  aus dem bestehenden System **wiederverwendet**.
- Falls eine neue Komponente nötig ist, wird sie **wiederverwendbar** und **passend zum Design** gebaut.

## Technische Leitplanken (LAT-spezifisch)

- **Nur CSS-Custom-Properties** (`var(--*)`) — keine hardcodierten Hex-Farben im Markup/JS.
- **Kein Inline-Style für Wiederverwendbares** — stattdessen eine CSS-Klasse im `<style>`-Block
  (`app/index.php` Z. 29–846) anlegen und über eine Render-Funktion nutzen.
- **Spacing** folgt dem 8px-Grid (`--s-1` … `--s-10`).
- **Radius/Shadow** über die Tokens (`--radius*`, `--shadow*`), nicht ad hoc.
- **Dark Mode:** Jede neue Farbe/Fläche muss im Light- **und** Dark-Theme (`[data-theme="dark"]`)
  funktionieren. Immer beide Themes prüfen.
- **Fonts:** Inter (UI), Geist Mono (Code/URLs/IDs) — keine weiteren Schriftfamilien einführen.

## Wenn ein neues visuelles Muster eingeführt wird

1. Prüfen, ob ein bestehendes Muster (Card `.needs-met-block`, Badge, Tab `.pv-tabs`,
   Collapsible `.log-wrap`, Modal `.agent-modal` …) wiederverwendet werden kann.
2. Falls neu: mit Tokens umsetzen, in beiden Themes testen.
3. **`docs/component-inventory.md`** um die Komponente/Variante ergänzen.
4. Dieses Dokument (`design-system-usage.md`) um das Muster ergänzen, falls es ein
   grundsätzlich neues Interaktions-/Layout-Prinzip ist.
5. In `docs/roadmap.md` unter „Abgeschlossen" vermerken.

## Interaktionszustände (Pflicht bei neuen interaktiven Elementen)

Jede neue interaktive Komponente definiert: **Default, Hover, Active, Focus, Disabled** —
und, wo zutreffend, **Loading** und **Error**. Referenz: Button-States in
`Documents/LAT-Design-System.md` §7.1.
