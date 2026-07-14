# Roadmap — LAT (lebende Übersicht)

> **Aktive Roadmap** für Projektstand & nächste Schritte. Die **historische Detailhistorie**
> aller bereits umgesetzten Features liegt in `Documents/ROADMAP.md` (Archiv).
>
> **Regel:** Nach jedem abgeschlossenen Schritt aktualisieren. Umgesetztes wandert von
> „Geplant / To-do" oder „In Arbeit" nach „Abgeschlossen". Neue Ideen gehen nicht verloren,
> sondern werden als „Geplant / To-do" oder „Später / Backlog" festgehalten. Neue
> Designsystem-Komponenten/Muster werden hier ebenfalls vermerkt.

## Abgeschlossen

| Datum | Titel | Beschreibung | Bereich | Ref |
|---|---|---|---|---|
| 2026-07-14 | Agent-Registry Schritt 2 | `ymyl` + `execSummary` in `AGENTS`-Registry aufgenommen, Call-Sites auf `getPrompt()` umgestellt (Verhalten unverändert, Smoke-getestet) | KI/index.php | — |
| 2026-07-14 | Agent-Registry Schritt 3 | Zentrale „KI-Agenten"-Verwaltung (alle registrierten Agenten editierbar/persistent); 3-Ebenen-Verdrahtung synchronisiert | KI/index.php + settings_save.php | `947c45c` |
| 2026-07-14 | KI-Agenten als eigener View | „KI-Agenten" aus den Einstellungen in einen eigenen Sidebar-Punkt unter „System" verschoben (`#view-agents`, `data-view="agents"`) | KI/index.php | — |
| 2026-07-14 | PV-Generator-Agenten (L1–L3) | Prompts nach `app/prompts/` ausgelagert (Single Source); Proxies lesen Override aus `settings.json`; `pv`/`pvrefine`/`pvconvert` in Registry & im Tool editierbar | KI/PV/index.php + 3 Proxies | — |
| 2026-07-14 | Governance-Dokumentation | `docs/`-Ordner mit Projektleitlinien, Designsystem-Nutzung, Component Inventory, Known-Issues, Roadmap, Checklist | Doku/Governance | — |
| 2026-07-14 | Phase B — Doku-Abgleich | MUST_READ (Zeilenzahl/Anker), ROADMAP (Status 2.2 / 7.4–7.6) mit Realität abgeglichen | Doku | `48488b2` |
| 2026-07-14 | Phase A — Repo-Hygiene | Legacy-Tool, Junk-Screenshots, redundante `.env.example`, leeres `modules/` entfernt; großes PDF untracked; README als schlanker Einstieg | Repo/Struktur | `9c09638` |
| 2026-07-14 | PV 7.4/7.5/7.6 | PLZ→Stadt-Auflösung, Conversion auf Rohfassung, Placement-Map-Tab entfernt | PV-Generator | `72dc7f0` |
| 2026-07-10 | Content Finder | Standalone-Tool (Puppeteer, OCR, Synonyme, BFS-Crawl) | Tools | `9997e1c` |

> Ältere Einträge: siehe `Documents/ROADMAP.md`.

## In Arbeit

| Titel | Ziel | Status | Offene Punkte |
|---|---|---|---|
| Agent-Registry vereinheitlichen | Alle 9 KI-Prompts als konfigurierbare Agenten | SQEG + PV editierbar (6 Agenten registriert) | Offen: UX-Vision + Content-Finder (Synonyme/OCR) nach `app/prompts/` + Override-Read; optional `runAgent()` |

## Geplant / To-do

### 🎯 Fokus morgen (15.07.2026) — PV Generator: Echtdaten-Integration fertigstellen

**Ziel:** Der PV Generator soll auch **standalone** (ohne vorherige URL-Analyse) mit echten
Marktdaten arbeiten. Feste Domain für alle domainbezogenen Abfragen: **`https://www.mvv.de`**
(das Tool wird ausschließlich für mvv.de genutzt).

**Ausgangslage (Audit 14.07.2026):**
- ✅ DWD (Globalstrahlung/Sonnenstunden) wird pro Stadt frisch geholt und fließt in die Generierung.
- ⚠️ DataForSEO-Suchvolumen wird von `pvSuggestKeywords()` bereits standalone geholt, landet aber
  **nur in den Vorschlags-Pills**, nicht in der Generierung.
- ❌ GSC/Sistrix-Kontext kommt nur aus `gscData`/`sistrixData` einer vorherigen URL-Analyse → standalone leer.

**Aufgaben:**
1. **DataForSEO-Suchvolumen in die Generierung einspeisen** (high) — beim Generieren Ortskeywords +
   Suchvolumen/Wettbewerb als `dataforseoContext` mitschicken; wenn keine Vorschläge geklickt wurden,
   `keyword_volume` automatisch holen. Dateien: `app/index.php` (`pvGenerate()`), `app/proxies/localpv.php` (Kontext nutzen).
2. **Prompt nutzt die Daten aktiv** (high) — `app/prompts/pv.php`: Keyword-Priorisierung, H1/Meta und
   Sections am realen Suchvolumen ausrichten (kein erfundenes Volumen).
3. **Transparenz „Datengrundlagen"-Tab** (medium) — anzeigen, welche echten Quellen tatsächlich verwendet
   wurden (DWD/DataForSEO/GSC/Sistrix), inkl. „nicht verfügbar".
4. **Sistrix/GSC standalone für `www.mvv.de`** (medium) — da das Tool ausschließlich für **mvv.de** genutzt
   wird: Sichtbarkeit/Ranking-Keywords (Sistrix) + GSC-Daten direkt für `https://www.mvv.de` abrufen
   (feste Domain, keine URL-Analyse nötig). `pvGenerate()` ruft `sistrix.php`/`gsc.php` mit fixer Domain und
   füttert `sistrixContext`/`gscContext`.

**Einschränkung:** Nicht lokal testbar (kein `curl` / keine API-Keys lokal). Struktur lokal via
`php -l` / JS-Parse / HTML validieren; echte Datenprüfung am Railway-Deploy mit konfigurierten Credentials.

---

| Idee / Aufgabe | Warum relevant? | Priorität | Bereich | Möglicher nächster Schritt |
|---|---|---|---|---|
| Phase C — Monolith entflechten | `app/index.php` (~6060 Z.) ist schwer wartbar; CSS/JS nicht cachebar | high | Struktur/Performance | CSS (Z. 29–846) → `app/assets/lat.css`, JS (Z. 1993–6313) → `app/assets/lat.js`, `?v=<hash>`-Cache-Busting; in kleinen, verifizierbaren Schritten mit Smoke-Test |
| Phase D — Lint-Gate | Syntaxfehler vor Deploy abfangen | high | QS | `php -l` über Proxies + `node --check` auf ausgelagertes JS als Pre-Deploy-Check |
| `app/synthesis.php` (Cross-Modul-KI-Synthese) | Roadmap 3.2 offen; aktuell nur Interim in Exec-Summary | medium | Analyse | Eigener LLM-Call mit strukturiertem Gesamt-Input oder als „verworfen" dokumentieren |
| Agent-Registry vereinheitlichen | Aktuell nur `AGENTS.sqeg` registriert; 8 weitere Prompts (YMYL, Exec-Summary, PV L1–L3, UX-Vision, Synonyme, OCR) sind inline hardcodiert. Eine zentrale Registry macht Prompts editierbar, testbar und wiederverwendbar | medium | KI/Architektur | **Spezifikation fertig** (`docs/agent-registry.md`). Offen: Frontend-Registry erweitern (ymyl, execSummary), Multi-Agent-Modal, Prompts nach `app/prompts/` zentralisieren, `runAgent()`. **Refactor mit App-Risiko → schrittweise mit Smoke-Test** |
| Gewichteter Gesamtscore | Übergreifender Score aus gewichteten Modul-Scores | low | Analyse | Gewichtungsschema definieren, Score-Hero erweitern |

## Später / Backlog

| Idee | Notiz |
|---|---|
| Git-History-Slim (großes PDF) | Repo-Historie enthält weiterhin den 8,7 MB-Blob; echtes Verkleinern nur via History-Rewrite (`git filter-repo`/BFG) + Force-Push — destruktiv, nur auf ausdrückliche Anweisung |
| Modul-Extraktion (`app/modules/`) | Ursprünglich in ROADMAP 2.2 geplant, nie umgesetzt; sinnvoll erst nach Phase C |
