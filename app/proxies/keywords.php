<?php
/**
 * keywords.php — Keyword-Fit Proxy
 *
 * Ruft Sistrix keyword.seo.searchintent für eine Liste von Keywords ab
 * und liefert Intent-Daten für das M6-Keyword-Fit-Modul.
 *
 * Actions:
 *   search_intent  POST → { keywords:[...], csrf_token }
 *                         → { success, results:{ keyword: { informational, navigational, transactional, commercial } } }
 */

session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}
session_write_close(); // Lock sofort freigeben

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';

// ── POST: Search Intent per Keyword (parallel cURL) ─────────────────────

if ($action === 'search_intent') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // CSRF prüfen
    if (empty($body['csrf_token']) || $body['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF-Fehler']);
        exit;
    }

    $keywords = array_values(array_filter(array_slice((array)($body['keywords'] ?? []), 0, 8)));
    if (empty($keywords)) {
        echo json_encode(['success' => false, 'error' => 'Keine Keywords angegeben']);
        exit;
    }

    $apiKey = CFG_SISTRIX_KEY;
    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'error' => 'Kein Sistrix API-Key konfiguriert']);
        exit;
    }

    // Parallel cURL Multi-Requests
    $handles = [];
    $mh = curl_multi_init();

    foreach ($keywords as $i => $kw) {
        $params = http_build_query([
            'kw'      => $kw,
            'api_key' => $apiKey,
            'format'  => 'json',
            'country' => 'de',
        ]);
        $url = 'https://api.sistrix.com/keyword.seo.searchintent?' . $params;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'LAT/3.0 (+https://github.com/julingn/LAT-Landingpage-Analyse-Tool)',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);
        $handles[$i] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    // Alle Requests abarbeiten
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    $results = [];
    foreach ($handles as $i => $ch) {
        $resp    = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        $kw      = $keywords[$i];
        $decoded = json_decode($resp, true);

        if (!is_array($decoded)) {
            $results[$kw] = null;
            continue;
        }

        // Sistrix API-Fehler abfangen
        $first = $decoded['answer'][0] ?? [];
        if (isset($first['error'])) {
            $results[$kw] = null;
            continue;
        }

        $seo = $first['seo'][0] ?? [];
        $results[$kw] = [
            'keyword'       => $kw,
            'informational' => round((float)($seo['@si_informational'] ?? 0), 3),
            'navigational'  => round((float)($seo['@si_navigational']  ?? 0), 3),
            'transactional' => round((float)($seo['@si_transactional'] ?? 0), 3),
            'commercial'    => round((float)($seo['@si_commercial']    ?? 0), 3),
        ];
    }
    curl_multi_close($mh);

    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
