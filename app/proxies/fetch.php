<?php
session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$url = $_GET['url'] ?? '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige oder fehlende URL']);
    exit;
}

// Only allow http/https
$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array($scheme, ['http', 'https'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nur HTTP/HTTPS-URLs erlaubt']);
    exit;
}

$ctx = stream_context_create([
    'http' => [
        'timeout'     => 20,
        'user_agent'  => 'Mozilla/5.0 (compatible; evalu-pro/1.0; +https://github.com/julingn/LAT-Landingpage-Analyse-Tool)',
        'follow_location' => true,
        'max_redirects'   => 5,
        'ignore_errors'   => true,
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$html = @file_get_contents($url, false, $ctx);

if ($html === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Seite konnte nicht abgerufen werden. Bitte HTML-Modus verwenden.']);
    exit;
}

echo json_encode([
    'html'   => $html,
    'length' => strlen($html),
    'url'    => $url,
]);
