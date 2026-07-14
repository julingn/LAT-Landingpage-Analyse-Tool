# Agent Registry — Spezifikation

> **Zweck:** Grundlage für die Vereinheitlichung aller KI-Prompts des LAT zu konfigurierbaren,
> wiederverwendbaren „Agenten". Diese Datei ist die **Spezifikation** (Design), nicht die
> Implementierung. Sie ermöglicht, den späteren Code-Refactor risikoarm und schrittweise
> umzusetzen (siehe „Migrationsplan").
>
> **Status:** Spezifikation vollständig · Schritt 2+3 umgesetzt (3 Frontend-Agenten registriert & im Tool editierbar) · Schritt 4 für PV-Agenten umgesetzt (Backend-Override + `app/prompts/`) · offen: UX + Content-Finder editierbar, `runAgent()` (siehe `docs/roadmap.md`).

## Ausgangslage

- Bereits vorhanden: eine minimale Registry `AGENTS = { sqeg: {...} }` in `app/index.php`,
  persistente `AGENT_CUSTOM_PROMPTS` (in Settings gespeichert), UI-Komponenten
  `.agent-modal` / `.agent-badge` / `.agent-custom-chip`.
- Aktuell ist **nur `sqeg`** als Agent registriert. Die übrigen 8 Prompts sind inline
  hardcodiert (Frontend in `app/index.php`, Backend in `app/proxies/*`).
- Es handelt sich um **Single-Shot-Prompts** (kein Tool-Calling, keine Schleifen/Memory).
  „Agent" = benannte, konfigurierbare Prompt-Rolle mit definiertem Input/Output.

## Einheitliches Agent-Schema

```
{
  id:            string   // eindeutig, z.B. "sqeg"
  name:          string   // Anzeigename
  description:   string   // Kurzbeschreibung (1–2 Sätze)
  location:      string   // wo der Prompt aktuell lebt
  provider:      "env" | "anthropic" | "openai"   // "env" = CFG_AI_PROVIDER
  model:         string   // z.B. "gpt-4o", "gpt-4.1", "" für ENV-Default
  kind:          "text" | "json" | "vision" | "classify"
  defaultPrompt: string   // System-/Rollen-Prompt
  inputSchema:   string   // was der Agent als User-Input erwartet
  outputSchema:  string   // erwartetes Ausgabeformat
  editable:      boolean  // im Agent-Modal überschreibbar?
  getPrompt():   string   // Custom-Prompt || defaultPrompt
  hasCustom():   boolean
}
```

## Die 9 Agenten

| id | name | location | provider / model | kind | Output |
|---|---|---|---|---|---|
| `sqeg` | SQEG-Analyst | `app/index.php` `AGENTS.sqeg` | env | json | JSON-Array (42 Kriterien) |
| `ymyl` | YMYL-Klassifikator | `app/index.php` `classifyYmyl()` | env | classify | `clear_ymyl\|mixed_ymyl\|none` |
| `execSummary` | Executive-Summary-Writer | `app/index.php` `generateExecSummary()` | env | text | Festes Textformat (3 Probleme + 3 Schritte) |
| `pvGenerate` | PV-Generator (L1, SEO/CRO-Assistent) | `app/proxies/localpv.php` | anthropic → openai | json | Strukturiertes PV-JSON |
| `pvRefine` | PV-Editor (L2, Content-Schärfung) | `app/proxies/localpvrefine.php` | anthropic → openai | json | PV-JSON (geschärft) |
| `pvConvert` | PV-Editor (L3, Conversion) | `app/proxies/localpvconvert.php` | anthropic → openai | json | PV-JSON (conversion-optimiert) |
| `uxVision` | UX/CRO-Vision-Experte | `app/proxies/ux.php` | vision | vision/json | JSON (5 Kriterien je Device) |
| `cfSynonyms` | Synonym-Generator | `app/proxies/contentfinder.php` `fetchAiSynonyms()` | openai / `gpt-4.1` | json | JSON-String-Array |
| `cfOcr` | Bild-OCR | `app/proxies/contentfinder.php` `runImageOcr()` | openai / `gpt-4o` | vision | Text |

> Die PV-Agenten `pvGenerate → pvRefine → pvConvert` bilden bereits eine **verkettete Pipeline**
> (Roh → geschärft → conversion-optimiert) — Referenzmodell für Multi-Step-Agenten.

## Zentralisierung der Prompts

Ziel: **eine** Quelle statt Prompts verstreut in JS + PHP.

- Vorschlag: `app/prompts/` mit je einer Datei pro Agent (PHP-Konstante oder `.txt`), die
  sowohl Backend-Proxies (`require`) als auch Frontend (`app/index.php` via
  `<?= ... ?>`-Injection, wie bei `AGENT_CUSTOM_PROMPTS`) lesen.
- `callApi()` (Frontend) und die Proxy-cURL-Aufrufe (Backend) bleiben als Runner bestehen;
  ein optionaler generischer `runAgent(id, input)` kann darauf aufsetzen.

## Migrationsplan (risikoarm, schrittweise)

> **Wichtig:** `app/index.php` ist ein Monolith, in dem ein einzelner JS-Syntaxfehler den
> gesamten `<script>`-Block bricht. Jeder Schritt braucht Validierung (`node --check` /
> `php -l`) und einen Smoke-Test (lokal oder Railway-Preview). Kein Big-Bang.

1. **Spezifikation** (diese Datei) — ✅ fertig.
2. **Frontend-Registry erweitern:** `ymyl` und `execSummary` als Agenten registriert
   (defaultPrompt = exakt bisherige Strings), Call-Sites auf `AGENTS.<id>.getPrompt()` umgestellt.
   Muster wie `sqeg`. → ✅ umgesetzt & Smoke-getestet (14.07.2026). Jetzt 3 Frontend-Agenten registriert.
3. **Multi-Agent-Modal:** bestehendes Agent-Modal von 1 auf N Agenten ausgebaut — zentrale
   „KI-Agenten"-Sektion in den Einstellungen listet alle registrierten Agenten mit „Bearbeiten"
   (öffnet das Modal), Speichern (persistiert in `settings.json`) und Reset-auf-Default.
   → ✅ umgesetzt für die 3 Frontend-Agenten (14.07.2026). 3-Ebenen-Verdrahtung synchronisiert
   (`$_agentPrompts`-Injection + `settings_save.php`-Whitelist + `AGENTS`-Registry; Regex erlaubt
   nun Großbuchstaben für `execSummary`).
4. **Prompts zentralisieren:** Backend-Prompts (PV L1–L3, UX, Synonyme, OCR) nach `app/prompts/`
   auslagern; Proxies lesen von dort. Verhalten unverändert.
5. **Optionaler `runAgent()`-Runner** + einheitliche Fehler-/Timeout-Behandlung.

## Grenzen / bewusst nicht im Scope

- Keine autonomen Agenten (kein Tool-Calling, keine Selbst-Schleifen, kein persistentes Memory).
- Keine neue KI-Library / kein Framework — bestehende cURL/`callApi`-Infrastruktur genügt.
