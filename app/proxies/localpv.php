<?php
/**
 * localpv.php — Local PV Generator Proxy
 *
 * Empfängt Stadt/PLZ + optionale Kontext-Daten,
 * baut einen strukturierten Prompt und ruft die Anthropic API auf.
 * Gibt ein JSON-Objekt mit modularen SEO/CRO-Bausteinen zurück.
 *
 * Actions (via POST):
 *   generate → { cityOrPostalCode, primaryKeyword?, landingPageUrl?, templateType?,
 *                gscContext?, sistrixContext?, dataforseoContext? }
 *             ← Strukturiertes JSON (meta, hero, sections, faq, seoChecklist,
 *                croChecklist, recommendations, exportMarkdown)
 */

set_time_limit(180);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => ['type' => 'method', 'message' => 'Method not allowed']]);
    exit;
}

// ── Session-Guard ────────────────────────────────────────────────────────
session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => ['type' => 'unauthorized', 'message' => 'Nicht authentifiziert.']]);
    exit;
}
session_write_close(); // Lock sofort freigeben

// ── Body einmalig lesen (vor Config, damit Archive-Actions früh abgefangen werden) ──
$_pvRaw  = file_get_contents('php://input');
$_pvBody = json_decode($_pvRaw, true) ?? [];
$_pvAction = trim($_pvBody['action'] ?? 'generate');

// ── Archive-Actions (kein API-Key nötig) ─────────────────────────────────
if (in_array($_pvAction, ['archive_list', 'archive_save', 'archive_delete', 'archive_check'], true)) {
    $archivePath = __DIR__ . '/../localpv_archive.json';
    $readEntries = function() use ($archivePath): array {
        if (!file_exists($archivePath)) return [];
        $data = json_decode(file_get_contents($archivePath), true);
        return is_array($data) ? $data : [];
    };

    if ($_pvAction === 'archive_list') {
        echo json_encode(['success' => true, 'entries' => $readEntries()]);
        exit;
    }

    if ($_pvAction === 'archive_check') {
        $location = mb_strtolower(trim($_pvBody['location'] ?? ''));
        $found = null;
        foreach ($readEntries() as $entry) {
            if (mb_strtolower(trim($entry['location'] ?? '')) === $location) { $found = $entry; break; }
        }
        echo json_encode(['success' => true, 'duplicate' => $found]);
        exit;
    }

    if ($_pvAction === 'archive_save') {
        $entries = $readEntries();
        $entry = [
            'id'          => time(),
            'location'    => trim($_pvBody['location']    ?? ''),
            'keyword'     => trim($_pvBody['keyword']     ?? ''),
            'url'         => trim($_pvBody['url']         ?? ''),
            'title'       => trim($_pvBody['title']       ?? ''),
            'date'        => date('Y-m-d'),
            'irradiance'  => (int)($_pvBody['irradiance'] ?? 0),
            'sunshine'    => (int)($_pvBody['sunshine']   ?? 0),
            'dwd_station' => trim($_pvBody['dwd_station'] ?? ''),
        ];
        if (empty($entry['location'])) {
            echo json_encode(['success' => false, 'error' => 'Kein Standort angegeben']); exit;
        }
        array_unshift($entries, $entry);
        file_put_contents($archivePath, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo json_encode(['success' => true, 'entry' => $entry]);
        exit;
    }

    if ($_pvAction === 'archive_delete') {
        $id      = (int)($_pvBody['id'] ?? 0);
        $entries = array_values(array_filter($readEntries(), fn($e) => (int)($e['id'] ?? 0) !== $id));
        file_put_contents($archivePath, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo json_encode(['success' => true]);
        exit;
    }
}

// ── Config ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config.php';

$provider = CFG_AI_PROVIDER; // 'anthropic' oder 'openai'

// Key-Auswahl: Provider-Einstellung + Fallback auf verfügbaren Key
if ($provider === 'openai') {
    $apiKey = CFG_OPENAI_KEY;
    if (empty($apiKey)) {
        http_response_code(503);
        echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein OpenAI API-Key hinterlegt. Bitte OPENAI_API_KEY als ENV setzen oder in den Einstellungen hinterlegen.']]);
        exit;
    }
} else {
    $apiKey = CFG_ANTHROPIC_KEY;
    if (empty($apiKey)) {
        // Fallback: OpenAI nutzen wenn Anthropic-Key fehlt
        if (!empty(CFG_OPENAI_KEY)) {
            $provider = 'openai';
            $apiKey   = CFG_OPENAI_KEY;
        } else {
            http_response_code(503);
            echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein API-Key hinterlegt. Bitte ANTHROPIC_API_KEY oder OPENAI_API_KEY als ENV setzen.']]);
            exit;
        }
    }
}

// ── Input parsen ─────────────────────────────────────────────────────────
$raw  = $_pvRaw;          // bereits gelesen
$body = $_pvBody;         // bereits geparst

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => ['type' => 'parse', 'message' => 'Ungültiger Request-Body (kein JSON).']]);
    exit;
}

$cityOrPostalCode = trim($body['cityOrPostalCode'] ?? '');
$primaryKeyword   = trim($body['primaryKeyword']   ?? '');
$landingPageUrl   = trim($body['landingPageUrl']   ?? '');
$templateType     = trim($body['templateType']     ?? '');

// Optionale Datenquellen-Kontexte
$gscContext        = $body['gscContext']       ?? null; // GSC-Daten: queries, clicks, impressions, ctr, position
$sistrixContext    = $body['sistrixContext']   ?? null; // Sistrix: visibility, keywords, competitors
$dataforseoContext = $body['dataforseoContext'] ?? null; // DataForSEO: search_volume, serp_features
$dwdSolarData      = $body['dwdSolarData']     ?? null; // DWD OpenData: Globalstrahlung, Sonnenstunden

if (empty($cityOrPostalCode)) {
    http_response_code(400);
    echo json_encode(['error' => ['type' => 'validation', 'message' => 'Stadt oder PLZ ist erforderlich.']]);
    exit;
}

// ── Kontext-String aufbauen ──────────────────────────────────────────────
$contextLines = [];
if (!empty($primaryKeyword))   $contextLines[] = "Hauptkeyword: {$primaryKeyword}";
if (!empty($landingPageUrl))   $contextLines[] = "Bestehende LP-URL: {$landingPageUrl}";
if (!empty($templateType))     $contextLines[] = "Seitentyp/Template: {$templateType}";

// GSC-Kontext aufbereiten (falls später übergeben)
if (is_array($gscContext) && !empty($gscContext['queries'])) {
    $topQueries = array_slice($gscContext['queries'], 0, 5);
    $queryList  = implode(', ', array_column($topQueries, 'query'));
    $contextLines[] = "GSC Top-Queries: {$queryList}";
    if (!empty($gscContext['clicks']))      $contextLines[] = "GSC Klicks (90 Tage): {$gscContext['clicks']}";
    if (!empty($gscContext['impressions'])) $contextLines[] = "GSC Impressionen: {$gscContext['impressions']}";
    if (!empty($gscContext['avgPosition'])) $contextLines[] = "GSC Ø Position: {$gscContext['avgPosition']}";
}

// Sistrix-Kontext aufbereiten
if (is_array($sistrixContext)) {
    if (!empty($sistrixContext['visibility']))   $contextLines[] = "Sistrix Sichtbarkeit: {$sistrixContext['visibility']}";
    if (!empty($sistrixContext['kw_count']))     $contextLines[] = "Sistrix Keywords Top 100: {$sistrixContext['kw_count']}";
    if (!empty($sistrixContext['keywords'])) {
        $topKws = array_slice(array_column($sistrixContext['keywords'], 'keyword'), 0, 5);
        $contextLines[] = "Sistrix Top-Keywords: " . implode(', ', $topKws);
    }
}

// DataForSEO-Kontext aufbereiten
if (is_array($dataforseoContext)) {
    if (!empty($dataforseoContext['search_volume'])) $contextLines[] = "Suchvolumen Hauptkeyword: {$dataforseoContext['search_volume']} / Monat";
    if (!empty($dataforseoContext['serp_features']))  $contextLines[] = "SERP-Features: " . implode(', ', (array)$dataforseoContext['serp_features']);
}

// DWD-Solardaten-Kontext aufbereiten (Echtwerte → dürfen konkret im Content verwendet werden)
$dwdBlock = '';
if (is_array($dwdSolarData) && !empty($dwdSolarData['irradiance_kWhm2_year'])) {
    $irr      = (int)$dwdSolarData['irradiance_kWhm2_year'];
    $sun      = (int)($dwdSolarData['sunshine_hours_year'] ?? 0);
    $stName   = $dwdSolarData['station']['name']     ?? 'unbekannte Station';
    $stDist   = $dwdSolarData['station']['distance_km'] ?? '?';
    $year     = $dwdSolarData['dataYear'] ?? null;
    $est      = !empty($dwdSolarData['estimated']);
    $yearNote = $year ? " (Messjahr {$year})" : ' (Schätzung)';
    $dwdBlock = "\n\nEchte DWD-Klimawerte für diese Region (DÜRFEN konkret im Content verwendet werden):\n"
              . "- Globalstrahlung Jahresmittel: {$irr} kWh/m²{$yearNote}\n"
              . "- Sonnenstunden pro Jahr: {$sun} h\n"
              . "- Datenquelle: DWD-Station {$stName} ({$stDist} km Entfernung)\n"
              . "- Diese Werte sind " . ($est ? 'regionaltypische Schätzwerte' : 'gemessene DWD-Klimadaten') . " und sachlich korrekt.";
    // Deutschland-Vergleich anfügen
    $dwdGe = $dwdSolarData['germany_avg'] ?? null;
    if (is_array($dwdGe)) {
        $geIrr = (int)($dwdGe['irradiance_kWhm2_year'] ?? 0);
        $geSun = (int)($dwdGe['sunshine_hours_year']   ?? 0);
        $geYr  = $dwdGe['year'] ?? null;
        $geKN  = (int)($dwdGe['klimanormal_1991_2020'] ?? 0);
        if ($geIrr || $geSun) {
            $dwdBlock .= "\n\nDeutschland-Vergleichswerte (für Einordnung im Content ERLAUBT):";
            if ($geIrr) $dwdBlock .= "\n- Deutschland Ø Globalstrahlung: {$geIrr} kWh/m² (Klimanormal 1991–2020)";
            if ($geSun) $dwdBlock .= "\n- Deutschland Ø Sonnenstunden: {$geSun} h/Jahr" . ($geYr ? " ({$geYr})" : '');
            if ($geKN)   $dwdBlock .= "\n- Deutschland Klimanormal Sonnenstunden 1991\u20132020: {$geKN} h/Jahr (fairer geografischer Vergleichswert)";
            if ($geSun > 0 && $sun > 0 && $geKN > 0) {
                // Vergleich gegen Klimanormal, nicht Jahreswert (Jahreswerte schwanken stark)
                $diff = round((($sun - $geKN) / $geKN) * 100, 1);
                $sign = $diff >= 0 ? '+' : '';
                $dwdBlock .= "\n- Standort-Einordnung vs. Klimanormal: {$sign}{$diff}% (positiv = sonniger als Deutschland-Langzeitschnitt)";
            }
        }
    }
}

$contextBlock = (!empty($contextLines)
    ? "\n\nVerfügbare Kontext-Daten:\n" . implode("\n", array_map(fn($l) => "- {$l}", $contextLines))
    : '') . $dwdBlock;

// ── Dynamische Feldbeschreibungen (abhängig von verfügbaren Daten) ────────
// solarPotential.content: wenn DWD-Werte vorliegen → explizit verwenden
if (is_array($dwdSolarData) && !empty($dwdSolarData['irradiance_kWhm2_year'])) {
    $irr = (int)$dwdSolarData['irradiance_kWhm2_year'];
    $sun = (int)($dwdSolarData['sunshine_hours_year'] ?? 0);
    $est = !empty($dwdSolarData['estimated']);
    $irrNote = $est ? 'regionaltypischer Schätzwert' : 'gemessener DWD-Wert';
    $solarPotentialDesc = "80–150 Wörter — VERWENDE die folgenden Messwerte konkret im Text: "
        . "Globalstrahlung {$irr} kWh/m²/Jahr ({$irrNote})"
        . ($sun > 0 ? ", Sonnenstunden {$sun} h/Jahr" : "")
        . ". Erkläre was diese Zahlen für eine Dachanlage in dieser Region bedeuten "
        . "(z.B. Vergleich mit deutschlandweitem Schnitt ~1.000–1.200 kWh/m², typische Anlage 10 kWp). "
        . "Kein Tourismus-Content, kein erfundener Amortisationszeitraum.";
} else {
    $solarPotentialDesc = "80–150 Wörter — erklärt was der Nutzer in der Grafik erfährt, "
        . "warum das Solarpotenzial in dieser Region relevant ist, ohne erfundene Einstrahlungswerte";
}

// ── Prompts ──────────────────────────────────────────────────────────────
$systemPrompt = <<<'SYSPROMPT'
Du bist ein spezialisierter SEO- und CRO-Assistent für lokale Photovoltaik-Landingpages in Deutschland.
Du unterstützt ein internes Tool namens LAT (Landingpage Analyse Tool).

GRUNDANNAHME (immer gültig):
Die Ziel-Landingpage enthält einen PV-Rechner im Hero-Bereich.
Der PV-Rechner ist der primäre Conversion-Punkt — kein Text, keine CTA darf das in Frage stellen.
Das Kontaktformular am Seitenende ist nur eine sekundäre Backup-Conversion.

AUFGABE:
Erzeuge strukturierte, modulare Content-Bausteine für eine lokale Photovoltaik-Landingpage.
Du erzeugst KEINEN langen Fließtext. Du erzeugst modulare Inhalte pro Seitenabschnitt.

SEITENSTRUKTUR (LP-Reihenfolge):
1. Hero — Dachzeile (kurz, über H1) + H1 (max. 60 Zeichen) + 2–4 USP-Bullets ODER kurzer Absatz
   → Direkt danach folgt der PV-Rechner (1. Frage: „Wie viele Personen leben in Ihrem Haushalt?“) — kein weiterer Text im Hero nötig
2. Intro — lokaler Einstiegstext
3. Vorteile — 4 Kacheln: Unabhängigkeit, Wertsteigerung, Alles aus einer Hand, Zuverlässiger Partner
4. Solarpotenzial — Grafik-Begleitung
5. Kennzahlen — Statistik-Block
6. 3-Schritte-Prozess — Ablauf-Erklärung
7. Referenzprojekte — Trust ohne erfundene Projekte
8. Wirtschaftlichkeit — ROI-Grafik-Begleitung
9. Kundenstimmen — Trust-Einleitung
10. FAQ — Accordion
11. Formular — Backup-CTA (weich, kein Druck)

JEDE SECTION HAT ZWEI PFLICHTEBENEN:
1. "micro" — max. 1–2 Sätze, für UI/Teaser/Übergänge, kurz und klar, ohne Füllwörter
2. "content" — 80–150 Wörter, eigenständiger SEO-Absatz, konkret und direkt nutzbar

CONVERSION-LOGIK:
- Hero-CTA führt immer zum PV-Rechner ("Jetzt Potenzial berechnen" o. ä.)
- Micro-CTAs nach Abschnitten führen ebenfalls zurück zum Rechner
- Formular-CTA ist sanft formuliert, kein Verkaufsdruck
- ctaStrategy liefert 3 Beispiel-CTAs pro Conversion-Ebene + 3 Micro-CTAs mit Placement

VORTEILE-BLOCK (benefits):
Ein Objekt mit H2 (Überschrift), einem Fließtext (intro) und exakt 4 Kacheln (items).
Feste Kachel-Titel (H3): Unabhängigkeit, Wertsteigerung, Alles aus einer Hand, Zuverlässiger Partner.
Alle 4 Beschreibungstexte müssen exakt gleich lang sein (je 2 Sätze, ca. 30–40 Wörter), konkret, lokal.

PLACEMENT MAP:
Erzeuge ein "placementMap"-Array mit 11 Einträgen (order 1–11).
Jeder Eintrag beschreibt genau ein Seitenmodul: order, module, visualType, contentNeeded, generatedFields, recommendation.
Die recommendation ist ein konkreter Einbauhinweis (1–2 Sätze).

SCHREIBREGELN (strikt):
VERBOTEN:
- erfundene Referenzprojekte, konkrete erfundene Zahlen
- erfundene Einstrahlungswerte (ausgenommen: explizit als DWD-Messwerte gelieferte Werte — diese sind echte Daten und DÜRFEN verwendet werden)
- lange Stadtbeschreibungen, Tourismus-Content
- Floskeln wie "die Stadt hat sich entwickelt", generische KI-Phrasen
- Renditeversprechen oder konkrete Amortisationszeiträume ohne Datenbasis

ERLAUBT UND ERWÜNSCHT:
- konkrete Aussagen zu Dachflächen, Eigenverbrauch, Stromkosten, typischen Gebäudetypen
- lokale Einbindung mit Stadtname/PLZ (natürlich, kein Keyword-Stuffing)
- realistische Aussagen ohne exakte erfundene Zahlen
- sachliche, direkt nutzbare Texte ohne Nachbearbeitung

QUALITÄTSZIEL:
- Output direkt in echte Landingpage einbaubar
- nicht nach KI klingen, nicht generisch wirken
- wenn unsicher: weniger schreiben, aber substanzieller

AUSGABEFORMAT: Antworte NUR mit einem validen JSON-Objekt. Kein erklärender Text. Kein Markdown-Codeblock.
SYSPROMPT;

$userPrompt = <<<UPROMPT
Erstelle strukturierte SEO- und CRO-Bausteine für eine lokale Photovoltaik-Landingpage.

Zielort: {$cityOrPostalCode}{$contextBlock}

Der PV-Rechner ist im Hero. Er ist die primäre Conversion. Das Formular am Ende ist nur Backup.

Erstelle ein JSON-Objekt mit exakt dieser Struktur (alle Felder befüllen):

{
  "input": {
    "cityOrPostalCode": "{$cityOrPostalCode}",
    "primaryKeyword": "{$primaryKeyword}",
    "landingPageUrl": "{$landingPageUrl}",
    "pvCalculatorInHero": true
  },
  "meta": {
    "title": "SEO-optimierter Meta-Title (max. 60 Zeichen, Haupt-Keyword + Stadt)",
    "description": "Meta-Description (max. 160 Zeichen, konkreter Nutzen + CTA)"
  },
  "hero": {
    "dachzeile": "Kurze Dachzeile über dem H1 (2–5 Wörter, lokal oder vertrauensbildend, z.B. 'Ihr Photovoltaik-Experte in {$cityOrPostalCode}')",
    "h1": "Prägnante H1 mit lokalem Keyword (max. 60 Zeichen, klar und nutzenorientiert)",
    "usps": [
      "USP 1 — kurz, konkret, Nutzen-fokussiert (max. 10 Wörter)",
      "USP 2 — kurz, konkret (max. 10 Wörter)",
      "USP 3 — kurz, konkret (max. 10 Wörter)"
    ],
    "absatz": "Alternative zu den USPs: 2–3 Sätze (max. 40 Wörter), falls kein Bullet-Format gewünscht. Konkret, kein Buzzword-Bingo."
  },
  "benefits": {
    "h2": "Überschrift für den Vorteile-Block (max. 50 Zeichen, aktivierend)",
    "intro": "1–2 Sätze Fließtext unter der H2 (Einleitung in die 4 Vorteile, lokal, max. 40 Wörter)",
    "items": [
      {"h3": "Unabhängigkeit", "text": "Genau 2 Sätze, ca. 30–40 Wörter, konkret, lokal"},
      {"h3": "Wertsteigerung",  "text": "Genau 2 Sätze, ca. 30–40 Wörter, konkret, lokal"},
      {"h3": "Alles aus einer Hand", "text": "Genau 2 Sätze, ca. 30–40 Wörter, konkret, lokal"},
      {"h3": "Zuverlässiger Partner", "text": "Genau 2 Sätze, ca. 30–40 Wörter, konkret, lokal"}
    ]
  },
  "sections": {
    "intro": {
      "h2": "Abschnittsüberschrift für den Einleitungsbereich (max. 50 Zeichen, lokal, nutzenorientiert)",
      "micro": "1–2 Sätze für UI/Teaser (max. 30 Wörter, kein Fülltext, lokal)",
      "content": "80–150 Wörter — eigenständiger Einstiegstext, nutzenorientiert, lokal eingebunden, kein Tourismus-Content",
      "placement": "2-Spalten-Layout: Links H2 + Text, rechts Stadtbild (wird vom CMS befüllt)"
    },
    "solarPotential": {
      "h2": "Frage-H2 für die Section (z.B. 'Lohnt sich eine Photovoltaikanlage in {$cityOrPostalCode}?', max. 60 Zeichen)",
      "micro": "1–2 Sätze Brückentext über der Grafik (optional, kurz)",
      "content": "{$solarPotentialDesc}",
      "statement": "Kurzes positives Fazit-Statement unter der Grafik (1 Satz, z.B. 'Glückwunsch: Hier lohnt sich eine PV-Anlage also besonders.' — DWD-Wert einsetzen wenn vorhanden)",
      "placement": "H2 oben, Text darunter, dann mikroanimiertes Grafik-Element (Sonnenstunden lokal vs. DE), dann Statement"
    },
    "statisticsExplanation": {
      "h2": "H2 mit Stadtname, aktivierend (z.B. 'Photovoltaik in {$cityOrPostalCode}, was haben Sie davon?')",
      "items": [
        {"icon": "house",  "label": "1–2 Sätze zum Dachflächen-Wert (CMS-dynamisch, lokal formulieren)"},
        {"icon": "sun",    "label": "Sonneneinstrahlung pro Jahr in {$cityOrPostalCode}. (VERWENDE DWD-Wert falls vorhanden)"},
        {"icon": "energy", "label": "1–2 Sätze zum Jahresertrag (CMS-dynamisch, Vergleich Einfamilienhaus erlaubt)"},
        {"icon": "co2",    "label": "1–2 Sätze zur CO₂-Einsparung (CMS-dynamisch, UBA-Emissionsfaktor 0,434 kg/kWh erlaubt)"}
      ],
      "placement": "Gradient-Block: H2 oben, darunter 4 Kennzahlen mit Icon + großer Zahl (CMS) + Label (generiert). Alle Labels gleich kurz."
    },
    "processIntro": {
      "micro": "1–2 Sätze Einleitung zum 3-Schritte-Prozess",
      "content": "80–150 Wörter — erklärt den Ablauf von der Erstberatung bis zur Inbetriebnahme, schafft Vertrauen ohne konkrete Zeitversprechen",
      "placement": "Über dem 3-Schritte-Prozess"
    },
    "projectsIntro": {
      "micro": "1–2 Sätze Einleitung zu Referenzprojekten (keine erfundenen Projekte)",
      "content": "80–150 Wörter — Rahmentext der Vertrauen schafft, ohne konkrete Projekte zu erfinden; beschreibt Erfahrung und Expertise im lokalen Markt",
      "placement": "Über den Referenzprojekt-Karten"
    },
    "economicsText": {
      "micro": "1–2 Sätze Brückentext zur Wirtschaftlichkeitsgrafik",
      "content": "80–150 Wörter — ROI-fokussiert, erklärt Amortisation und Eigenverbrauch realistisch ohne erfundene Zeiträume oder Renditeversprechen",
      "placement": "Vor der Wirtschaftlichkeitsgrafik"
    },
    "testimonialsIntro": {
      "micro": "1–2 Sätze Trust-Einleitung über Kundenstimmen",
      "content": "80–150 Wörter — erklärt warum echte Kundenstimmen wichtig sind, leitet zur Bewertungssektion über, kein erfundener Social Proof",
      "placement": "Über den Kundenstimmen"
    },
    "faqIntro": {
      "micro": "1 Satz Einleitung zum FAQ-Bereich",
      "content": "80–150 Wörter — warum diese FAQs relevant für lokale PV-Käufer in dieser Region sind, welche Fragen hier beantwortet werden",
      "placement": "Über dem FAQ-Accordion"
    },
    "formIntro": {
      "micro": "1–2 Sätze über dem Formular (kein Druck, Backup-Conversion)",
      "content": "80–150 Wörter — erklärt den nächsten Schritt, schafft Vertrauen, leitet sanft zur Anfrage, kein Verkaufsdruck",
      "placement": "Über dem Kontaktformular als Backup-CTA"
    }
  },
  "ctaStrategy": {
    "primaryConversion": {
      "element": "PV-Rechner im Hero",
      "ctaExamples": [
        "Konkreter CTA-Text 1 für PV-Rechner (z.B. Jetzt Potenzial berechnen)",
        "Konkreter CTA-Text 2 für PV-Rechner",
        "Konkreter CTA-Text 3 für PV-Rechner"
      ]
    },
    "secondaryConversion": {
      "element": "Formular am Seitenende",
      "ctaExamples": [
        "Sanfter CTA-Text 1 für Formular",
        "Sanfter CTA-Text 2 für Formular",
        "Sanfter CTA-Text 3 für Formular"
      ]
    },
    "microCtas": [
      {"placement": "Nach Solarpotenzial", "text": "Kurzer Micro-CTA zurück zum Rechner"},
      {"placement": "Nach Kennzahlen", "text": "Kurzer Micro-CTA zurück zum Rechner"},
      {"placement": "Nach Referenzprojekten", "text": "Kurzer Micro-CTA zum Rechner oder zur Anfrage"}
    ]
  },
  "placementMap": [
    {"order": 1, "module": "Hero", "visualType": "hero-text-block", "contentNeeded": ["Dachzeile", "H1", "2–4 USP-Bullets oder Absatz"], "generatedFields": ["hero.dachzeile", "hero.h1", "hero.usps", "hero.absatz"], "recommendation": "Dachzeile über dem H1 platzieren, dann H1, dann USP-Liste als Bullets. PV-Rechner folgt direkt darunter — kein CTA-Button im Hero-Text nötig."},
    {"order": 2, "module": "Einleitung", "visualType": "two-column-text-image", "contentNeeded": ["H2", "Einleitungstext"], "generatedFields": ["sections.intro.h2", "sections.intro.micro", "sections.intro.content"], "recommendation": "2-Spalten-Layout: links H2 über dem Text, rechts Stadtbild (CMS-Bild, kein generierter Inhalt). micro als kompakter Teaser-Satz optional."},
    {"order": 3, "module": "Vorteile", "visualType": "2x2-check-grid", "contentNeeded": ["H2", "Fließtext", "4 Kacheln H3+Text"], "generatedFields": ["benefits.h2", "benefits.intro", "benefits.items[0-3]"], "recommendation": "H2 oben, darunter Fließtext (intro), dann 2×2 Grid mit Checkmark-Icon + H3 + Text. Alle 4 Kacheln gleich lang."},
    {"order": 4, "module": "Solarpotenzial", "visualType": "text-animation-statement", "contentNeeded": ["H2", "Text", "Statement"], "generatedFields": ["sections.solarPotential.h2", "sections.solarPotential.content", "sections.solarPotential.statement"], "recommendation": "H2 zentriert, Text darunter. Animiertes Element zeigt Sonnenstunden lokal vs. DE (CMS-Daten, kein generierter Inhalt). Statement kurz und positiv danach."},
    {"order": 5, "module": "Kennzahlenblock", "visualType": "gradient-stat-grid", "contentNeeded": ["H2", "4 Labels"], "generatedFields": ["sections.statisticsExplanation.h2", "sections.statisticsExplanation.items[0-3].label"], "recommendation": "Gradient-Hintergrund (blau→grün). H2 oben links. 4 Spalten: Icon (fix) + große Zahl (CMS-dynamisch aus Rechner) + Label-Text (generiert). Alle Labels gleich kurz."},
    {"order": 6, "module": "3-Schritte-Prozess", "visualType": "process-grid", "contentNeeded": ["Ablauf-Erklärung"], "generatedFields": ["sections.processIntro.micro", "sections.processIntro.content"], "recommendation": "Einbauhinweis"},
    {"order": 7, "module": "Referenzprojekte", "visualType": "project-cards", "contentNeeded": ["Einordnung der Projektkarten"], "generatedFields": ["sections.projectsIntro.micro", "sections.projectsIntro.content"], "recommendation": "Einbauhinweis"},
    {"order": 8, "module": "Wirtschaftlichkeit", "visualType": "economics-chart", "contentNeeded": ["Eigenverbrauch", "Einspeisung", "Stromkosten"], "generatedFields": ["sections.economicsText.micro", "sections.economicsText.content"], "recommendation": "Einbauhinweis"},
    {"order": 9, "module": "Kundenstimmen", "visualType": "testimonial-section", "contentNeeded": ["Trust-Einleitung"], "generatedFields": ["sections.testimonialsIntro.micro", "sections.testimonialsIntro.content"], "recommendation": "Einbauhinweis"},
    {"order": 10, "module": "FAQ", "visualType": "accordion", "contentNeeded": ["FAQ Intro", "lokale FAQ-Fragen"], "generatedFields": ["sections.faqIntro.micro", "faq"], "recommendation": "Einbauhinweis"},
    {"order": 11, "module": "Formular", "visualType": "form-section", "contentNeeded": ["Backup-CTA", "Beratungstext"], "generatedFields": ["sections.formIntro.micro", "sections.formIntro.content"], "recommendation": "Einbauhinweis"}
  ],
  "faq": [
    {"question": "Häufige lokale PV-Frage 1", "answer": "80–120 Wörter, konkret, ohne generische Aussagen"},
    {"question": "Häufige technische PV-Frage 2", "answer": "80–120 Wörter, konkret"},
    {"question": "Frage zu Kosten und Förderung 3", "answer": "80–120 Wörter, konkret"},
    {"question": "Frage zur Wirtschaftlichkeit 4", "answer": "80–120 Wörter, konkret"},
    {"question": "Frage zu lokalen Bedingungen 5", "answer": "80–120 Wörter, konkret"}
  ],
  "seoChecklist": [
    {"item": "Meta-Title enthält Haupt-Keyword + Stadt", "status": "ok", "note": "Realistisch bewerten basierend auf generiertem Title"},
    {"item": "Meta-Description mit klarem CTA und Nutzenversprechen", "status": "ok", "note": "..."},
    {"item": "H1 mit lokalem Keyword (max. 60 Zeichen)", "status": "ok", "note": "..."},
    {"item": "Lokaler Breadcrumb vorhanden", "status": "warning", "note": "..."},
    {"item": "Strukturierte Daten (LocalBusiness-Schema)", "status": "missing", "note": "..."},
    {"item": "FAQ-Schema (FAQPage)", "status": "ok", "note": "..."},
    {"item": "Canonical-Tag korrekt gesetzt", "status": "warning", "note": "..."},
    {"item": "Interne Verlinkung zur Hauptseite", "status": "ok", "note": "..."}
  ],
  "croChecklist": [
    {"item": "PV-Rechner als primäre CTA im Hero sichtbar und prominent", "status": "ok", "note": "..."},
    {"item": "Calculator Intro (Microcopy) über dem Rechner vorhanden", "status": "ok", "note": "..."},
    {"item": "Vertrauenssignale im Hero-Bereich (Siegel, Bewertungen, Zertifikate)", "status": "warning", "note": "..."},
    {"item": "Klarer Nutzen in H1 + Dachzeile erkennbar", "status": "ok", "note": "..."},
    {"item": "Micro-CTAs nach Abschnitten führen zurück zum Rechner", "status": "warning", "note": "..."},
    {"item": "Social Proof / Kundenstimmen vorhanden", "status": "missing", "note": "..."},
    {"item": "Mobile Hero-CTA ohne Scrollen sichtbar", "status": "warning", "note": "..."},
    {"item": "Formular friktionsarm und als Alternative positioniert", "status": "warning", "note": "..."}
  ],
  "recommendations": [
    {"module": "Hero", "priority": "high", "recommendation": "Konkreter, umsetzbarer Optimierungshinweis"},
    {"module": "Social Proof", "priority": "high", "recommendation": "Empfehlung zu Vertrauenssignalen"},
    {"module": "Strukturierte Daten", "priority": "medium", "recommendation": "Schema.org LocalBusiness + FAQPage"},
    {"module": "FAQ", "priority": "medium", "recommendation": "FAQ-Optimierungshinweis"},
    {"module": "Mobile", "priority": "low", "recommendation": "Mobile-CTA-Hinweis"}
  ],
  "exportMarkdown": "Vollständiges Markdown aller generierten Bausteine in lesbarer Form, gegliedert nach Abschnitten"
}

WICHTIG: Nicht alles pauschal auf 'ok' setzen — SEO- und CRO-Checkliste realistisch bewerten.
Antworte NUR mit dem JSON-Objekt. Kein erklärender Text. Kein Markdown-Codeblock.
UPROMPT;


// ── API-Call ──────────────────────────────────────────────────────────────
$model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;

if ($provider === 'openai') {
    // OpenAI: system-Prompt als role=system Nachricht
    $oaiMessages = [
        ['role' => 'system',  'content' => $systemPrompt],
        ['role' => 'user',    'content' => $userPrompt],
    ];
    $payload = [
        'model'      => $model,
        'max_tokens' => 8000,
        'messages'   => $oaiMessages,
    ];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);
} else {
    // Anthropic
    $payload = [
        'model'      => $model,
        'max_tokens' => 8000,
        'system'     => $systemPrompt,
        'messages'   => [
            ['role' => 'user', 'content' => $userPrompt],
        ],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
    ]);
}

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => ['type' => 'curl', 'message' => 'Netzwerkfehler: ' . $curlError]]);
    exit;
}

if ($httpCode !== 200) {
    $errData = json_decode($response, true);
    http_response_code(502);
    echo json_encode(['error' => ['type' => 'api', 'message' => $errData['error']['message'] ?? ('HTTP ' . $httpCode)]]);
    exit;
}

// ── Antwort parsen ────────────────────────────────────────────────────────
$data = json_decode($response, true);
// Text je nach Provider extrahieren
if ($provider === 'openai') {
    $rawText = $data['choices'][0]['message']['content'] ?? '';
} else {
    $rawText = $data['content'][0]['text'] ?? '';
}

// Markdown-Code-Fences entfernen falls vorhanden
$jsonStr = $rawText;
if (preg_match('/```(?:json)?\s*([\s\S]*?)```/s', $jsonStr, $m)) {
    $jsonStr = $m[1];
}

// JSON-Objekt-Grenzen finden (Fallback)
$jsonStr = trim($jsonStr);
if (!str_starts_with($jsonStr, '{')) {
    $start = strpos($jsonStr, '{');
    $end   = strrpos($jsonStr, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $jsonStr = substr($jsonStr, $start, $end - $start + 1);
    }
}

$result = json_decode($jsonStr, true);
if (!is_array($result)) {
    http_response_code(502);
    echo json_encode([
        'error' => [
            'type'    => 'parse',
            'message' => 'KI-Antwort konnte nicht als JSON geparst werden. Bitte erneut versuchen.',
            'raw'     => substr($rawText, 0, 500),
        ],
    ]);
    exit;
}

// ── _dataFoundation injizieren (PHP-berechnet, nicht LLM-generiert) ───────
if (is_array($dwdSolarData) && !empty($dwdSolarData['irradiance_kWhm2_year'])) {
    $irr  = (int)$dwdSolarData['irradiance_kWhm2_year'];
    $sun  = (int)($dwdSolarData['sunshine_hours_year'] ?? 0);
    $est  = !empty($dwdSolarData['estimated']);
    $deGe = $dwdSolarData['germany_avg'] ?? [];
    $deIrr = (int)($deGe['irradiance_kWhm2_year'] ?? 0);
    $deSun = (int)($deGe['sunshine_hours_year']   ?? 0);
    $deKN  = (int)($deGe['klimanormal_1991_2020']  ?? 0);

    $calcs = [];

    // 1. Standort-Einordnung Sonnenstunden
    if ($sun > 0 && $deKN > 0) {
        $diff = round((($sun - $deKN) / $deKN) * 100, 1);
        $sign = $diff >= 0 ? '+' : '';
        $calcs[] = [
            'label'   => 'Standort-Einordnung Sonnenstunden',
            'formula' => "({$sun} h - {$deKN} h Klimanormal DE) ÷ {$deKN} h × 100",
            'result'  => "{$sign}{$diff}% (" . ($diff >= 0 ? 'überdurchschnittlich' : 'unterdurchschnittlich') . ")",
            'source'  => 'DWD Klimanormal 1991–2020',
        ];
    }

    // 2. Spezifischer Jahresertrag
    if ($irr > 0) {
        $yieldPerkWp = (int)round($irr * 0.85);
        $calcs[] = [
            'label'   => 'Typischer Jahresertrag',
            'formula' => "{$irr} kWh/m² × 0,85 (Systemwirkungsgrad)",
            'result'  => "ca. {$yieldPerkWp} kWh/kWp/Jahr",
            'source'  => 'DWD Globalstrahlung + Branchen-Richtwert Systemwirkungsgrad 85 %',
            'note'    => 'Tatsächlicher Ertrag abhängig von Dachneigung, Ausrichtung, Verschattung und Anlagenqualität.',
        ];

        // 3. Amortisation (10 kWp Beispielanlage)
        $yield10 = $yieldPerkWp * 10;
        $ev = (int)round($yield10 * 0.30 * 0.34);  // 30% Eigenverbrauch @ Ø 34 ct/kWh
        $es = (int)round($yield10 * 0.70 * 0.082); // 70% Einspeisung @ EEG 2024 8,2 ct/kWh
        $jn = $ev + $es;
        if ($jn > 0) {
            $a1 = round(17000 / $jn, 1); // 1.700 €/kWp
            $a2 = round(20000 / $jn, 1); // 2.000 €/kWp
            $calcs[] = [
                'label'   => 'Amortisationszeit (10 kWp Beispielanlage)',
                'formula' => "Investition (17.000–20.000 €) ÷ Jahresnutzen\n"
                    . "Jahresnutzen = {$ev} € Eigenverbrauch (30 % × 0,34 €/kWh) + {$es} € Einspeisung (70 % × 0,082 €/kWh) = {$jn} €/Jahr",
                'result'  => "ca. {$a1}–{$a2} Jahre",
                'source'  => 'DWD-Ertrag + Einspeisevergütung EEG 2024 (8,2 ct/kWh bis 10 kWp) + Bundesnetzagentur Stromøpreis 2024',
                'note'    => 'Richtwert. Tatsächliche Amortisation variiert je nach Anlagengröße, Eigenverbrauchsanteil, Finanzierung und Steuervorteilen.',
            ];
        }

        // 4. CO₂-Einsparung
        $co2kg = round($yield10 * 0.434);  // UBA 2024: 434 g CO₂/kWh
        $co2t  = round($co2kg / 1000, 1);
        $calcs[] = [
            'label'   => 'CO₂-Einsparung (10 kWp, 1 Jahr)',
            'formula' => "{$yield10} kWh/Jahr × 0,434 kg CO₂/kWh",
            'result'  => "ca. {$co2t} t CO₂/Jahr",
            'source'  => 'Umweltbundesamt: Emissionsfaktor Strommix Deutschland 2024 (434 g CO₂/kWh)',
        ];
    }

    $result['_dataFoundation'] = [
        'location'   => $cityOrPostalCode,
        'geocoded'   => $dwdSolarData['geocoded']   ?? null,
        'lat'        => $dwdSolarData['lat']        ?? null,
        'lon'        => $dwdSolarData['lon']        ?? null,
        'dwd'        => [
            'station'          => $dwdSolarData['station']['name']         ?? null,
            'distance_km'      => $dwdSolarData['station']['distance_km']  ?? null,
            'irradiance_kWhm2' => $irr,
            'sunshine_hours'   => $sun,
            'data_year'        => $dwdSolarData['dataYear'] ?? null,
            'estimated'        => $est,
            'source'           => 'DWD OpenData — Deutscher Wetterdienst',
        ],
        'germany_avg' => [
            'irradiance_kWhm2' => $deIrr ?: null,
            'sunshine_hours'   => $deSun ?: null,
            'klimanormal'      => $deKN  ?: null,
        ],
        'calculations' => $calcs,
        'note' => 'Alle Werte in diesem Tab wurden vom Backend anhand echter DWD-Messdaten berechnet — unabhängig vom KI-Modell. Zahlen im generierten Text ohne explizite Quellenangabe können vom KI-Modell stammen und sollten separat geprüft werden.',
    ];
}

echo json_encode($result);
