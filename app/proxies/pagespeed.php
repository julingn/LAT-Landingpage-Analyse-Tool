<?php
/**
 * pagespeed.php — Google PageSpeed Insights Proxy
 * POST { url, strategy: 'mobile'|'desktop' }
 * → { success, perf_score, lcp, fid, cls, fcp, tbt, si, tti, ... }
 */

session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}
session_write_close();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/config.php';

// ── GET: Verbindungstest ──
if (($_GET['action'] ?? '') === 'test') {
    $key = CFG_PAGESPEED_KEY;
    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'Kein API-Key konfiguriert (PAGESPEED_API_KEY)']);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require_once __DIR__ . '/config.php';

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$url      = trim($body['url'] ?? '');
$strategy = in_array($body['strategy'] ?? '', ['desktop', 'mobile']) ? $body['strategy'] : 'mobile';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige URL']);
    exit;
}

// Nur HTTPS/HTTP erlaubt
$scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
if (!in_array($scheme, ['http', 'https'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nur HTTP/HTTPS-URLs erlaubt']);
    exit;
}

$psiKey = CFG_PAGESPEED_KEY;

$psiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
    . '?url=' . rawurlencode($url)
    . '&strategy=' . $strategy
    . '&category=PERFORMANCE'
    . ($psiKey ? '&key=' . rawurlencode($psiKey) : '');

$ch = curl_init($psiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['success' => false, 'error' => $curlErr]);
    exit;
}

$data = json_decode($resp, true);
if ($httpCode !== 200 || isset($data['error'])) {
    $msg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$cats   = $data['lighthouseResult']['categories'] ?? [];
$audits = $data['lighthouseResult']['audits'] ?? [];

function metricVal(array $audits, string $key): ?string {
    return $audits[$key]['displayValue'] ?? null;
}
function metricScore(array $audits, string $key): ?int {
    $s = $audits[$key]['score'] ?? null;
    return $s !== null ? (int) round((float)$s * 100) : null;
}

echo json_encode([
    'success'    => true,
    'strategy'   => $strategy,
    'perf_score' => isset($cats['performance']['score']) ? (int) round($cats['performance']['score'] * 100) : null,
    'fcp'        => metricVal($audits, 'first-contentful-paint'),
    'lcp'        => metricVal($audits, 'largest-contentful-paint'),
    'tbt'        => metricVal($audits, 'total-blocking-time'),
    'cls'        => metricVal($audits, 'cumulative-layout-shift'),
    'si'         => metricVal($audits, 'speed-index'),
    'tti'        => metricVal($audits, 'interactive'),
    'fcp_score'  => metricScore($audits, 'first-contentful-paint'),
    'lcp_score'  => metricScore($audits, 'largest-contentful-paint'),
    'cls_score'  => metricScore($audits, 'cumulative-layout-shift'),
]);
