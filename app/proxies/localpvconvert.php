<?php
/**
 * localpvconvert.php — Local PV Generator: Level-3 Conversion-Pass
 *
 * Empfängt den Level-2-JSON (sharpenedOutput), optimiert ihn selektiv
 * auf Conversion-Stärke und gibt den Level-3-JSON zurück.
 */

set_time_limit(180);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => ['type' => 'method', 'message' => 'Method not allowed']]);
    exit;
}

session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => ['type' => 'unauthorized', 'message' => 'Nicht authentifiziert.']]);
    exit;
}
session_write_close();

require_once __DIR__ . '/../config.php';

$provider = CFG_AI_PROVIDER;
if ($provider === 'openai') {
    $apiKey = CFG_OPENAI_KEY;
    if (empty($apiKey)) { http_response_code(503); echo json_encode(['error'=>['type'=>'no_key','message'=>'Kein OpenAI API-Key.']]); exit; }
} else {
    $apiKey = CFG_ANTHROPIC_KEY;
    if (empty($apiKey)) {
        if (!empty(CFG_OPENAI_KEY)) { $provider = 'openai'; $apiKey = CFG_OPENAI_KEY; }
        else { http_response_code(503); echo json_encode(['error'=>['type'=>'no_key','message'=>'Kein API-Key.']]); exit; }
    }
}

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

// DWD-Kontext für Convert-Pass (Zahlen schützen)
$dwdConvertNote = '';
$dwdSolarData   = $body['dwdSolarData'] ?? null;
if (is_array($dwdSolarData) && !empty($dwdSolarData['irradiance_kWhm2_year'])) {
    $irr = (int)$dwdSolarData['irradiance_kWhm2_year'];
    $sun = (int)($dwdSolarData['sunshine_hours_year'] ?? 0);
    $est = !empty($dwdSolarData['estimated']);
    $dwdConvertNote = "\n\nWICHTIG — DWD-Klimamesswerte für diese Region:"
        . "\n- Globalstrahlung: {$irr} kWh/m²/Jahr" . ($est ? ' (Schätzwert)' : ' (DWD-Messung)')
        . ($sun > 0 ? "\n- Sonnenstunden: {$sun} h/Jahr" : '')
        . "\nDiese Zahlen stammen aus echten DWD-Daten. Falls sie im Content erscheinen: BEHALTEN. NICHT entfernen oder auf andere Werte ändern.";
}

// ── Prompts ──────────────────────────────────────────────────────────────
$systemPrompt = <<<'SYSPROMPT'
Du bist ein spezialisierter Conversion-Editor für lokale Photovoltaik-Landingpages in Deutschland.

Du erhältst einen bereits geschärften Level-2-JSON-Output für eine lokale PV-Landingpage.

GRUNDANNAHME: Auf der Landingpage ist ein PV-Rechner im Hero. Er ist der primäre Conversion-Punkt. Das Formular am Ende ist nur sekundäre Backup-Conversion.

DEINE AUFGABE — SELEKTIV OPTIMIEREN:
Analysiere den Level-2-Output und optimiere NUR dort, wo eine echte Conversion-Verbesserung möglich ist.
Ändere nicht, was bereits konkret, hilfreich und handlungsstark ist.

OPTIMIERE NUR BEI DIESEN SCHWÄCHEN:
1. Schwache Conversion-Logik (kein klarer nächster Schritt)
2. Zu abstrakte Nutzenformulierungen
3. Unklare CTA-Unterscheidung (Rechner vs. Formular)
4. FAQ-Antworten, die informieren statt Entscheidung unterstützen
5. Placement-Empfehlungen, die zu unkonkret sind
6. Abschnitte ohne logische Handlungsführung

SELEKTIVE BEARBEITUNGSLOGIK:
Vor jeder Änderung intern prüfen:
- Ist der Text bereits konkret?
- Enthält er einen klaren Nutzen?
- Passt er zum Modul?
- Ist der nächste Schritt klar?
- Klingt er vertrauenswürdig?
→ Wenn 4/5 erfüllt: unverändert lassen oder minimal verbessern
→ Wenn unter 4/5: gezielt optimieren

CTA-REGELN:
Primäre CTAs → PV-Rechner: "Jetzt PV-Potenzial berechnen", "Ertrag für mein Dach prüfen", "Solarpotenzial berechnen"
Sekundäre CTAs → Formular: "Persönliche Beratung anfragen", "Unverbindliches Angebot anfordern", "Rückruf vereinbaren"
Micro-CTAs: kurz, nicht aufdringlich, nach Solarpotenzial/Kennzahlen/Referenzen/Wirtschaftlichkeit

CONVERSION-PRINZIPIEN:
- Relevanz: "Das betrifft mein Dach, meinen Stromverbrauch, meine Entscheidung"
- Verständlichkeit: Dachfläche, Ausrichtung, Verschattung, Eigenverbrauch, Einspeisung, Speicher
- Nutzen: "Was bringt mir diese Information?"
- Vertrauen: realistische Orientierung, keine Pauschalen
- Nächster Schritt: Rechner nutzen, Potenzial prüfen, Beratung anfragen

VERBOTEN:
- JSON-Struktur verändern / Feldnamen ändern / Felder entfernen oder ergänzen
- Gesamten Text ohne Grund neu schreiben
- Gute Level-2-Formulierungen verschlechtern
- USPs/Zahlen/Referenzprojekte erfinden / Garantien / Druck / Verknappung
- Stadtporträts / touristische Info / Übertreibungen
- Phrasen: "profitieren Sie von zahlreichen Vorteilen", "maßgeschneiderte Lösung", "optimale Lösung", "nachhaltige Zukunft", "innovativ", "perfekte Voraussetzungen"

ERLAUBT:
- Abschnittsenden conversion-stärker formulieren
- Dezente Micro-CTAs ergänzen wo sinnvoll
- PV-Nutzen klarer machen (Dachfläche, Ausrichtung, Eigenverbrauch)
- CTA-Texte aktiver machen
- Inhalte stärker mit dem PV-Rechner verbinden

AUSGABEFORMAT:
- Exakt dieselbe JSON-Struktur beibehalten
- Keine Feldnamen ändern
- Keine neuen Top-Level-Felder
- Nur Inhalte innerhalb vorhandener Felder optimieren
- Antworte NUR mit dem validen JSON-Objekt — kein Text, kein Markdown
SYSPROMPT;

$userPrompt = "Optimiere den folgenden Level-2-JSON-Output selektiv auf Conversion-Stärke.{$dwdConvertNote}\n\nNur ändern, wo eine echte Verbesserung möglich ist. Antworte NUR mit dem optimierten JSON-Objekt.\n\n{$currentJsonStr}";

// ── SSE: Headers + Heartbeat ────────────────────────────────────────────
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');
while (ob_get_level()) { ob_end_clean(); }
ob_implicit_flush(true);
function pvSseEventConvert(string $data): void { echo 'data: ' . $data . "\n\n"; @flush(); }
pvSseEventConvert(json_encode(['status' => 'starting']));

// ── API-Call ──────────────────────────────────────────────────────────────
$model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;
$_lastHbC = time();
$_hbFnC   = function($r,$dlt,$dln,$ult,$uln) use (&$_lastHbC): int {
    if(time()-$_lastHbC>=8){pvSseEventConvert(json_encode(['status'=>'thinking']));$_lastHbC=time();}return 0;
};

if ($provider === 'openai') {
    $payload = ['model' => $model, 'max_tokens' => 8000, 'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $userPrompt],
    ]];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>300,CURLOPT_NOPROGRESS=>false,CURLOPT_PROGRESSFUNCTION=>$_hbFnC,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$apiKey]]);
} else {
    $payload = ['model' => $model, 'max_tokens' => 8000, 'system' => $systemPrompt, 'messages' => [['role'=>'user','content'=>$userPrompt]]];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>300,CURLOPT_NOPROGRESS=>false,CURLOPT_PROGRESSFUNCTION=>$_hbFnC,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01']]);
}

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) { pvSseEventConvert(json_encode(['status'=>'error','type'=>'curl','message'=>'Netzwerkfehler: '.$curlError])); exit; }
if ($httpCode !== 200) {
    $errData = json_decode($response, true);
    pvSseEventConvert(json_encode(['status'=>'error','type'=>'api','message'=>$errData['error']['message']??('HTTP '.$httpCode)]));
    exit;
}

$data    = json_decode($response, true);
$rawText = ($provider === 'openai') ? ($data['choices'][0]['message']['content']??'') : ($data['content'][0]['text']??'');

$jsonStr = $rawText;
if (preg_match('/```(?:json)?\s*([\s\S]*?)```/s', $jsonStr, $m)) { $jsonStr = $m[1]; }
$jsonStr = trim($jsonStr);
if (!str_starts_with($jsonStr, '{')) {
    $start = strpos($jsonStr, '{'); $end = strrpos($jsonStr, '}');
    if ($start !== false && $end !== false && $end > $start) { $jsonStr = substr($jsonStr, $start, $end - $start + 1); }
}

$result = json_decode($jsonStr, true);
if (!is_array($result)) {
    pvSseEventConvert(json_encode(['status'=>'error','type'=>'parse','message'=>'KI-Antwort konnte nicht als JSON geparst werden.','raw'=>substr($rawText,0,500)]));
    exit;
}

pvSseEventConvert(json_encode(['status' => 'complete', 'result' => $result]));
