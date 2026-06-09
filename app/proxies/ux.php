<?php
/**
 * ux.php — UX/CRO Analyse Proxy (M5)
 *
 * Schritt 1: Screenshot via Headless Chromium (lokal im Container)
 * Schritt 2: Vision-LLM-Analyse (Anthropic Claude oder OpenAI GPT-4o)
 *
 * Actions:
 *   analyze  POST → { url, csrf_token }
 *                 → { success, score, level, summary, findings:[{area,rating,issue,recommendation}],
 *                     sub_scores:{value_prop,cta,trust,hierarchy,above_fold},
 *                     screenshot_base64 }
 */

session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'analyze') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // CSRF prüfen
    if (empty($body['csrf_token']) || $body['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF-Fehler']);
        exit;
    }

    $url = trim($body['url'] ?? '');
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'error' => 'Ungültige URL']);
        exit;
    }
    // Nur HTTP/HTTPS
    $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        echo json_encode(['success' => false, 'error' => 'Nur HTTP/HTTPS erlaubt']);
        exit;
    }

    // ── Schritt 1: Screenshot ──────────────────────────────────────────────

    $tmpFile = '/tmp/ux_shot_' . bin2hex(random_bytes(8)) . '.png';

    // Chromium-Binary ermitteln
    $candidates = [
        getenv('CHROMIUM_PATH') ?: '',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
    ];
    $chromium = '';
    foreach ($candidates as $c) {
        if ($c && file_exists($c) && is_executable($c)) { $chromium = $c; break; }
    }

    $screenshotBase64 = null;
    if ($chromium) {
        $cmd = $chromium
            . ' --headless=new'
            . ' --no-sandbox'
            . ' --disable-dev-shm-usage'
            . ' --disable-gpu'
            . ' --disable-extensions'
            . ' --disable-software-rasterizer'
            . ' --run-all-compositor-stages-before-draw'
            . ' --virtual-time-budget=5000'
            . ' --window-size=1280,900'
            . ' --screenshot=' . escapeshellarg($tmpFile)
            . ' ' . escapeshellarg($url)
            . ' 2>/dev/null';
        exec($cmd, $out, $exitCode);
        if ($exitCode === 0 && file_exists($tmpFile)) {
            $screenshotBase64 = base64_encode(file_get_contents($tmpFile));
            unlink($tmpFile);
        }
    }

    if (!$screenshotBase64) {
        echo json_encode(['success' => false, 'error' => 'Screenshot fehlgeschlagen — Chromium nicht verfügbar oder Seite nicht erreichbar']);
        exit;
    }

    // ── Schritt 2: Vision-LLM Analyse ─────────────────────────────────────

    $systemPrompt = <<<'PROMPT'
Du bist ein UX/CRO-Experte der Agentur für digitale Marketing-Optimierung. Analysiere den Screenshot einer Landingpage und bewerte die User Experience nach genau diesen 5 Kriterien:

1. Value Proposition — Ist der Hauptnutzen sofort klar und überzeugend?
2. CTA — Sind Call-to-Action-Elemente sichtbar, eindeutig und gut platziert?
3. Trust-Signale — Sind Vertrauenselemente (Logos, Testimonials, Zertifikate, Siegel) vorhanden?
4. Visuelle Hierarchie — Ist die Struktur klar, lesbar und führt den Blick?
5. Above-the-Fold — Sind die relevantesten Inhalte ohne Scrollen sichtbar?

Antworte NUR mit einem JSON-Objekt ohne Markdown-Formatierung. Exaktes Format:
{
  "score": <Gesamtscore 0-100>,
  "level": "<Lowest|Low|Medium|High|Highest>",
  "summary": "<2-3 Sätze sachliche Gesamtbewertung auf Deutsch>",
  "findings": [
    {"area": "Value Proposition", "rating": "<green|amber|red>", "issue": "<konkreter Befund>", "recommendation": "<konkrete Maßnahme>"},
    {"area": "CTA", "rating": "<green|amber|red>", "issue": "<konkreter Befund>", "recommendation": "<konkrete Maßnahme>"},
    {"area": "Trust-Signale", "rating": "<green|amber|red>", "issue": "<konkreter Befund>", "recommendation": "<konkrete Maßnahme>"},
    {"area": "Visuelle Hierarchie", "rating": "<green|amber|red>", "issue": "<konkreter Befund>", "recommendation": "<konkrete Maßnahme>"},
    {"area": "Above-the-Fold", "rating": "<green|amber|red>", "issue": "<konkreter Befund>", "recommendation": "<konkrete Maßnahme>"}
  ],
  "sub_scores": {
    "value_prop": <0-100>,
    "cta": <0-100>,
    "trust": <0-100>,
    "hierarchy": <0-100>,
    "above_fold": <0-100>
  }
}
PROMPT;

    $text = '';
    $provider = CFG_AI_PROVIDER;
    $anthropicKey = CFG_ANTHROPIC_KEY;
    $openaiKey    = CFG_OPENAI_KEY;

    if ($provider === 'anthropic' && $anthropicKey) {
        // Anthropic Claude Vision
        $model = CFG_AI_MODEL ?: 'claude-sonnet-4-5';
        $payload = [
            'model'      => $model,
            'max_tokens' => 1500,
            'system'     => $systemPrompt,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => $screenshotBase64]],
                    ['type' => 'text',  'text'   => 'Analysiere diese Landingpage. URL: ' . $url],
                ],
            ]],
        ];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: '          . $anthropicKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'error' => 'Anthropic API Fehler: HTTP ' . $httpCode]);
            exit;
        }
        $respData = json_decode($resp, true);
        $text = $respData['content'][0]['text'] ?? '';

    } elseif ($openaiKey) {
        // OpenAI GPT-4o Vision
        $model = CFG_OPENAI_MODEL ?: 'gpt-4o';
        $payload = [
            'model'      => $model,
            'max_tokens' => 1500,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => [
                    ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . $screenshotBase64, 'detail' => 'high']],
                    ['type' => 'text',      'text'      => 'Analysiere diese Landingpage. URL: ' . $url],
                ]],
            ],
        ];
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $openaiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API Fehler: HTTP ' . $httpCode]);
            exit;
        }
        $respData = json_decode($resp, true);
        $text = $respData['choices'][0]['message']['content'] ?? '';

    } else {
        echo json_encode(['success' => false, 'error' => 'Kein API-Key konfiguriert (Anthropic oder OpenAI)']);
        exit;
    }

    // JSON aus LLM-Antwort extrahieren
    $jsonStart = strpos($text, '{');
    $jsonEnd   = strrpos($text, '}');
    if ($jsonStart === false || $jsonEnd === false) {
        echo json_encode(['success' => false, 'error' => 'LLM-Antwort enthält kein JSON']);
        exit;
    }
    $analysis = json_decode(substr($text, $jsonStart, $jsonEnd - $jsonStart + 1), true);
    if (!is_array($analysis)) {
        echo json_encode(['success' => false, 'error' => 'LLM JSON nicht parsebar']);
        exit;
    }

    echo json_encode(array_merge(
        ['success' => true, 'screenshot_base64' => $screenshotBase64],
        $analysis
    ));
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unbekannte Aktion: ' . htmlspecialchars($action)]);
