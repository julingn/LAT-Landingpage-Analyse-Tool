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
$systemPrompt = require __DIR__ . '/../prompts/pvrefine.php';
// Override aus KI-Agenten-Verwaltung (settings.json: agent_prompt_pvrefine)
$agentOverride = trim((string) cfg('LAT_AGENT_PROMPT_PVREFINE', 'agent_prompt_pvrefine', ''));
if ($agentOverride !== '') { $systemPrompt = $agentOverride; }

$userPrompt = "Schärfe den folgenden JSON-Output inhaltlich nach den oben genannten Regeln.{$dwdRefineNote}\n\nAntworte NUR mit dem optimierten JSON-Objekt. Keine Erklärungen. Kein Markdown.\n\n{$currentJsonStr}";

// ── SSE: Headers + Heartbeat ────────────────────────────────────────────
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');
while (ob_get_level()) { ob_end_clean(); }
ob_implicit_flush(true);

function pvSseEventRefine(string $data): void { echo 'data: ' . $data . "\n\n"; @flush(); }

pvSseEventRefine(json_encode(['status' => 'starting']));

// ── API-Call ──────────────────────────────────────────────────────────────
$model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;
$_lastHbR = time();
$_hbFnR   = function($r, $dlt, $dln, $ult, $uln) use (&$_lastHbR): int {
    if (time() - $_lastHbR >= 8) { pvSseEventRefine(json_encode(['status' => 'thinking'])); $_lastHbR = time(); }
    return 0;
};

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
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_NOPROGRESS     => false,
        CURLOPT_PROGRESSFUNCTION => $_hbFnR,
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
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_NOPROGRESS     => false,
        CURLOPT_PROGRESSFUNCTION => $_hbFnR,
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

if ($curlError) { pvSseEventRefine(json_encode(['status'=>'error','type'=>'curl','message'=>'Netzwerkfehler: '.$curlError])); exit; }
if ($httpCode !== 200) {
    $errData = json_decode($response, true);
    pvSseEventRefine(json_encode(['status'=>'error','type'=>'api','message'=>$errData['error']['message']??('HTTP '.$httpCode)]));
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
    pvSseEventRefine(json_encode(['status'=>'error','type'=>'parse','message'=>'KI-Antwort konnte nicht geparst werden.','raw'=>substr($rawText,0,500)]));
    exit;
}

pvSseEventRefine(json_encode(['status' => 'complete', 'result' => $result]));
