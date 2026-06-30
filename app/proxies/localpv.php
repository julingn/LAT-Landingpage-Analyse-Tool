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
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => ['type' => 'parse', 'message' => 'Ungültiger Request-Body (kein JSON).']]);
    exit;
}

$cityOrPostalCode = trim($body['cityOrPostalCode'] ?? '');
$primaryKeyword   = trim($body['primaryKeyword']   ?? '');
$landingPageUrl   = trim($body['landingPageUrl']   ?? '');
$templateType     = trim($body['templateType']     ?? '');

// Optionale Datenquellen-Kontexte (für spätere Integration vorbereitet)
$gscContext       = $body['gscContext']       ?? null; // GSC-Daten: queries, clicks, impressions, ctr, position
$sistrixContext   = $body['sistrixContext']   ?? null; // Sistrix: visibility, keywords, competitors
$dataforseoContext = $body['dataforseoContext'] ?? null; // DataForSEO: search_volume, serp_features

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

$contextBlock = !empty($contextLines)
    ? "\n\nVerfügbare Kontext-Daten:\n" . implode("\n", array_map(fn($l) => "- {$l}", $contextLines))
    : '';

// ── Prompts ──────────────────────────────────────────────────────────────
$systemPrompt = <<<'SYSPROMPT'
Du bist ein spezialisierter SEO- und CRO-Assistent für lokale Photovoltaik-Landingpages in Deutschland.
Du unterstützt ein internes Tool namens LAT (Landingpage Analyse Tool).

AUFGABE:
Erzeuge strukturierte, modulare Content-Bausteine für eine lokale Photovoltaik-Landingpage.
Du erzeugst KEINEN langen Fließtext. Du erzeugst modulare Inhalte pro Seitenabschnitt.

JEDE SECTION HAT ZWEI PFLICHTEBENEN:
1. "micro" — max. 1–2 Sätze, für UI/Teaser/Übergänge, kurz und klar, ohne Füllwörter
2. "content" — 80–150 Wörter, eigenständiger SEO-Absatz, konkret und direkt nutzbar

KONTEXT:
Diese Seiten verkaufen Photovoltaikanlagen in lokalen Märkten.
Seitenmodule: Hero (PV-Rechner = primäre CTA) → Einstieg → Vorteile → Solarpotenzial → Kennzahlen → Referenzen → Wirtschaftlichkeit → FAQ → Formular (Backup-Conversion).

CONVERSION-LOGIK:
- PV-Rechner im Hero ist der primäre Conversion-Punkt
- Formular am Seitenende ist nur sekundär
- Texte erklären visuelle Module, ersetzen sie nicht
- Nutzer sollen immer wieder zum Rechner oder zur Anfrage geführt werden

SCHREIBREGELN (strikt):
VERBOTEN:
- erfundene USPs, Referenzprojekte, konkrete Zahlen ohne Datenbasis
- lange Stadtbeschreibungen, Tourismus-Content
- Floskeln wie "die Stadt hat sich entwickelt", generische KI-Phrasen

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

Erstelle ein JSON-Objekt mit exakt dieser Struktur (alle Felder befüllen):

{
  "input": {
    "cityOrPostalCode": "{$cityOrPostalCode}",
    "primaryKeyword": "{$primaryKeyword}",
    "landingPageUrl": "{$landingPageUrl}"
  },
  "meta": {
    "title": "SEO-optimierter Meta-Title (max. 60 Zeichen, Haupt-Keyword + Stadt)",
    "description": "Meta-Description (max. 160 Zeichen, konkreter Nutzen + CTA)"
  },
  "hero": {
    "h1": "Prägnante H1 mit lokalem Keyword (max. 70 Zeichen)",
    "subline": "Vertrauensbildende Subline (max. 120 Zeichen, kein Buzzword-Bingo)",
    "primaryCta": "CTA-Text für PV-Rechner-Button (kurz, handlungsorientiert)",
    "secondaryCta": "Sekundärer CTA-Text (z.B. kostenlose Beratung)"
  },
  "sections": {
    "intro": {
      "micro": "1–2 Sätze für UI/Teaser (max. 30 Wörter, kein Fülltext, lokal)",
      "content": "80–150 Wörter — eigenständiger Einstiegstext, nutzenorientiert, lokal eingebunden, kein Tourismus-Content"
    },
    "solarPotential": {
      "micro": "1–2 Sätze Brückentext zur Solarpotenzial-Grafik",
      "content": "80–150 Wörter — erklärt was der Nutzer in der Grafik erfährt, warum das Solarpotenzial in dieser Region relevant ist, ohne erfundene Einstrahlungswerte"
    },
    "benefitsIntro": {
      "micro": "1–2 Sätze Einleitung zu den Vorteilen",
      "content": "80–150 Wörter — konkrete Vorteile einer PV-Anlage im lokalen Kontext (Eigenverbrauch, Stromkosten, typische Gebäudetypen), ohne erfundene Zahlen"
    },
    "statisticsExplanation": {
      "micro": "1–2 Sätze Brückentext zum Kennzahlenblock",
      "content": "80–150 Wörter — kontextualisiert die Kennzahlen für den lokalen Markt, erklärt Bedeutung ohne die Zahlen selbst zu erfinden"
    },
    "projectsIntro": {
      "micro": "1–2 Sätze Einleitung zu Referenzprojekten (keine erfundenen Projekte)",
      "content": "80–150 Wörter — Rahmentext der Vertrauen schafft, ohne konkrete Projekte zu erfinden; beschreibt Erfahrung und Expertise im lokalen Markt"
    },
    "economicsText": {
      "micro": "1–2 Sätze Brückentext zur Wirtschaftlichkeitsgrafik",
      "content": "80–150 Wörter — ROI-fokussiert, erklärt Amortisation und Eigenverbrauch realistisch ohne erfundene Zeiträume oder Renditeversprechen"
    },
    "faqIntro": {
      "micro": "1 Satz Einleitung zum FAQ-Bereich",
      "content": "80–150 Wörter — warum diese FAQs relevant für lokale PV-Käufer in dieser Region sind, welche Fragen hier beantwortet werden"
    },
    "formIntro": {
      "micro": "1–2 Sätze über dem Formular (kein Druck, Backup-Conversion)",
      "content": "80–150 Wörter — erklärt den nächsten Schritt, schafft Vertrauen, leitet sanft zur Anfrage, kein Verkaufsdruck"
    }
  },
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
    {"item": "H1 mit lokalem Keyword", "status": "ok", "note": "..."},
    {"item": "Lokaler Breadcrumb vorhanden", "status": "warning", "note": "..."},
    {"item": "Strukturierte Daten (LocalBusiness-Schema)", "status": "missing", "note": "..."},
    {"item": "FAQ-Schema (FAQPage)", "status": "ok", "note": "..."},
    {"item": "Canonical-Tag korrekt gesetzt", "status": "warning", "note": "..."},
    {"item": "Interne Verlinkung zur Hauptseite", "status": "ok", "note": "..."}
  ],
  "croChecklist": [
    {"item": "PV-Rechner als primäre CTA im Hero sichtbar und prominent", "status": "ok", "note": "..."},
    {"item": "Vertrauenssignale im Hero-Bereich (Siegel, Bewertungen, Zertifikate)", "status": "warning", "note": "..."},
    {"item": "Klarer Nutzen in H1 und Subline erkennbar", "status": "ok", "note": "..."},
    {"item": "Nutzerführung zurück zum Rechner nach den Sections", "status": "warning", "note": "..."},
    {"item": "Social Proof / Referenzen vorhanden", "status": "missing", "note": "..."},
    {"item": "Mobile Hero-CTA ohne Scrollen sichtbar", "status": "warning", "note": "..."},
    {"item": "Formular friktionsarm (wenige Pflichtfelder)", "status": "warning", "note": "..."}
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

echo json_encode($result);
