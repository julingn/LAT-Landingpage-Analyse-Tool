# ROADMAP — LAT Landingpage-Analyse-Tool

**Stand:** 22. Mai 2026 (aktualisiert nach Session 3)  
Dieses Dokument wird nach jeder Arbeitseinheit aktualisiert.

---

## ✅ Erledigt — 22. Mai 2026 (Session 3)

| # | Was | Details |
|---|-----|---------|
| ES-01 | **Executive Summary — KI-Karte nach Analyse** | 3-Spalten-Grid: Gesamtbewertung · Hauptprobleme · Nächste Schritte. Demo-Modus statisch, echter Modus per `callApi()` (~700 Token). CSS-Block `.exec-summary-*`, HTML in `results-section`, JS: `generateExecSummary()`, `parseExecSummary()`, `renderExecSummary()`. Commit `7595e10` |
| ES-02 | **Zwei-Zeilen-Format ✖/→ für Probleme** | Jedes Problem: ✖ Titel (Zeile 1) + → Erklärung (Zeile 2). Parser erkennt ✖/✗/x + → Zeilenpaare. Demo-Daten und System-Prompt auf neues Format. Commit `9b3d947` |
| ES-03 | **Prompt-Regeln verfeinert** | Max. 10–12 Wörter pro Zeile, kein „–" im Satz, konsistenter Schreibstil, explizit keine KPI-Wiederholung. Commit `b7fee8a` |
| ES-04 | **Trust-Fix: Score nicht mehr hardcoded** | Demo-`bewertung` nutzt `calcScore()` statt fester `62` → Zahl immer identisch mit Score-Hero. Commit `0da3405` |
| SI-01 | **Score-Interpretation deterministisch (5 Stufen)** | `getScoreInterpretation(score)` mit exakten Grenzen 0–39/40–59/60–74/75–89/90–100, deutschen Labels + festem Interpretationssatz. Anzeige als Subtitle im Score-Hero unter Level-Badge (`.score-hero-interp`). Label überschreibt KI-generiertes `bewertung`-Feld. Commit `ed2a134` |
| UX-01 | **Eingabemaske Feinschliff (7 Punkte)** | Demo-Button → Card-Header; `card-sub` Geist Mono → Inter; Context-Toggle-Label „Analyse verfeinern"; URL-Placeholder „URL der Landingpage eingeben"; `url-input bg: --bg3→--bg`; Context-Fields mit `border-top`; `.input-card.input-dimmed` während Analyse. Commit `24b1047` |

---

## ✅ Erledigt — 22. Mai 2026 (Session 2)

| # | Was | Details |
|---|-----|---------|
| R-01 | **Rebranding: MVV-Logo + LAT** | `logo_colored.png` in `app/assets/`, Sidebar-Text `L·A·T`, Titel `LAT · SQEG Analyzer` — Commit `ad01cef` |
| B-03 | **Bugfix: Tabellen-Header Spaltenanzahl** | `<thead>` hatte 3 `<th>`, Rows 4 `<td>` → 4. leere `<th>` für Chevron-Spalte ergänzt |
| B-04 | **Bugfix: Skeleton initial sichtbar** | `skeleton-wrap` fehlte `style="display:none"` → ergänzt |
| B-05 | **Bugfix: Skeleton bleibt bei Fehler** | `catch`-Block versteckt `skeleton-wrap` jetzt ebenfalls |
| B-06 | **Bugfix: DM Sans im Modell-Dropdown** | Schrift auf `Inter` korrigiert |
| U-05 | **Skeleton-Animation: Shimmer → Slow Pulse** | `skel-wave` (1.4s Shimmer) → `skel-pulse` (3s Fade, 45–80% Opacity) — weniger ablenkend bei langen Analysen |
| U-06 | **Timer: ETA entfernt** | ETA-Formel war bei Batch-Sprüngen unzuverlässig (Sprünge 1–5 Min.) → nur noch Elapsed-Zeit |
| U-07 | **Progressbar-Verteilung korrigiert** | Prep/Fetch: 0–13%, Mini-Calls: 13–90% (77%), Rendering: 90–100% — gleichmäßige Bewegung über gesamte Laufzeit |

---

## ✅ Erledigt — 21. Mai 2026

| # | Was | Details |
|---|-----|---------|
| F-01 | **Hotfix: `extractPageText` nicht definiert** | Funktion nach dem Refactoring wiederhergestellt (nach `buildPsiBlock`) |
| F-02 | **Hotfix: `TypeError: Failed to fetch`** | 21 parallele Mini-Calls überlasteten den Server → Batches à 5 Requests |
| I-1 | **Schema.org-Parsing** | `buildSchemaBlock(html)`: JSON-LD + Microdata aus `rawHtml` extrahiert → Kontext für Kriterium 6.4 |
| I-3 | **Wortanzahl-Benchmark** | `wordCount` aus `pageText` in `buildCtxBlock` eingebaut → Kontext für 2.5 / 8.2 |
| I-6 | **GSC Branded-Queries** | Branded-Query-Anteil aus `gscData` als Autoritäts-Signal (3.3) in `buildCtxBlock` |
| I-7 | **YMYL als Score-Multiplikator** | `getEffectiveWeight(id)` + `YMYL_ESCALATION`-Tabelle: bei `clear_ymyl` heben sich Gewichte von 2.4, 3.2, 3.5, 4.3, 4.4 auf nächste Stufe |
| U-01 | **Log: Kriterien-Namen statt "Call 1, 2…"** | Log-Zeilen zeigen jetzt `✓ Erkennbarer Seitenzweck · Seitentyp-Klassifikation` |
| U-02 | **GSC-Panel in Ergebnisse** | Top-15-Keywords-Tabelle mit Klicks, Impressionen, CTR, Position (farbkodiert) + Balken-Visualisierung |
| U-03 | **Timer mit Elapsed + ETA** | `formatTime()`, `updateTimer()`, `#progress-timer` neben Progressbar; ETA-Berechnung über `lastPct` |
| U-04 | **UI v2.0 vollständig implementiert & deployed** | Commit `f61c90e` — Details siehe unten |
| D-01 | **LAT-Design-System.md erstellt** | Living document für alle Design-Entscheidungen |

### UI v2.0 — Änderungen im Detail

**Typografie & Farben**
- Font: `Inter` (Body/UI) + `Geist Mono` (Code, URLs, IDs)
- Palette: Slate-basiert — `--bg: #F8FAFC`, `--text: #0F172A`, Indigo-Akzent `#4F46E5`
- Shadow-System (`--shadow-sm` bis `--shadow-lg`), Radius-System (`--radius-sm: 6px` bis `--radius-xl: 16px`)

**Score Hero-Card** (ersetzt altes `score-badge`)
- 64px-Zahl, farbkodiert (grün/amber/rot)
- Eingebettete Progress-Bar + Level-Pill
- Chips: YMYL-Status · Kriterien-Anzahl · Analyse-Dauer

**Kriterien-Tabelle mit Expand-Rows**
- Kompakte Zeile: Status-Dot, ID, Name, Fazit (120 Zeichen)
- Klick öffnet Detail-Block: Beleg · Regel · Bewertung · Verbesserungsvorschlag + Gewicht
- Chevron-Animation

**Skeleton Screens**
- Während der Analyse: animierte Platzhalter für Score-Hero und Stat-Grid

**Sonstiges**
- Top-Bar: Glassmorphismus (`backdrop-filter: blur(12px)`)
- Log-Box: höher (200px), dunklerer Hintergrund
- Progressbar: 6px Höhe, smoothere Transition

---

## 🔜 Nächste Schritte

### Nächste Session — Priorität Hoch

| # | Was | Beschreibung |
|---|-----|--------------|
| N-01 | ~~**UX/UI Checkup**~~ | ✅ Erledigt heute |
| N-02 | **Tooltips für alle Werte** | Alle Messwerte, Score-Chips, Kriterien-IDs und Level-Bezeichnungen sollen einen Hover-Tooltip mit Kurzerklärung bekommen (z.B. „LCP: Largest Contentful Paint — misst die Ladezeit des größten sichtbaren Elements") |

### Demnächst — Priorität Mittel

| # | Was | Beschreibung |
|---|-----|--------------|
| N-03 | **Settings-UI** | Formular zum Ändern von API-Keys (DataForSEO, OpenAI/Anthropic, PageSpeed, GSC Service Account) — aktuell nur via `settings.json` per Datei |
| N-04 | **Passwort ändern** | UI-Seite zum Setzen eines neuen Login-Passworts ohne direkten Serverzugriff |
| N-05 | **GSC Domain-Verwaltung** | Domains zu `gsc_domains.json` direkt aus der App hinzufügen / entfernen |
| N-06 | **I-2: HTTPS-Check** | URL-Prüfung sauber als eigenen Kriterien-Befund `6.5` ausgeben (aktuell implizit vorhanden, aber nicht explizit geloggt) |

### Backlog — Priorität Niedrig / Nice-to-have

| # | Was | Beschreibung |
|---|-----|--------------|
| B-01 | **PDF-Export** | Analyse-Ergebnis als PDF speichern (aktuell nur HTML-Export) |
| B-02 | **Vergleichs-Ansicht** | Zwei URLs gegeneinander analysieren (Side-by-Side) |
| B-03 | **Analyse-Historie** | Vergangene Ergebnisse speichern und zeitlich vergleichen |

---

## Changelog

| Datum | Version | Änderung |
|-------|---------|----------|
| 21.05.2026 | v2.0 | UI-Redesign: Inter/Geist, Slate-Palette, Score-Hero, Expand-Rows, Skeleton — Commit `f61c90e` |
| 21.05.2026 | v1.x | YMYL-Multiplikator, Schema.org, Wortanzahl, GSC Branded-Queries, Batch-Calls, Timer |
| 21.05.2026 | v1.x | Hotfix extractPageText, Hotfix Failed-to-fetch |
