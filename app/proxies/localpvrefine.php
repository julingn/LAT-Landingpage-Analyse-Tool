<?php
/**
 * localpvrefine.php — Local PV Generator: Content-Schärfungs-Pass
 *
 * Empfängt den vorhandenen JSON-Output des Generators,
 * schickt ihn mit einem Refinement-Prompt an die KI und gibt
 * den geschärften JSON zurück (gleiche Struktur, verbesserter Inhalt).
 */

set_time_limit(180);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
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
session_write_close();

// ── Config ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config.php';

$provider = CFG_AI_PROVIDER;
if ($provider === 'openai') {
    $apiKey = CFG_OPENAI_KEY;
    if (empty($apiKey)) {
        http_response_code(503);
        echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein OpenAI API-Key hinterlegt.']]);
        exit;
    }
} else {
    $apiKey = CFG_ANTHROPIC_KEY;
    if (empty($apiKey)) {
        if (!empty(CFG_OPENAI_KEY)) { $provider = 'openai'; $apiKey = CFG_OPENAI_KEY; }
        else {
            http_response_code(503);
            echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein API-Key hinterlegt.']]);
            exit;
        }
    }
}

// ── Input ────────────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body) || empty($body['currentJson'])) {
    http_response_code(400);
    echo json_encode(['error' => ['type' => 'parse', 'message' => 'Kein currentJson im Request-Body.']]);
    exit;
}

$currentJsonStr = is_string($body['currentJson'])
    ? $body['currentJson']
    : json_encode($body['currentJson'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// DWD-Kontext für Refine-Pass (Zahlen schützen)
$dwdRefineNote = '';
$dwdSolarData  = $body['dwdSolarData'] ?? null;
if (is_array($dwdSolarData) && !empty($dwdSolarData['irradiance_kWhm2_year'])) {
    $irr = (int)$dwdSolarData['irradiance_kWhm2_year'];
    $sun = (int)($dwdSolarData['sunshine_hours_year'] ?? 0);
    $est = !empty($dwdSolarData['estimated']);
    $dwdRefineNote = "\n\nWICHTIG — DWD-Klimamesswerte für diese Region:"
        . "\n- Globalstrahlung: {$irr} kWh/m²/Jahr" . ($est ? ' (Schätzwert)' : ' (DWD-Messung)')
        . ($sun > 0 ? "\n- Sonnenstunden: {$sun} h/Jahr" : '')
        . "\nDiese Zahlen STAMMEN AUS ECHTEN DWD-DATEN. Falls sie im bestehenden Content vorkommen: BEHALTEN und gängig einbetten. NICHT entfernen, NICHT abrunden auf andere Werte.";
}

// ── Prompts ───────────────────────────────────────────────────────────────
$systemPrompt = <<<'SYSPROMPT'
Du bist ein spezialisierter SEO- und CRO-Editor für lokale Photovoltaik-Landingpages in Deutschland.

Du erhältst bereits generierte Content-Bausteine für eine lokale PV-Landingpage.
Deine Aufgabe ist NICHT, die Struktur komplett neu zu erstellen.
Deine Aufgabe ist, die vorhandenen Inhalte gezielt zu schärfen, konkreter zu machen und stärker auf Conversion auszurichten.

GRUNDANNAHME (immer gültig):
Auf der Landingpage ist ein PV-Rechner im Hero. Er ist der primäre Conversion-Punkt.
Das Kontaktformular am Ende ist nur eine sekundäre Backup-Conversion.

ZIEL:
Verbessere die Texte so, dass sie:
- weniger generisch wirken
- konkreter und glaubwürdiger sind
- den Nutzen für Nutzer klarer machen
- besser zur bestehenden Landingpage-Struktur passen
- stärker auf den PV-Rechner im Hero einzahlen
- SEO-relevant bleiben
- direkt einbaubar sind

HÄUFIGE SCHWÄCHEN (beheben):
1. Zu generische Aussagen — „Viele Dächer eignen sich gut", „Photovoltaik lohnt sich langfristig" → konkreter machen
2. Fehlende Nutzerperspektive — Was bedeutet das für mein Dach? Was bringt mir das konkret?
3. Zu schwache Conversion-Logik — jeder relevante Abschnitt soll auf eine Aktion einzahlen
4. Zu wenig Interpretation von Zahlen/Grafiken — was bedeuten sie, wie nützen sie dem Nutzer?
5. Austauschbare Standardtexte — nutze konkrete PV-Kontexte: Dachfläche, Ausrichtung, Verschattung, Eigenverbrauch, Stromkosten, Speicher, Einspeisung, Gebäudetypen

STRENGE REGELN — VERBOTEN:
- neue USPs erfinden
- Referenzprojekte erfinden
- konkrete Zahlen erfinden
- Förderversprechen / Garantien
- Übertreibungen
- Stadtporträts / touristische Info
- generische KI-Floskeln
- Phrasen wie „nachhaltig und zukunftsorientiert", „maßgeschneiderte Lösung", „optimale Lösung", „perfekte Voraussetzungen", „innovativ"

ERLAUBT:
- bestehende Aussagen präzisieren
- Nutzen klarer formulieren
- realistische PV-Szenarien beschreiben (Dachfläche, Ausrichtung, Eigenverbrauch etc.)
- vorhandene lokale Bezüge stärken
- CTAs klarer machen
- bei fehlenden Daten neutral formulieren: „abhängig von Dachfläche, Ausrichtung und Verbrauch"

MODUL-SPEZIFIKA:

Hero: H1 lokal + konkret, Subline mit klarem Nutzen, calculatorIntro erklärt was der Nutzer berechnen kann, primaryCta zum Rechner (z.B. "PV-Potenzial berechnen"), secondaryCta zur Beratung.

Intro: Lokal starten, direkt zu PV führen, kein Stadtporträt, konkreter Nutzen in 2–4 Punkten.

Benefits: Kurz, konkreter Nutzen, keine Marketingphrasen. z.B. "Reduzieren Sie Ihren Strombezug und machen Sie sich unabhängiger von steigenden Energiepreisen."

Solarpotenzial: Erkläre welche Gebäudefaktoren zählen (Dachfläche, Ausrichtung, Neigung, Verschattung), warum der Rechner individuelle Ergebnisse liefert.

Kennzahlen: Zahlen übersetzen, erklären was sie für Nutzer bedeuten, auf PV-Rechner oder Beratung hinweisen.

3-Schritte-Prozess: Sicherheit geben, erkläre was nach der Rechnernutzung passiert.

Referenzprojekte: Als Vertrauensbeweis erklären was die Projekte zeigen, warum sie keine individuelle Berechnung ersetzen.

Wirtschaftlichkeit: Eigenverbrauch, Einspeisung, Speicher, Verbrauchsverhalten erklären. Vermeiden: "amortisiert sich langfristig". Besser: "wirtschaftlich vor allem dann, wenn erzeugter Strom direkt selbst genutzt wird".

Kundenstimmen: Vertrauen durch Beratung, Verlässlichkeit, Installation, regionale Erfahrung.

FAQ: Konkret, echte Einwände beantworten, 80–120 Wörter, lokale Suchintention.

Formular: Niedrigschwellig, persönliche Einschätzung, nicht aggressiv.

CTAs primär: "PV-Potenzial berechnen", "Ertrag für mein Dach prüfen", "Solarpotenzial jetzt berechnen"
CTAs sekundär: "Persönliche Beratung anfragen", "Unverbindliches Angebot anfordern", "Rückruf zur PV-Beratung vereinbaren"
Micro-CTAs: nach Solarpotenzial, Kennzahlen, Referenzprojekten, Wirtschaftlichkeit einsetzen

AUSGABEFORMAT:
- Behalte die exakte bestehende JSON-Struktur bei
- Ändere keine Feldnamen
- Ergänze keine neuen Top-Level-Felder
- Optimiere nur den Inhalt innerhalb der vorhandenen Felder
- Antworte NUR mit dem validen JSON-Objekt — kein erklärender Text, kein Markdown-Codeblock
SYSPROMPT;

$userPrompt = "Schärfe den folgenden JSON-Output inhaltlich nach den oben genannten Regeln.{$dwdRefineNote}\n\nAntworte NUR mit dem optimierten JSON-Objekt. Keine Erklärungen. Kein Markdown.\n\n{$currentJsonStr}";

// ── API-Call ──────────────────────────────────────────────────────────────
$model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;

if ($provider === 'openai') {
    $payload = [
        'model'      => $model,
        'max_tokens' => 8000,
        'messages'   => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
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
    $payload = [
        'model'      => $model,
        'max_tokens' => 8000,
        'system'     => $systemPrompt,
        'messages'   => [['role' => 'user', 'content' => $userPrompt]],
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
$rawText = ($provider === 'openai')
    ? ($data['choices'][0]['message']['content'] ?? '')
    : ($data['content'][0]['text'] ?? '');

$jsonStr = $rawText;
if (preg_match('/```(?:json)?\s*([\s\S]*?)```/s', $jsonStr, $m)) {
    $jsonStr = $m[1];
}
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
            'message' => 'KI-Antwort konnte nicht als JSON geparst werden.',
            'raw'     => substr($rawText, 0, 500),
        ],
    ]);
    exit;
}

echo json_encode($result);
