<?php
/**
 * contentfinder.php — Content Finder Proxy
 *
 * Actions (via ?action=...):
 *   synonyms   POST { term, options, csrf_token }
 *              → { variants: [{text,type}], synonyms: [{text,type}] }
 *
 *   crawl_url  POST { url, term_list: [{term, variants:[{text,type}]}], options, csrf_token }
 *              → { url, hits: [{term,variant,type,location,context,matched,img_src}], stats }
 */

ini_set('display_errors', '0');
error_reporting(0);
set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

// ── Auth: Token-File-Muster (identisch zu ux.php) ────────────────────────────
$_rawInput  = file_get_contents('php://input');
$_body      = json_decode($_rawInput, true) ?? [];
$_csrfToken = $_body['csrf_token'] ?? '';
$_authed    = false;
if ($_csrfToken && strlen($_csrfToken) === 64) {
    $_tokFile = sys_get_temp_dir() . '/lat_ux_' . hash('sha256', $_csrfToken) . '.tok';
    if (file_exists($_tokFile)) $_authed = true;
}
if (!$_authed) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

$action = $_GET['action'] ?? '';

// ════════════════════════════════════════════════════════════════════════════════
// ACTION: synonyms
// ════════════════════════════════════════════════════════════════════════════════
if ($action === 'synonyms') {
    $term    = trim($_body['term'] ?? '');
    $options = $_body['options'] ?? [];

    if ($term === '') {
        echo json_encode(['success' => false, 'error' => 'Kein Begriff angegeben']);
        exit;
    }

    $variants = buildVariants($term, $options);
    $synonyms = [];

    // KI-Synonyme via OpenAI (wenn aktiviert und Key vorhanden)
    if (!empty($options['ai_synonyms']) && CFG_OPENAI_KEY !== '') {
        $synonyms = fetchAiSynonyms($term);
    }

    echo json_encode([
        'success'  => true,
        'term'     => $term,
        'variants' => $variants,
        'synonyms' => $synonyms,
    ]);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════
// ACTION: crawl_url
// ════════════════════════════════════════════════════════════════════════════════
if ($action === 'crawl_url') {
    $url      = trim($_body['url'] ?? '');
    $termList = $_body['term_list'] ?? [];  // [{term, variants:[{text,type}]}]
    $options  = $_body['options'] ?? [];

    // URL-Validierung
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
        echo json_encode(['success' => false, 'error' => 'Ungültige URL: ' . htmlspecialchars($url)]);
        exit;
    }

    // ── Node.js Content-Extraktion via Puppeteer ─────────────────────────────
    $ocrFlag    = !empty($options['ocr']) ? '--ocr' : '';
    $scriptPath = dirname(__DIR__, 2) . '/app/contentfinder_extract.mjs';
    // Nicht gefunden? Versuche relativer Pfad
    if (!file_exists($scriptPath)) {
        $scriptPath = __DIR__ . '/../contentfinder_extract.mjs';
    }
    $nodeCmd  = trim((string)shell_exec('which node 2>/dev/null')) ?: '/usr/bin/node';

    $blocks         = [];
    $imgOcrCandidates = [];
    $crawlError     = null;

    if (file_exists($scriptPath) && file_exists($nodeCmd)) {
        $cmd = 'timeout 45 ' . escapeshellarg($nodeCmd)
            . ' ' . escapeshellarg($scriptPath)
            . ' ' . escapeshellarg($url)
            . ($ocrFlag ? ' ' . $ocrFlag : '')
            . ' 2>/dev/null';
        $jsonOutput = '';
        exec($cmd, $outLines, $exitCode);
        $jsonOutput = implode('', $outLines);
        if ($exitCode === 0 && $jsonOutput) {
            $data = json_decode($jsonOutput, true);
            if (is_array($data)) {
                $blocks           = $data['blocks'] ?? [];
                $imgOcrCandidates = $data['img_ocr_candidates'] ?? [];
            } else {
                $crawlError = 'Ungültiger JSON-Output vom Extraktor';
            }
        } else {
            $crawlError = 'Puppeteer-Extraktion fehlgeschlagen (Exit: ' . $exitCode . ')';
        }
    } else {
        // Fallback: statisches HTML via cURL
        $blocks     = fetchHtmlFallback($url);
        $crawlError = 'Puppeteer nicht verfügbar — statisches HTML verwendet';
    }

    // ── Bild-OCR via OpenAI Vision ────────────────────────────────────────────
    if (!empty($options['ocr']) && CFG_OPENAI_KEY !== '' && !empty($imgOcrCandidates)) {
        foreach ($imgOcrCandidates as $candidate) {
            $tmpFile = $candidate['tmpFile'] ?? '';
            $imgSrc  = $candidate['src']     ?? '';
            if (!$tmpFile || !file_exists($tmpFile)) continue;
            $ocrText = runImageOcr($tmpFile, $imgSrc);
            if ($ocrText) {
                $blocks[] = ['type' => 'Bild-OCR', 'text' => $ocrText, 'src' => $imgSrc];
            }
            @unlink($tmpFile);
        }
    } elseif (!empty($imgOcrCandidates)) {
        // Temporäre Dateien aufräumen auch wenn OCR deaktiviert
        foreach ($imgOcrCandidates as $c) {
            if (!empty($c['tmpFile']) && file_exists($c['tmpFile'])) @unlink($c['tmpFile']);
        }
    }

    // ── Begriff-Suche ─────────────────────────────────────────────────────────
    $hits = searchBlocks($blocks, $termList, $options);

    echo json_encode([
        'success'      => true,
        'url'          => $url,
        'hits'         => $hits,
        'blocks_count' => count($blocks),
        'warning'      => $crawlError,
        'stats'        => [
            'blocks'    => count($blocks),
            'hits'      => count($hits),
            'ocr_used'  => !empty($options['ocr']) && CFG_OPENAI_KEY !== '',
        ],
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unbekannte Action: ' . htmlspecialchars($action)]);
exit;

// ════════════════════════════════════════════════════════════════════════════════
// HILFSFUNKTIONEN
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Generiert alle regelbasierten Varianten eines Suchbegriffs.
 * Kein API-Call — rein algorithmisch.
 */
function buildVariants(string $term, array $options = []): array
{
    $term = trim(preg_replace('/\s+/', ' ', $term));
    if ($term === '') return [];

    $umlautToAscii = ['ö' => 'oe', 'ä' => 'ae', 'ü' => 'ue', 'ß' => 'ss',
                      'Ö' => 'Oe', 'Ä' => 'Ae', 'Ü' => 'Ue'];

    $results  = [];
    $seen     = [];

    // Closure statt nested function (vermeidet Cannot-redeclare bei Mehrfachaufruf)
    $add = function(string $text, string $type) use (&$results, &$seen): void {
        $key = mb_strtolower($text);
        if (isset($seen[$key]) || $text === '') return;
        $seen[$key] = true;
        $results[]  = ['text' => $text, 'type' => $type];
    };

    // Basis-Formen: original + Leerzeichen↔Bindestrich
    $forms = [$term];
    $hasSpace  = str_contains($term, ' ');
    $hasDash   = str_contains($term, '-');
    if ($hasSpace) $forms[] = str_replace(' ', '-', $term);
    if ($hasDash)  $forms[] = str_replace('-', ' ', $term);

    foreach ($forms as $i => $form) {
        $type = $i === 0 ? 'exact' : 'variant';
        $add($form, $type);

        // Umlaut → ASCII
        if (!empty($options['umlauts']) ?? true) {
            $ua = strtr($form, $umlautToAscii);
            if ($ua !== $form) $add($ua, 'variant');

            // Bindestrich-Variante mit Umlaut-Konvertierung
            if ($hasSpace) {
                $uaDash = strtr(str_replace(' ', '-', $form), $umlautToAscii);
                $add($uaDash, 'variant');
            }
        }

        // Plural-Formen
        if (!empty($options['plural'])) {
            foreach (['en', 'e', 'n', 'er', 's'] as $sfx) {
                $add($form . $sfx, 'variant');
                $ua = strtr($form . $sfx, $umlautToAscii);
                if ($ua !== $form . $sfx) $add($ua, 'variant');
            }
            // Singular aus Plural
            foreach (['en', 'e', 'n'] as $sfx) {
                if (mb_strtolower(mb_substr($form, -mb_strlen($sfx))) === $sfx && mb_strlen($form) > mb_strlen($sfx) + 3) {
                    $sing = mb_substr($form, 0, -mb_strlen($sfx));
                    $add($sing, 'variant');
                }
            }
        }
    }

    return $results;
}

/**
 * OpenAI-Synonyme für einen Begriff abrufen.
 */
function fetchAiSynonyms(string $term): array
{
    $payload = [
        'model'      => CFG_OPENAI_MODEL ?: 'gpt-4.1',
        'max_tokens' => 200,
        'messages'   => [[
            'role'    => 'user',
            'content' => 'Gib mir für den deutschen Begriff "' . $term . '" eine JSON-Liste mit 5–8 Synonymen und eng verwandten Fachbegriffen (keine Schreibvarianten, nur echte Synonyme/verwandte Konzepte). Antworte NUR mit einem JSON-Array von Strings, z.B. ["Synonym1","Begriff2"]. Keine Erklärungen.',
        ]],
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . CFG_OPENAI_KEY,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if (!$resp) return [];
    $data    = json_decode($resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    // Extrahiere JSON-Array aus der Antwort
    if (preg_match('/\[.*\]/s', $content, $m)) {
        $arr = json_decode($m[0], true);
        if (is_array($arr)) {
            return array_map(fn($s) => ['text' => trim((string)$s), 'type' => 'synonym'], array_filter($arr, 'is_string'));
        }
    }
    return [];
}

/**
 * Statischer HTML-Fallback: cURL + DOMDocument wenn Puppeteer nicht verfügbar.
 */
function fetchHtmlFallback(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ContentFinder/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_MAXREDIRS      => 5,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    if (!$html) return [];

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    $blocks = [];
    $seen   = [];

    $tagTypeMap = [
        'title' => 'Meta-Title', 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3',
        'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
        'p' => 'Absatz', 'li' => 'Liste', 'td' => 'Tabelle', 'th' => 'Tabelle',
        'button' => 'Button', 'a' => 'Link', 'label' => 'Label',
    ];

    foreach ($tagTypeMap as $tag => $type) {
        foreach ($doc->getElementsByTagName($tag) as $el) {
            $text = trim(preg_replace('/\s+/', ' ', $el->textContent ?? ''));
            if ($text === '' || mb_strlen($text) < 2) continue;
            $key = $type . '|' . mb_strtolower($text);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $blocks[] = ['type' => $type, 'text' => $text, 'src' => null];
        }
    }

    // Meta description
    foreach ($doc->getElementsByTagName('meta') as $m) {
        if (strtolower($m->getAttribute('name') ?? '') === 'description') {
            $c = trim($m->getAttribute('content') ?? '');
            if ($c) $blocks[] = ['type' => 'Meta-Description', 'text' => $c, 'src' => null];
        }
    }

    // Alt-Texte
    foreach ($doc->getElementsByTagName('img') as $img) {
        $alt = trim($img->getAttribute('alt') ?? '');
        $src = trim($img->getAttribute('src') ?? '');
        if ($alt) $blocks[] = ['type' => 'Bild-Alt', 'text' => $alt, 'src' => $src ?: null];
    }

    return $blocks;
}

/**
 * OpenAI Vision: Text aus einem Bild extrahieren.
 */
function runImageOcr(string $filePath, string $imgSrc): string
{
    if (!file_exists($filePath)) return '';
    $imgData = base64_encode(file_get_contents($filePath));
    if (!$imgData) return '';

    $payload = [
        'model'      => 'gpt-4o',
        'max_tokens' => 300,
        'messages'   => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Extrahiere alle lesbaren Texte und Zahlen aus diesem Bild. Antworte mit dem reinen Text, keine Erklärungen. Falls kein Text vorhanden ist, antworte mit "KEIN TEXT".'],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . $imgData, 'detail' => 'low']],
            ],
        ]],
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . CFG_OPENAI_KEY,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if (!$resp) return '';
    $data    = json_decode($resp, true);
    $content = trim($data['choices'][0]['message']['content'] ?? '');
    if ($content === 'KEIN TEXT' || $content === '' || stripos($content, 'kein text') !== false) return '';
    return $content;
}

/**
 * Durchsucht alle Content-Blöcke nach allen Termen und ihren Varianten.
 * Gibt Treffer mit Kontext zurück.
 */
function searchBlocks(array $blocks, array $termList, array $options = []): array
{
    if (empty($blocks) || empty($termList)) return [];

    $partialMatch = !empty($options['partial']);
    $caseSensitive = !empty($options['case_sensitive']);
    $flags = 'u' . ($caseSensitive ? '' : 'i');

    $hits = [];
    $hitKeys = []; // Duplikate vermeiden

    foreach ($termList as $termData) {
        $term     = $termData['term'] ?? '';
        $variants = $termData['variants'] ?? [];

        foreach ($variants as $variantData) {
            $varText = $variantData['text'] ?? '';
            $varType = $variantData['type'] ?? 'exact';
            if ($varText === '') continue;

            // Regex aufbauen
            $escaped = preg_quote($varText, '/');
            // Teilwort-Treffer: kein Word-Boundary
            // Ganzes-Wort-Treffer: word boundary
            if ($partialMatch) {
                $pattern = '/' . $escaped . '/' . $flags;
            } else {
                // Unicode word boundary (\b funktioniert mit u-Flag für ASCII, für Umlaute Lookaround)
                $pattern = '/(?<![a-zA-ZÀ-ÖØ-öø-ÿ])' . $escaped . '(?![a-zA-ZÀ-ÖØ-öø-ÿ])/' . $flags;
            }

            foreach ($blocks as $block) {
                $text = $block['text'] ?? '';
                if ($text === '') continue;

                if (@preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE) === 1) {
                    $pos      = $match[0][1];
                    $matched  = $match[0][0];

                    // Kontext: ~100 Zeichen um die Fundstelle
                    $contextStart = max(0, $pos - 80);
                    $contextEnd   = min(strlen($text), $pos + strlen($matched) + 80);
                    $context      = substr($text, $contextStart, $contextEnd - $contextStart);
                    if ($contextStart > 0) $context = '…' . $context;
                    if ($contextEnd < strlen($text)) $context .= '…';

                    // Duplikat-Check: selbe URL+Term+Variante+Kontext
                    $hitKey = md5($term . '|' . $varText . '|' . $block['type'] . '|' . $context);
                    if (isset($hitKeys[$hitKey])) continue;
                    $hitKeys[$hitKey] = true;

                    $hits[] = [
                        'term'     => $term,
                        'variant'  => $varText,
                        'type'     => $varType,
                        'location' => $block['type'] ?? 'Text',
                        'context'  => $context,
                        'matched'  => $matched,
                        'img_src'  => $block['src'] ?? null,
                    ];
                }
            }
        }
    }

    return $hits;
}
