<?php
set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Session guard — only allow authenticated requests
session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => ['type' => 'unauthorized', 'message' => 'Nicht authentifiziert.']]);
    exit;
}
// Lock sofort freigeben — API-Call kann Sekunden dauern, kein Session-Write nötig
session_write_close();

require_once __DIR__ . '/config.php';

$provider = CFG_AI_PROVIDER; // 'anthropic' oder 'openai'

// ── API-Key je Provider prüfen ──────────────────────────────────────────
if ($provider === 'openai') {
    $apiKey = CFG_OPENAI_KEY;
    if (empty($apiKey)) {
        http_response_code(503);
        echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein OpenAI API-Key hinterlegt. Bitte OPENAI_API_KEY als Umgebungsvariable oder in den Einstellungen setzen.']]);
        exit;
    }
} else {
    $apiKey = CFG_ANTHROPIC_KEY;
    if (empty($apiKey)) {
        http_response_code(503);
        echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein Anthropic API-Key hinterlegt. Bitte ANTHROPIC_API_KEY als Umgebungsvariable oder in den Einstellungen setzen.']]);
        exit;
    }
}

$raw = file_get_contents('php://input');

// ── GET: Verbindungstest ──────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'test') {
    $model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;
    if ($provider === 'openai') {
        $payload = ['model' => $model, 'max_tokens' => 3, 'messages' => [['role' => 'user', 'content' => 'ok']]];
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]]);
    } else {
        $payload = ['model' => $model, 'max_tokens' => 3, 'messages' => [['role' => 'user', 'content' => 'ok']]];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01']]);
    }
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $d = json_decode($r, true);
    if ($code === 200) {
        $returnedModel = $d['model'] ?? ($d['choices'][0]['model'] ?? $model);
        echo json_encode(['success' => true, 'model' => $returnedModel]);
    } else {
        echo json_encode(['success' => false, 'error' => $d['error']['message'] ?? ('HTTP ' . $code)]);
    }
    exit;
}

if (empty($raw)) {
    http_response_code(400);
    echo json_encode(['error' => 'Leerer Request-Body']);
    exit;
}

$body = json_decode($raw, true);
if (!$body || empty($body['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'messages fehlt oder ungültiges JSON']);
    exit;
}

// Modell aus config (Provider-spezifisch)
$model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;

// Build payload — allow caller to override model, but enforce max_tokens cap
$requestedModel  = $body['model'] ?? $model;
$requestedTokens = min((int)($body['max_tokens'] ?? 2000), 4096);
$messages        = $body['messages'];
$systemPrompt    = $body['system'] ?? '';

// ── Anthropic ──────────────────────────────────────────────────────────
if ($provider !== 'openai') {
    $payload = [
        'model'      => $requestedModel,
        'max_tokens' => $requestedTokens,
        'messages'   => $messages,
    ];
    if (!empty($systemPrompt)) {
        $payload['system'] = $systemPrompt;
    }

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        http_response_code(502);
        echo json_encode(['error' => ['type' => 'curl_error', 'message' => $err]]);
        exit;
    }

    http_response_code($httpCode);
    echo $response;
    exit;
}

// ── OpenAI ─────────────────────────────────────────────────────────────
// System-Prompt als erste Nachricht mit role=system einfügen
$oaiMessages = [];
if (!empty($systemPrompt)) {
    $oaiMessages[] = ['role' => 'system', 'content' => $systemPrompt];
}
foreach ($messages as $msg) {
    $oaiMessages[] = $msg;
}

$payload = [
    'model'      => $requestedModel,
    'max_tokens' => $requestedTokens,
    'messages'   => $oaiMessages,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['error' => ['type' => 'curl_error', 'message' => $err]]);
    exit;
}

// OpenAI-Response auf Anthropic-Format normalisieren
// (damit index.php kein zweites Response-Format kennen muss)
$oaiData = json_decode($response, true);
if ($httpCode === 200 && isset($oaiData['choices'][0]['message']['content'])) {
    $text = $oaiData['choices'][0]['message']['content'];
    $normalized = [
        'id'      => $oaiData['id'] ?? '',
        'type'    => 'message',
        'role'    => 'assistant',
        'model'   => $oaiData['model'] ?? $requestedModel,
        'content' => [['type' => 'text', 'text' => $text]],
        'usage'   => [
            'input_tokens'  => $oaiData['usage']['prompt_tokens']     ?? 0,
            'output_tokens' => $oaiData['usage']['completion_tokens'] ?? 0,
        ],
        'stop_reason' => $oaiData['choices'][0]['finish_reason'] ?? 'end_turn',
    ];
    http_response_code(200);
    echo json_encode($normalized);
} else {
    // Fehlerantwort von OpenAI durchleiten
    http_response_code($httpCode);
    echo $response;
}
