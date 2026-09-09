<?php
/**
 * knowledge.php — Proxy für das Modul „Wissensabdeckung & Chancen"
 *
 * Actions (POST, ?action= oder body.action):
 *   extract   → { url, html, role: 'own'|'competitor' }
 *               ← { entities:[], attributes:[], relationships:[] }
 *   analyze   → { own:{...}, competitors:[{...}], intersectionKeywords?:[] }
 *               ← { coverage:{...}, opportunities:[ {..., score, scoreDrivers[]} ] }
 *   briefing  → { opportunity:{...}, url? }
 *               ← { goal, audience, ... , _meta }
 *   generate  → { briefing:{...}, originalText? }
 *               ← { markdown, coveredEntities[], ..., verificationRequired[] }
 *
 * Persistenz: keine (erster Slice = In-Session + Export im Frontend).
 * Muster: Session-Guard + session_write_close(), Provider-Auswahl wie localpv.php.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => ['type' => 'method', 'message' => 'Method not allowed']]);
    exit;
}

// ── Session-Guard ─────────────────────────────────────────────────────────
session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => ['type' => 'unauthorized', 'message' => 'Nicht authentifiziert.']]);
    exit;
}
session_write_close(); // Lock sofort freigeben — KI-Calls dauern

require_once __DIR__ . '/../config.php';

// ── Input ─────────────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => ['type' => 'parse', 'message' => 'Ungültiger Request-Body (kein JSON).']]);
    exit;
}
$action = trim((string)($_GET['action'] ?? $body['action'] ?? ''));

// ── Provider-/Key-Auswahl (wie localpv.php) ───────────────────────────────
$provider = CFG_AI_PROVIDER; // 'anthropic' | 'openai'
if ($provider === 'openai') {
    $apiKey = CFG_OPENAI_KEY;
    if (empty($apiKey)) {
        http_response_code(503);
        echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein OpenAI API-Key hinterlegt. Bitte OPENAI_API_KEY setzen.']]);
        exit;
    }
} else {
    $apiKey = CFG_ANTHROPIC_KEY;
    if (empty($apiKey)) {
        if (!empty(CFG_OPENAI_KEY)) { $provider = 'openai'; $apiKey = CFG_OPENAI_KEY; }
        else {
            http_response_code(503);
            echo json_encode(['error' => ['type' => 'no_key', 'message' => 'Kein API-Key hinterlegt. Bitte ANTHROPIC_API_KEY oder OPENAI_API_KEY setzen.']]);
            exit;
        }
    }
}

// ── Helper: KI-Call (non-streaming), gibt Text zurück oder wirft Fehler ───
/** @return array{0:?string,1:?array} [text, error] */
function knowCallAi(string $provider, string $apiKey, string $systemPrompt, string $userPrompt, int $maxTokens): array {
    $model = ($provider === 'openai') ? CFG_OPENAI_MODEL : CFG_AI_MODEL;
    $maxTokens = max(256, min($maxTokens, 4096)); // api.php-Cap respektieren

    if ($provider === 'openai') {
        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
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
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        ]);
    } else {
        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'system'     => $systemPrompt,
            'messages'   => [['role' => 'user', 'content' => $userPrompt]],
        ];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'],
        ]);
    }

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($cerr) return [null, ['type' => 'curl', 'message' => 'Netzwerkfehler: ' . $cerr]];
    $data = json_decode((string)$resp, true);
    if ($code !== 200) {
        return [null, ['type' => 'api', 'message' => $data['error']['message'] ?? ('HTTP ' . $code)]];
    }
    // Antworttext extrahieren (Provider-abhängig)
    $text = ($provider === 'openai')
        ? ($data['choices'][0]['message']['content'] ?? '')
        : ($data['content'][0]['text'] ?? '');
    if ($text === '') return [null, ['type' => 'empty', 'message' => 'Leere KI-Antwort.']];
    return [$text, null];
}

// ── Helper: JSON aus KI-Text robust extrahieren ───────────────────────────
function knowParseJson(string $text): ?array {
    $t = trim($text);
    // Markdown-Codeblock-Fences entfernen
    $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
    $t = preg_replace('/\s*```$/', '', $t);
    $decoded = json_decode($t, true);
    if (is_array($decoded)) return $decoded;
    // Fallback: erstes {...}-Objekt herausschneiden
    $start = strpos($t, '{');
    $end   = strrpos($t, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($t, $start, $end - $start + 1), true);
        if (is_array($decoded)) return $decoded;
    }
    return null;
}

// ── Helper: HTML → Klartext (Token sparen, bessere Extraktion) ────────────
function knowHtmlToText(string $html, int $maxLen = 12000): string {
    // script/style/noscript entfernen
    $html = preg_replace('#<(script|style|noscript)[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    $text = trim($text);
    if (mb_strlen($text) > $maxLen) $text = mb_substr($text, 0, $maxLen);
    return $text;
}

// ── Helper: transparentes, gewichtetes Scoring einer Opportunity ──────────
function knowScoreOpportunity(array $factors): array {
    // Gewichte + lesbare Labels für Score-Treiber
    $weights = [
        'entityRelevance'       => ['w' => 1.0, 'label' => 'Themen-Relevanz'],
        'attributeRelevance'    => ['w' => 1.0, 'label' => 'Eigenschafts-Relevanz'],
        'relationshipRelevance' => ['w' => 1.0, 'label' => 'Zusammenhang-Relevanz'],
        'searchDemand'          => ['w' => 1.5, 'label' => 'Suchnachfrage'],
        'competitiveGap'        => ['w' => 1.5, 'label' => 'Wettbewerber-Lücke'],
        'ownCoverageGap'        => ['w' => 1.5, 'label' => 'Eigene Lücke'],
        'searchIntentFit'       => ['w' => 1.0, 'label' => 'Intent-Passung'],
        'businessRelevance'     => ['w' => 1.2, 'label' => 'Business-Relevanz'],
        'rankingPotential'      => ['w' => 1.0, 'label' => 'Ranking-Potenzial'],
        'conversionRelevance'   => ['w' => 1.2, 'label' => 'Conversion-Relevanz'],
        'localRelevance'        => ['w' => 0.8, 'label' => 'Lokale Relevanz'],
        'geoRelevance'          => ['w' => 0.8, 'label' => 'GEO/AI-Relevanz'],
        'effortInverse'         => ['w' => 0.8, 'label' => 'Geringer Aufwand'],
    ];
    $clamp = fn($v) => max(0.0, min(1.0, (float)$v));

    $effort     = $clamp($factors['effort'] ?? 0.5);
    $confidence = $clamp($factors['confidence'] ?? 0.5);

    $values = [
        'entityRelevance'       => $clamp($factors['entityRelevance']       ?? 0),
        'attributeRelevance'    => $clamp($factors['attributeRelevance']    ?? 0),
        'relationshipRelevance' => $clamp($factors['relationshipRelevance'] ?? 0),
        'searchDemand'          => $clamp($factors['searchDemand']          ?? 0),
        'competitiveGap'        => $clamp($factors['competitiveGap']        ?? 0),
        'ownCoverageGap'        => $clamp($factors['ownCoverageGap']        ?? 0),
        'searchIntentFit'       => $clamp($factors['searchIntentFit']       ?? 0),
        'businessRelevance'     => $clamp($factors['businessRelevance']     ?? 0),
        'rankingPotential'      => $clamp($factors['rankingPotential']      ?? 0),
        'conversionRelevance'   => $clamp($factors['conversionRelevance']   ?? 0),
        'localRelevance'        => $clamp($factors['localRelevance']        ?? 0),
        'geoRelevance'          => $clamp($factors['geoRelevance']          ?? 0),
        'effortInverse'         => 1.0 - $effort,
    ];

    $sumW = 0.0; $sumWF = 0.0; $drivers = [];
    foreach ($weights as $key => $meta) {
        $sumW  += $meta['w'];
        $sumWF += $meta['w'] * $values[$key];
    }
    $base  = $sumW > 0 ? ($sumWF / $sumW) : 0.0;      // 0..1 vor Confidence
    $score = (int)round(100 * $base * $confidence);    // Confidence dämpft

    // Score-Treiber: Beitrag jedes Faktors (Anteil am Gesamtscore)
    foreach ($weights as $key => $meta) {
        $contribution = $sumW > 0 ? round(100 * ($meta['w'] * $values[$key]) / $sumW * $confidence, 1) : 0.0;
        $drivers[] = [
            'factor'       => $key,
            'label'        => $meta['label'],
            'value'        => round($values[$key], 2),
            'weight'       => $meta['w'],
            'contribution' => $contribution,
        ];
    }
    usort($drivers, fn($a, $b) => $b['contribution'] <=> $a['contribution']);

    return ['score' => $score, 'confidence' => round($confidence, 2), 'scoreDrivers' => $drivers];
}

// ── Kompakte Textrepräsentation einer Extraktion (für analyze-Prompt) ─────
function knowExtractionSummary(array $ex): string {
    $lines = [];
    foreach (($ex['entities'] ?? []) as $e) {
        $syn = !empty($e['synonyms']) ? ' (' . implode(', ', (array)$e['synonyms']) . ')' : '';
        $lines[] = '- Thema: ' . ($e['prefLabel'] ?? '?') . $syn . ' [Abdeckung: ' . ($e['coverage'] ?? '?') . ']';
    }
    foreach (($ex['attributes'] ?? []) as $a) {
        $lines[] = '- Eigenschaft: ' . ($a['name'] ?? '?') . ' von ' . ($a['entity'] ?? '?') . ' [Abdeckung: ' . ($a['coverage'] ?? '?') . ']';
    }
    foreach (($ex['relationships'] ?? []) as $r) {
        $lines[] = '- Zusammenhang: ' . ($r['source'] ?? '?') . ' ' . ($r['predicate'] ?? '') . ' ' . ($r['target'] ?? '?') . ' [Abdeckung: ' . ($r['coverage'] ?? '?') . ']';
    }
    return $lines ? implode("\n", $lines) : '(keine Elemente erkannt)';
}

// ════════════════════════════════════════════════════════════════════════
//  ACTION-ROUTING
// ════════════════════════════════════════════════════════════════════════

// ── action=extract ────────────────────────────────────────────────────────
if ($action === 'extract') {
    $url  = trim((string)($body['url'] ?? ''));
    $html = (string)($body['html'] ?? '');
    $role = (($body['role'] ?? 'own') === 'competitor') ? 'competitor' : 'own';
    if ($html === '') {
        http_response_code(400);
        echo json_encode(['error' => ['type' => 'validation', 'message' => 'Kein Seiteninhalt (html) übergeben.']]);
        exit;
    }
    $text = knowHtmlToText($html);
    if (mb_strlen($text) < 40) {
        echo json_encode([
            'ok'       => true,
            'partial'  => true,
            'url'      => $url,
            'role'     => $role,
            'entities' => [], 'attributes' => [], 'relationships' => [],
            'note'     => 'In diesem Inhalt wurde zu wenig auswertbarer Text gefunden (evtl. clientseitig gerendert).',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $system = require __DIR__ . '/../prompts/knowledge_extract.php';
    $override = trim((string) cfg('LAT_AGENT_PROMPT_KNOWLEDGE_EXTRACT', 'agent_prompt_knowledge_extract', ''));
    if ($override !== '') $system = $override;

    $roleLabel = $role === 'competitor' ? 'Wettbewerberseite' : 'eigene Seite';
    $userPrompt = "Analysiere den folgenden Seitentext ({$roleLabel}, URL: {$url}).\n\nSEITENTEXT:\n{$text}";

    [$aiText, $err] = knowCallAi($provider, $apiKey, $system, $userPrompt, 3000);
    if ($err) { http_response_code(502); echo json_encode(['error' => $err], JSON_UNESCAPED_UNICODE); exit; }

    $parsed = knowParseJson($aiText);
    if ($parsed === null) {
        http_response_code(502);
        echo json_encode(['error' => ['type' => 'parse', 'message' => 'KI-Antwort konnte nicht als JSON gelesen werden.']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'ok'            => true,
        'url'           => $url,
        'role'          => $role,
        'entities'      => $parsed['entities']      ?? [],
        'attributes'    => $parsed['attributes']    ?? [],
        'relationships' => $parsed['relationships'] ?? [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action=analyze ────────────────────────────────────────────────────────
if ($action === 'analyze') {
    $own         = is_array($body['own'] ?? null) ? $body['own'] : null;
    $competitors = is_array($body['competitors'] ?? null) ? $body['competitors'] : [];
    $intersect   = is_array($body['intersectionKeywords'] ?? null) ? $body['intersectionKeywords'] : [];
    if ($own === null || empty($competitors)) {
        http_response_code(400);
        echo json_encode(['error' => ['type' => 'validation', 'message' => 'Eigene Extraktion und mindestens ein Wettbewerber erforderlich.']]);
        exit;
    }

    $ownSummary = knowExtractionSummary($own);
    $compBlocks = [];
    foreach ($competitors as $i => $comp) {
        if (!is_array($comp)) continue;
        $label = trim((string)($comp['url'] ?? ('Wettbewerber ' . ($i + 1))));
        $compBlocks[] = "WETTBEWERBER (" . $label . "):\n" . knowExtractionSummary($comp);
    }
    $compText = implode("\n\n", $compBlocks);

    $searchBlock = '';
    if (!empty($intersect)) {
        $kwLines = [];
        foreach (array_slice($intersect, 0, 25) as $kw) {
            if (is_array($kw)) {
                $k = $kw['keyword'] ?? '';
                $v = isset($kw['search_volume']) ? (' — ' . (int)$kw['search_volume'] . '/Monat') : '';
                if ($k !== '') $kwLines[] = '- ' . $k . $v;
            } elseif (is_string($kw)) {
                $kwLines[] = '- ' . $kw;
            }
        }
        if ($kwLines) $searchBlock = "\n\nGEMEINSAME RANKING-KEYWORDS / SUCHSIGNALE (belegen echte Suchnachfrage):\n" . implode("\n", $kwLines);
    }

    $system = require __DIR__ . '/../prompts/knowledge_analyze.php';
    $override = trim((string) cfg('LAT_AGENT_PROMPT_KNOWLEDGE_ANALYZE', 'agent_prompt_knowledge_analyze', ''));
    if ($override !== '') $system = $override;

    $userPrompt = "EIGENE SEITE:\n{$ownSummary}\n\n{$compText}{$searchBlock}\n\n"
        . "Vergleiche die Abdeckung und leite nachvollziehbare Content-Chancen ab. Antworte nur mit dem JSON-Objekt.";

    [$aiText, $err] = knowCallAi($provider, $apiKey, $system, $userPrompt, 4096);
    if ($err) { http_response_code(502); echo json_encode(['error' => $err], JSON_UNESCAPED_UNICODE); exit; }

    $parsed = knowParseJson($aiText);
    if ($parsed === null) {
        http_response_code(502);
        echo json_encode(['error' => ['type' => 'parse', 'message' => 'KI-Antwort konnte nicht als JSON gelesen werden.']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Transparentes Scoring in PHP (nicht im LLM)
    $opps = [];
    foreach (($parsed['opportunities'] ?? []) as $idx => $opp) {
        if (!is_array($opp)) continue;
        $factors = is_array($opp['factors'] ?? null) ? $opp['factors'] : [];
        $scored  = knowScoreOpportunity($factors);
        $opp['id']           = 'opp_' . ($idx + 1);
        $opp['score']        = $scored['score'];
        $opp['confidence']   = $scored['confidence'];
        $opp['scoreDrivers'] = $scored['scoreDrivers'];
        $opp['status']       = 'offen';
        $opps[] = $opp;
    }
    // Nach Score absteigend sortieren (Priorisierung)
    usort($opps, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

    echo json_encode([
        'ok'            => true,
        'coverage'      => $parsed['coverage'] ?? ['entities' => [], 'attributes' => [], 'relationships' => []],
        'opportunities' => $opps,
        'usedSearchSignals' => !empty($intersect),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action=briefing ───────────────────────────────────────────────────────
if ($action === 'briefing') {
    $opp = is_array($body['opportunity'] ?? null) ? $body['opportunity'] : null;
    $url = trim((string)($body['url'] ?? ''));
    if ($opp === null) {
        http_response_code(400);
        echo json_encode(['error' => ['type' => 'validation', 'message' => 'Keine Opportunity übergeben.']]);
        exit;
    }
    $system = require __DIR__ . '/../prompts/knowledge_briefing.php';
    $override = trim((string) cfg('LAT_AGENT_PROMPT_KNOWLEDGE_BRIEFING', 'agent_prompt_knowledge_briefing', ''));
    if ($override !== '') $system = $override;

    $oppJson = json_encode($opp, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $userPrompt = "Betroffene URL: " . ($url !== '' ? $url : '(neuer Content)') . "\n\nOPPORTUNITY:\n{$oppJson}\n\nErstelle das Content-Briefing. Antworte nur mit dem JSON-Objekt.";

    [$aiText, $err] = knowCallAi($provider, $apiKey, $system, $userPrompt, 2500);
    if ($err) { http_response_code(502); echo json_encode(['error' => $err], JSON_UNESCAPED_UNICODE); exit; }

    $parsed = knowParseJson($aiText);
    if ($parsed === null) {
        http_response_code(502);
        echo json_encode(['error' => ['type' => 'parse', 'message' => 'KI-Antwort konnte nicht als JSON gelesen werden.']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $parsed['ok'] = true;
    echo json_encode($parsed, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action=generate ───────────────────────────────────────────────────────
if ($action === 'generate') {
    $briefing = is_array($body['briefing'] ?? null) ? $body['briefing'] : null;
    $original = trim((string)($body['originalText'] ?? ''));
    if ($briefing === null) {
        http_response_code(400);
        echo json_encode(['error' => ['type' => 'validation', 'message' => 'Kein Briefing übergeben.']]);
        exit;
    }
    $system = require __DIR__ . '/../prompts/knowledge_generate.php';
    $override = trim((string) cfg('LAT_AGENT_PROMPT_KNOWLEDGE_GENERATE', 'agent_prompt_knowledge_generate', ''));
    if ($override !== '') $system = $override;

    $briefingJson = json_encode($briefing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $origBlock = $original !== ''
        ? "\n\nBESTEHENDER AUSGANGSTEXT (optimieren statt neu anlegen):\n{$original}"
        : '';
    $formatMap = [
        'section'    => 'Fließtext-Abschnitt mit H2/H3 und Absätzen',
        'faq'        => 'FAQ-Block: 3–6 Frage-Antwort-Paare (Frage als ###, Antwort als Absatz)',
        'table'      => 'Vergleichstabelle als Markdown-Tabelle mit sinnvollen Spalten',
        'infobox'    => 'kompakte Infobox: kurze Einleitung + Stichpunkt-Liste',
        'definition' => 'prägnante Definition (2–4 Sätze) + optional kurze Ergänzung',
        'howto'      => 'Prozessbeschreibung als nummerierte Schritt-für-Schritt-Liste',
    ];
    $format = trim((string)($body['format'] ?? ''));
    $formatBlock = ($format !== '' && isset($formatMap[$format]))
        ? "\n\nGEWÜNSCHTES FORMAT: {$formatMap[$format]}."
        : '';
    $userPrompt = "CONTENT-BRIEFING:\n{$briefingJson}{$origBlock}{$formatBlock}\n\nErstelle den Content-Baustein. Antworte nur mit dem JSON-Objekt.";

    [$aiText, $err] = knowCallAi($provider, $apiKey, $system, $userPrompt, 3500);
    if ($err) { http_response_code(502); echo json_encode(['error' => $err], JSON_UNESCAPED_UNICODE); exit; }

    $parsed = knowParseJson($aiText);
    if ($parsed === null) {
        http_response_code(502);
        echo json_encode(['error' => ['type' => 'parse', 'message' => 'KI-Antwort konnte nicht als JSON gelesen werden.']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $parsed['ok'] = true;
    echo json_encode($parsed, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action=schema ──────────────────────────────────────────────────────
// Schlägt strukturiertes Daten-Markup (schema.org JSON-LD) zur Opportunity/Briefing vor.
if ($action === 'schema') {
    $opp      = is_array($body['opportunity'] ?? null) ? $body['opportunity'] : null;
    $briefing = is_array($body['briefing'] ?? null) ? $body['briefing'] : null;
    if ($opp === null && $briefing === null) {
        http_response_code(400);
        echo json_encode(['error' => ['type' => 'validation', 'message' => 'Keine Opportunity oder Briefing übergeben.']]);
        exit;
    }
    $system = require __DIR__ . '/../prompts/knowledge_schema.php';
    $override = trim((string) cfg('LAT_AGENT_PROMPT_KNOWLEDGE_SCHEMA', 'agent_prompt_knowledge_schema', ''));
    if ($override !== '') $system = $override;

    $ctx = json_encode(['opportunity' => $opp, 'briefing' => $briefing], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $userPrompt = "KONTEXT:\n{$ctx}\n\nSchlage passendes schema.org-Markup vor. Antworte nur mit dem JSON-Objekt.";

    [$aiText, $err] = knowCallAi($provider, $apiKey, $system, $userPrompt, 2000);
    if ($err) { http_response_code(502); echo json_encode(['error' => $err], JSON_UNESCAPED_UNICODE); exit; }

    $parsed = knowParseJson($aiText);
    if ($parsed === null) {
        http_response_code(502);
        echo json_encode(['error' => ['type' => 'parse', 'message' => 'KI-Antwort konnte nicht als JSON gelesen werden.']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $parsed['ok'] = true;
    echo json_encode($parsed, JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['error' => ['type' => 'action', 'message' => 'Unbekannte action: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8')]]);
