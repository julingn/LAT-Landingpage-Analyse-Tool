<?php
/**
 * sistrix.php — Sistrix API Proxy
 *
 * Alle Anfragen laufen serverseitig durch diesen Proxy.
 * Der API-Key wird niemals an den Browser übertragen.
 *
 * Actions (via ?action=...):
 *   test         GET  → Verbindungstest (credits)
 *   url_data     POST → Visibility + Top-Keywords + Quick-Win-Opportunities + Wettbewerber { url, csrf_token }
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

$apiKey = CFG_SISTRIX_KEY;
if (empty($apiKey)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Kein Sistrix API-Key konfiguriert. Bitte in den Einstellungen hinterlegen.']);
    exit;
}

$action = $_GET['action'] ?? '';

/**
 * Führt einen GET-Request gegen die Sistrix API aus.
 * Fügt automatisch api_key, format=json und country=de hinzu.
 */
function sistrixGet(string $endpoint, array $params, string $apiKey): array {
    $params['api_key'] = $apiKey;
    $params['format']  = 'json';
    $params['country'] = 'de';

    $url = 'https://api.sistrix.com/' . ltrim($endpoint, '/') . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'LAT/2.0 (+https://github.com/julingn/LAT-Landingpage-Analyse-Tool)',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['_error' => 'Netzwerkfehler: ' . $err];
    }

    $decoded = json_decode($resp, true);
    if (!is_array($decoded)) {
        return ['_error' => 'Ungültige Antwort von Sistrix (HTTP ' . $httpCode . ')'];
    }

    // Sistrix API-Fehler abfangen
    $firstAnswer = $decoded['answer'][0] ?? [];
    if (isset($firstAnswer['error'])) {
        $msg = $firstAnswer['error'][0]['@message'] ?? 'Unbekannter Sistrix API-Fehler';
        return ['_error' => $msg];
    }

    return $decoded;
}

// ── GET: Verbindungstest ─────────────────────────────────────────────────────

if ($action === 'test') {
    $result = sistrixGet('credits', [], $apiKey);
    if (isset($result['_error'])) {
        echo json_encode(['success' => false, 'error' => $result['_error']]);
        exit;
    }
    $credits = $result['answer'][0]['credits'][0] ?? [];
    echo json_encode([
        'success'   => true,
        'remaining' => $credits['@remaining'] ?? null,
        'used'      => $credits['@used']      ?? null,
    ]);
    exit;
}

// ── POST: URL-Daten (Overview + Keywords) ────────────────────────────────────

if ($action === 'url_data') {
    // CSRF prüfen
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? '');
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF-Token ungültig']);
        exit;
    }

    $url = trim($body['url'] ?? '');
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Gültige URL erforderlich']);
        exit;
    }
    session_write_close(); // Session-Lock freigeben vor langen parallelen API-Calls

    // Domain aus URL extrahieren (für domain.overview)
    $parsedUrl = parse_url($url);
    $domain    = preg_replace('/^www\./i', '', $parsedUrl['host'] ?? '');

    // Drei Endpunkte parallel über cURL Multi fetchen
    $handles = [];
    $multi   = curl_multi_init();
    $baseParams = ['api_key' => $apiKey, 'format' => 'json', 'country' => 'de'];

    $endpoints = [
        'domain.visibilityindex' => array_merge($baseParams, ['domain' => $domain]),
        'domain.kwcount.seo'     => array_merge($baseParams, ['domain' => $domain]),
        'keyword.domain.seo'     => array_merge($baseParams, ['url' => $url, 'limit' => 20]),
        'domain.opportunities'   => array_merge($baseParams, ['domain' => $domain, 'limit' => 20]),
        'domain.competitors.seo' => array_merge($baseParams, ['domain' => $domain, 'limit' => 5]),
    ];

    foreach ($endpoints as $ep => $params) {
        $epUrl = 'https://api.sistrix.com/' . $ep . '?' . http_build_query($params);
        $ch    = curl_init($epUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'LAT/2.0',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$ep] = $ch;
    }

    // Ausführen
    $running = null;
    do { curl_multi_exec($multi, $running); } while ($running > 0);

    $raw = [];
    foreach ($handles as $ep => $ch) {
        $resp = curl_multi_getcontent($ch);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
        $raw[$ep] = json_decode($resp ?: '{}', true) ?? [];
    }
    curl_multi_close($multi);

    // ── domain.visibilityindex parsen ──
    // Struktur: answer[0]['sichtbarkeitsindex'][0]['value']
    $visEntry   = $raw['domain.visibilityindex']['answer'][0]['sichtbarkeitsindex'][0] ?? null;
    $visibility = $visEntry ? round((float)($visEntry['value'] ?? 0), 4) : null;

    // ── domain.kwcount.seo parsen ──
    // Struktur: answer[0]['kwcount.seo'][0]['value']
    $kwcEntry = $raw['domain.kwcount.seo']['answer'][0]['kwcount.seo'][0] ?? null;
    $kwCount  = $kwcEntry ? (int)($kwcEntry['value'] ?? 0) : null;

    // ── keyword.domain.seo parsen ──
    // Struktur: answer[0]['result'] — flache Felder: kw, position, traffic
    $kwItems  = $raw['keyword.domain.seo']['answer'][0]['result'] ?? [];
    $keywords = [];
    if (is_array($kwItems)) {
        foreach (array_slice($kwItems, 0, 20) as $kw) {
            if (!is_array($kw)) continue;
            $keywords[] = [
                'keyword'  => (string)($kw['kw']       ?? ''),
                'position' => (int)($kw['position']    ?? 0),
                'volume'   => (int)($kw['traffic']     ?? 0),
            ];
        }
    }

    // ── domain.opportunities parsen ──
    // Struktur: answer[0]['result'] — Felder: kw, position, gain, url, competition
    $oppItems     = $raw['domain.opportunities']['answer'][0]['result'] ?? [];
    $opportunities = [];
    if (is_array($oppItems)) {
        foreach (array_slice($oppItems, 0, 20) as $item) {
            if (!is_array($item)) continue;
            $opportunities[] = [
                'keyword'     => (string)($item['kw'] ?? $item['keyword'] ?? ''),
                'position'    => (int)($item['position'] ?? 0),
                'gain'        => (int)($item['gain'] ?? 0),
                'url'         => (string)($item['url'] ?? ''),
                'competition' => round((float)($item['competition'] ?? 0), 2),
            ];
        }
    }

    // ── domain.competitors.seo parsen ──
    // Struktur: answer[0]['result'] — Felder: domain, competition
    $compItems   = $raw['domain.competitors.seo']['answer'][0]['result'] ?? [];
    $competitors = [];
    if (is_array($compItems)) {
        foreach (array_slice($compItems, 0, 5) as $item) {
            if (!is_array($item)) continue;
            $competitors[] = [
                'domain'      => (string)($item['domain'] ?? ''),
                'competition' => round((float)($item['competition'] ?? 0), 2),
            ];
        }
    }

    echo json_encode([
        'success'       => true,
        'visibility'    => $visibility,
        'kw_count'      => $kwCount,
        'keywords'      => $keywords,
        'opportunities' => $opportunities,
        'competitors'   => $competitors,
        'no_data'       => ($visibility === null && empty($keywords) && empty($opportunities)),
    ]);
    exit;
}

// ── POST: GEO-Daten (AI-Prompts + Quellen für die Entity) ───────────────────

if ($action === 'geo_data') {
    // CSRF prüfen
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? '');
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF-Token ungültig']);
        exit;
    }
    session_write_close(); // Session-Lock freigeben vor langen API-Calls

    $url = trim($body['url'] ?? '');
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Gültige URL erforderlich']);
        exit;
    }

    // Entity aus Domain ableiten: mvv.de → mvv.de (Sistrix akzeptiert beides)
    $parsedUrl = parse_url($url);
    $entity    = preg_replace('/^www\./i', '', $parsedUrl['host'] ?? '');

    // Zwei Endpunkte parallel fetchen
    $multi      = curl_multi_init();
    $handles    = [];
    $baseParams = ['api_key' => $apiKey, 'format' => 'json', 'country' => 'de'];

    $geoEndpoints = [
        'ai.entity.prompts'  => array_merge($baseParams, ['entity' => $entity, 'limit' => 20]),
        'ai.entity.sources'  => array_merge($baseParams, ['entity' => $entity, 'limit' => 10]),
    ];

    foreach ($geoEndpoints as $ep => $params) {
        $epUrl = 'https://api.sistrix.com/' . $ep . '?' . http_build_query($params);
        $ch    = curl_init($epUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'LAT/2.0 (+https://github.com/julingn/LAT-Landingpage-Analyse-Tool)',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$ep] = $ch;
    }

    $running = null;
    do { curl_multi_exec($multi, $running); } while ($running > 0);

    $raw = [];
    foreach ($handles as $ep => $ch) {
        $resp       = curl_multi_getcontent($ch);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
        $raw[$ep]   = json_decode($resp ?: '{}', true) ?? [];
    }
    curl_multi_close($multi);

    // ── ai.entity.prompts parsen ──
    // Struktur: answer[0]['result'] — Felder: prompt, model, language
    $promptItems = $raw['ai.entity.prompts']['answer'][0]['result'] ?? [];
    $prompts     = [];
    if (is_array($promptItems)) {
        foreach ($promptItems as $item) {
            if (!is_array($item)) continue;
            $prompts[] = [
                'prompt'   => (string)($item['prompt']   ?? ''),
                'model'    => (string)($item['model']    ?? ''),
                'language' => (string)($item['language'] ?? ''),
            ];
        }
    }

    // ── ai.entity.sources parsen ──
    // Struktur: answer[0]['result'] — Felder: url (oder domain)
    $sourceItems = $raw['ai.entity.sources']['answer'][0]['result'] ?? [];
    $sources     = [];
    if (is_array($sourceItems)) {
        foreach ($sourceItems as $item) {
            if (!is_array($item)) continue;
            $sourceUrl = (string)($item['url'] ?? $item['domain'] ?? '');
            if ($sourceUrl) $sources[] = ['url' => $sourceUrl];
        }
    }

    echo json_encode([
        'success' => true,
        'entity'  => $entity,
        'prompts' => $prompts,
        'sources' => $sources,
    ]);
    exit;
}

// ── POST: SERP-Features für GSC-Keywords ────────────────────────────────────

if ($action === 'serp_features') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? '');
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF-Token ungültig']);
        exit;
    }
    session_write_close();

    $keywords = array_slice(array_filter((array)($body['keywords'] ?? []), 'is_string'), 0, 5);
    if (empty($keywords)) {
        echo json_encode(['results' => []]);
        exit;
    }

    $multi      = curl_multi_init();
    $handles    = [];
    $baseParams = ['api_key' => $apiKey, 'format' => 'json', 'country' => 'de'];

    foreach ($keywords as $kw) {
        $epUrl = 'https://api.sistrix.com/keyword.seo.serpfeatures?' . http_build_query(array_merge($baseParams, ['kw' => $kw]));
        $ch    = curl_init($epUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'LAT/2.0 (+https://github.com/julingn/LAT-Landingpage-Analyse-Tool)',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$kw] = $ch;
    }

    $running = null;
    do { curl_multi_exec($multi, $running); } while ($running > 0);

    $results = [];
    foreach ($handles as $kw => $ch) {
        $resp = curl_multi_getcontent($ch);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
        $data  = json_decode($resp ?: '{}', true) ?? [];
        $items = $data['answer'][0]['result'] ?? $data['answer'][0] ?? [];
        // Normalisiere: Feature-Name → Anzahl
        $features = [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $name  = strtolower(str_replace([' ', '-'], '_', (string)($item['type'] ?? $item['serp_type'] ?? '')));
                $count = (int)($item['count'] ?? $item['value'] ?? 1);
                if ($name) $features[$name] = $count;
            }
        }
        $results[$kw] = $features;
    }
    curl_multi_close($multi);

    echo json_encode(['results' => $results]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unbekannte Action: ' . htmlspecialchars($action, ENT_QUOTES)]);
