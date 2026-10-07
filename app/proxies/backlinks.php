<?php
/**
 * backlinks.php — Backlink-Monitor Proxy
 *
 * Verwaltung, technische Prüfung, Scoring und Monitoring von Backlinks → mvv.de.
 * Persistenz: PostgreSQL (app/db.php). SISTRIX wird ausschließlich über die
 * bestehende sistrix.php angesprochen — der Frontend-Flow übergibt die SISTRIX-
 * Daten hier als Teil des Request-Bodys (keine zweite SISTRIX-Integration).
 *
 * Actions (via ?action=...):
 *   list       GET/POST → alle Backlinks (kompakt, inkl. Primär-Link)
 *   detail     POST { id } → ein Backlink mit allen MVV-Links + letzter Diff
 *   add        POST { url } → URL anlegen + sofort prüfen
 *   import     POST multipart { file } → XLSX/CSV einlesen, URLs als „pending" anlegen
 *   check      POST { id, sistrix? } → (Re-)Check: prüfen, scoren, speichern, Diff
 *   delete     POST { id } → Backlink löschen
 *   export     GET  → CSV-Download aller Backlinks
 */

declare(strict_types=1);

session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}
$sessionCsrf = $_SESSION['csrf_token'] ?? '';
session_write_close();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? '';

/** Export schreibt eigene Header; alle anderen Actions liefern JSON. */
if ($action !== 'export') {
    header('Content-Type: application/json; charset=utf-8');
}

function jsonOut(array $data): void { echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function jsonErr(string $msg, int $code = 400): void { http_response_code($code); jsonOut(['error' => $msg]); }

// ── CSRF für schreibende JSON-Actions ───────────────────────────────────────
$jsonBody = [];
if ($action !== 'export' && $action !== 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
}
function requireCsrf(array $body, string $expected): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? '');
    if (!$expected || $token !== $expected) { jsonErr('CSRF-Token ungültig', 403); }
}

// ── DB sicherstellen ────────────────────────────────────────────────────────
try {
    if (!db_available()) { jsonErr('Keine Datenbank konfiguriert. Bitte in Railway einen PostgreSQL-Dienst hinzufügen (DATABASE_URL).', 503); }
    db_init();
} catch (Throwable $e) {
    jsonErr('Datenbankfehler: ' . $e->getMessage(), 503);
}

/* ============================================================================
 *  Helpers: Fetch / Analyse / Scoring
 * ==========================================================================*/

/** Nur mvv.de und www.mvv.de zählen als MVV (keine Subdomains). */
function blIsMvvHost(string $host): bool {
    $host = strtolower(trim($host));
    return $host === 'mvv.de' || $host === 'www.mvv.de';
}

function blHost(string $url): string {
    $h = parse_url($url, PHP_URL_HOST);
    return $h ? strtolower($h) : '';
}

/** PostgreSQL-Boolean (via PDO oft 't'/'f') robust auswerten. */
function pgBool(mixed $v): bool {
    return $v === true || $v === 't' || $v === '1' || $v === 1 || $v === 'true';
}

/** Relative URL gegen Basis auflösen. */
function blResolveUrl(string $rel, string $base): string {
    $rel = trim($rel);
    if ($rel === '') return $base;
    if (preg_match('#^https?://#i', $rel)) return $rel;
    $b = parse_url($base);
    $scheme = $b['scheme'] ?? 'https';
    $host   = $b['host'] ?? '';
    if (str_starts_with($rel, '//')) return $scheme . ':' . $rel;
    if (str_starts_with($rel, '/'))  return $scheme . '://' . $host . $rel;
    $path = $b['path'] ?? '/';
    $dir  = preg_replace('#/[^/]*$#', '/', $path);
    return $scheme . '://' . $host . $dir . $rel;
}

/** HTML-Tags grob entfernen und Text normalisieren. */
function blCleanText(string $html): string {
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);
    $txt  = preg_replace('#<[^>]+>#', ' ', $html);
    $txt  = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_replace('/\s+/u', ' ', $txt);
}

/**
 * Ruft die URL ab und folgt Weiterleitungen manuell, um den Verlauf zu erfassen.
 * @return array{chain:array,final_url:string,status:int,body:string,error:string,headers:string}
 */
function blFetch(string $url, int $maxRedirects = 6): array {
    $UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    $chain   = [];
    $current = $url;

    for ($i = 0; $i <= $maxRedirects; $i++) {
        $ch = curl_init($current);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => $UA,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => ['Accept-Language: de-DE,de;q=0.9,en;q=0.8'],
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hsz  = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $code === 0) {
            $chain[] = ['url' => $current, 'status' => 0];
            return ['chain' => $chain, 'final_url' => $current, 'status' => 0, 'body' => '', 'error' => $err ?: 'Nicht erreichbar', 'headers' => ''];
        }

        $headers = substr($resp, 0, $hsz);
        $body    = substr($resp, $hsz);
        $chain[] = ['url' => $current, 'status' => $code];

        if ($code >= 300 && $code < 400 && $i < $maxRedirects
            && preg_match('/^location:\s*(.+)$/im', $headers, $m)) {
            $current = blResolveUrl(trim($m[1]), $current);
            continue;
        }

        return ['chain' => $chain, 'final_url' => $current, 'status' => $code, 'body' => $body, 'error' => '', 'headers' => $headers];
    }

    return ['chain' => $chain, 'final_url' => $current, 'status' => 0, 'body' => '', 'error' => 'Zu viele Weiterleitungen', 'headers' => ''];
}

/** Position eines Treffers im HTML grob einem Strukturbereich zuordnen. */
function blLinkPosition(string $body, int $offset): string {
    $prefix = substr($body, 0, $offset);
    $best = ''; $bestPos = -1;
    foreach (['footer', 'nav', 'aside', 'header'] as $sec) {
        $open = strripos($prefix, '<' . $sec);
        if ($open === false) continue;
        $close = strripos($prefix, '</' . $sec . '>');
        if ($close !== false && $close > $open) continue; // bereits geschlossen
        if ($open > $bestPos) { $bestPos = $open; $best = $sec; }
    }
    if ($best === 'footer') return 'Footer';
    if ($best === 'header') return 'Header';
    if ($best === 'nav')    return 'Navigation';
    if ($best === 'aside')  return 'Seitenleiste';
    return 'Content';
}

/** Analysiert den HTML-Body und liefert alle relevanten Kennzahlen. */
function blAnalyze(string $url, array $fetch): array {
    $body     = $fetch['body'];
    $finalUrl = $fetch['final_url'];
    $headers  = $fetch['headers'] ?? '';
    $out = [
        'page_title'    => '',
        'canonical_url' => '',
        'indexable'     => null,
        'meta_robots'   => '',
        'mvv_links'     => [],
        'mvv_in_text'   => false,
        'mvv_mentions'  => 0,
        'published_at'  => '',
        'modified_at'   => '',
    ];
    if ($body === '') return $out;

    // Titel
    if (preg_match('#<title[^>]*>(.*?)</title>#is', $body, $m)) {
        $out['page_title'] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    // Canonical
    if (preg_match('#<link[^>]+rel\s*=\s*("|\')canonical\1[^>]*>#i', $body, $m)) {
        if (preg_match('#href\s*=\s*("|\')(.*?)\1#i', $m[0], $hm)) {
            $out['canonical_url'] = blResolveUrl(trim(html_entity_decode($hm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')), $finalUrl);
        }
    }
    // meta robots + X-Robots-Tag-Header
    $robots = '';
    if (preg_match('#<meta[^>]+name\s*=\s*("|\')robots\1[^>]*>#i', $body, $m)
        && preg_match('#content\s*=\s*("|\')(.*?)\1#i', $m[0], $cm)) {
        $robots = strtolower(trim($cm[2]));
    }
    if ($robots === '' && preg_match('/^x-robots-tag:\s*(.+)$/im', $headers, $xm)) {
        $robots = strtolower(trim($xm[1]));
    }
    $out['meta_robots'] = $robots;
    $out['indexable']   = (strpos($robots, 'noindex') === false);

    // Datumsangaben (best effort: meta + JSON-LD)
    if (preg_match('#<meta[^>]+(?:property|name)\s*=\s*("|\')(?:article:published_time|datePublished|date)\1[^>]*content\s*=\s*("|\')(.*?)\2#i', $body, $m)) {
        $out['published_at'] = trim($m[3]);
    } elseif (preg_match('#"datePublished"\s*:\s*"([^"]+)"#i', $body, $m)) {
        $out['published_at'] = trim($m[1]);
    }
    if (preg_match('#<meta[^>]+(?:property|name)\s*=\s*("|\')(?:article:modified_time|dateModified)\1[^>]*content\s*=\s*("|\')(.*?)\2#i', $body, $m)) {
        $out['modified_at'] = trim($m[3]);
    } elseif (preg_match('#"dateModified"\s*:\s*"([^"]+)"#i', $body, $m)) {
        $out['modified_at'] = trim($m[1]);
    }

    // MVV im Fließtext
    $txt = blCleanText($body);
    $out['mvv_mentions'] = preg_match_all('/\bMVV\b/u', $txt);
    $out['mvv_in_text']  = $out['mvv_mentions'] > 0;

    // Alle MVV-Links finden
    if (preg_match_all('#<a\b([^>]*?)href\s*=\s*("|\')(.*?)\2([^>]*)>(.*?)</a>#is', $body, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($matches as $a) {
            $attrsBefore = $a[1][0];
            $href        = trim(html_entity_decode($a[3][0], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $attrsAfter  = $a[4][0];
            $inner       = $a[5][0];
            $offset      = $a[0][1];
            if ($href === '' || stripos($href, 'javascript:') === 0 || $href[0] === '#') continue;
            if (!preg_match('#^https?://#i', $href) && !str_starts_with($href, '/') && !str_starts_with($href, '//')) continue;
            $abs  = blResolveUrl($href, $finalUrl);
            $host = blHost($abs);
            if (!blIsMvvHost($host)) continue;

            $anchor = trim(blCleanText($inner));
            if ($anchor === '' && preg_match('#alt\s*=\s*("|\')(.*?)\1#i', $inner, $am)) {
                $anchor = trim($am[2]);
            }
            $relAttrs = $attrsBefore . ' ' . $attrsAfter;
            $rel = '';
            if (preg_match('#rel\s*=\s*("|\')(.*?)\1#i', $relAttrs, $rm)) {
                $rel = strtolower(trim($rm[2]));
            }
            $out['mvv_links'][] = [
                'target_url'    => $abs,
                'anchor_text'   => $anchor,
                'rel_attr'      => $rel,
                'link_position' => blLinkPosition($body, $offset),
            ];
        }
    }
    return $out;
}

/** Wählt den primären MVV-Link (bevorzugt Content + dofollow). */
function blPickPrimary(array $links): int {
    if (!$links) return -1;
    foreach ($links as $i => $l) {
        if ($l['link_position'] === 'Content' && strpos($l['rel_attr'], 'nofollow') === false) return $i;
    }
    foreach ($links as $i => $l) {
        if ($l['link_position'] === 'Content') return $i;
    }
    return 0;
}

/** Regelbasiertes Scoring (0–100) + Klasse, Risiko, Empfehlung. */
function blScore(array $state): array {
    $detail = [];
    $reachable = $state['reachable'];
    $links     = $state['mvv_links'];
    $primaryIdx = blPickPrimary($links);
    $primary   = $primaryIdx >= 0 ? $links[$primaryIdx] : null;

    if (!$reachable) {
        return [
            'score' => 0, 'quality_class' => 'D', 'risk_level' => 'hoch',
            'recommendation' => 'Quelle nicht erreichbar (HTTP ' . (int)$state['http_status'] . '). Backlink prüfen oder aus dem Monitoring entfernen.',
            'score_detail' => [['label' => 'Erreichbarkeit', 'points' => 0, 'max' => 100, 'note' => 'nicht erreichbar']],
        ];
    }

    $score = 0; $issues = [];

    // MVV-Link vorhanden (35)
    $hasLink = $primary !== null;
    $p = $hasLink ? 35 : 0;
    $score += $p;
    $detail[] = ['label' => 'MVV-Link vorhanden', 'points' => $p, 'max' => 35, 'note' => $hasLink ? 'ja' : 'kein Link auf mvv.de'];
    if (!$hasLink) $issues[] = 'no_link';

    // Linkattribut (20 / 6)
    if ($hasLink) {
        $isNofollow = preg_match('/\b(nofollow|ugc|sponsored)\b/', $primary['rel_attr']) === 1;
        $p = $isNofollow ? 6 : 20;
        $score += $p;
        $detail[] = ['label' => 'Linkattribut', 'points' => $p, 'max' => 20, 'note' => $isNofollow ? ($primary['rel_attr'] ?: 'nofollow') : 'dofollow'];
        if ($isNofollow) $issues[] = 'nofollow';
    } else {
        $detail[] = ['label' => 'Linkattribut', 'points' => 0, 'max' => 20, 'note' => '–'];
    }

    // Indexierbarkeit (15)
    $idx = $state['indexable'];
    $p = $idx === true ? 15 : ($idx === null ? 8 : 0);
    $score += $p;
    $detail[] = ['label' => 'Indexierbarkeit', 'points' => $p, 'max' => 15, 'note' => $idx === true ? 'indexierbar' : ($idx === null ? 'unbekannt' : 'noindex')];
    if ($idx === false) $issues[] = 'noindex';

    // Linkposition (12)
    if ($hasLink) {
        $pos = $primary['link_position'];
        $p = match ($pos) { 'Content' => 12, 'Navigation', 'Seitenleiste' => 6, 'Header' => 4, 'Footer' => 3, default => 6 };
        $score += $p;
        $detail[] = ['label' => 'Linkposition', 'points' => $p, 'max' => 12, 'note' => $pos];
        if (in_array($pos, ['Footer', 'Header', 'Seitenleiste'], true)) $issues[] = 'position';
    } else {
        $detail[] = ['label' => 'Linkposition', 'points' => 0, 'max' => 12, 'note' => '–'];
    }

    // MVV im Fließtext (8)
    $p = $state['mvv_in_text'] ? 8 : 0;
    $score += $p;
    $detail[] = ['label' => 'MVV im Fließtext', 'points' => $p, 'max' => 8, 'note' => $state['mvv_in_text'] ? ($state['mvv_mentions'] . '× genannt') : 'nicht genannt'];

    // Canonical (3)
    $canon = $state['canonical_url'];
    if ($canon !== '') {
        $consistent = blHost($canon) === blHost($state['final_url']);
        $p = $consistent ? 3 : 1;
        $detail[] = ['label' => 'Canonical', 'points' => $p, 'max' => 3, 'note' => $consistent ? 'konsistent' : 'fremde Domain'];
    } else {
        $p = 0;
        $detail[] = ['label' => 'Canonical', 'points' => 0, 'max' => 3, 'note' => 'keine'];
    }
    $score += $p;

    // SISTRIX-Domain-Autorität (7)
    $vis = (float)($state['sistrix_data']['visibility'] ?? 0);
    $p = 0;
    if ($vis >= 1)      $p = 7;
    elseif ($vis >= 0.1) $p = 5;
    elseif ($vis > 0)    $p = 3;
    $score += $p;
    $detail[] = ['label' => 'Domain-Autorität (SISTRIX)', 'points' => $p, 'max' => 7, 'note' => $vis > 0 ? ('Sichtbarkeit ' . $vis) : 'keine Daten'];

    $score = max(0, min(100, $score));

    $class = $score >= 80 ? 'A' : ($score >= 60 ? 'B' : ($score >= 40 ? 'C' : 'D'));
    $risk  = (!$hasLink) ? 'hoch' : ($score >= 70 ? 'niedrig' : ($score >= 45 ? 'mittel' : 'hoch'));

    // Empfehlung
    $rec = [];
    if (in_array('no_link', $issues, true)) $rec[] = 'Kein Link auf mvv.de gefunden — Backlink verloren oder entfernt. Kontakt zur Quelle prüfen.';
    if (in_array('nofollow', $issues, true)) $rec[] = 'Link ist nofollow/ugc/sponsored — kein direkter SEO-Wert; ggf. dofollow anfragen.';
    if (in_array('noindex', $issues, true)) $rec[] = 'Quellseite ist nicht indexierbar (noindex) — Linkwert eingeschränkt.';
    if (in_array('position', $issues, true)) $rec[] = 'Link in Footer/Header/Seitenleiste — geringere Relevanz als ein Content-Link.';
    if (!$rec) $rec[] = 'Backlink in Ordnung — keine Maßnahme nötig.';

    return ['score' => $score, 'quality_class' => $class, 'risk_level' => $risk, 'recommendation' => implode(' ', $rec), 'score_detail' => $detail];
}

/* ============================================================================
 *  Persistenz-Helfer
 * ==========================================================================*/

function blRowById(int $id): ?array {
    $st = db()->prepare('SELECT * FROM bl_backlinks WHERE id = :id');
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

function blLinksByBacklink(int $id): array {
    $st = db()->prepare('SELECT target_url, anchor_text, rel_attr, link_position, is_primary FROM bl_mvv_links WHERE backlink_id = :id ORDER BY is_primary DESC, id ASC');
    $st->execute([':id' => $id]);
    return $st->fetchAll();
}

/** Baut ein kompaktes Snapshot-Array für Diffs. */
function blSnapshot(array $row, array $links): array {
    $primary = null;
    foreach ($links as $l) { if (!empty($l['is_primary'])) { $primary = $l; break; } }
    if (!$primary && $links) $primary = $links[0];
    $idx = $row['indexable'];
    $idxStr = $idx === null ? 'unbekannt' : (pgBool($idx) ? 'indexierbar' : 'noindex');
    return [
        'http_status'   => (int)$row['http_status'],
        'final_url'     => $row['final_url'],
        'reachable'     => pgBool($row['reachable']),
        'has_mvv_link'  => pgBool($row['has_mvv_link']),
        'mvv_target'    => $primary['target_url'] ?? '',
        'anchor_text'   => $primary['anchor_text'] ?? '',
        'rel_attr'      => $primary['rel_attr'] ?? '',
        'link_position' => $primary['link_position'] ?? '',
        'indexable'     => $idxStr,
        'canonical_url' => $row['canonical_url'],
        'page_title'    => $row['page_title'],
        'published_at'  => $row['published_at'],
        'modified_at'   => $row['modified_at'],
        'score'         => (int)$row['score'],
        'quality_class' => $row['quality_class'],
        'risk_level'    => $row['risk_level'],
    ];
}

/** Vergleicht zwei Snapshots und liefert die Änderungen. */
function blDiff(array $old, array $new): array {
    $labels = [
        'http_status' => 'HTTP-Status', 'final_url' => 'Finale URL', 'reachable' => 'Erreichbarkeit',
        'has_mvv_link' => 'MVV-Link vorhanden', 'mvv_target' => 'MVV-Ziel-URL', 'anchor_text' => 'Ankertext',
        'rel_attr' => 'Linkattribut', 'link_position' => 'Linkposition', 'indexable' => 'Indexierbarkeit',
        'canonical_url' => 'Canonical-URL', 'page_title' => 'Seitentitel', 'published_at' => 'Veröffentlicht',
        'modified_at' => 'Aktualisiert', 'score' => 'Qualitätsscore', 'quality_class' => 'Qualitätsklasse',
        'risk_level' => 'Risikostufe',
    ];
    $changes = [];
    foreach ($labels as $key => $label) {
        $o = $old[$key] ?? null; $n = $new[$key] ?? null;
        $os = is_bool($o) ? ($o ? 'ja' : 'nein') : (string)$o;
        $ns = is_bool($n) ? ($n ? 'ja' : 'nein') : (string)$n;
        if ($os !== $ns) $changes[] = ['field' => $label, 'old' => $os, 'new' => $ns];
    }
    return $changes;
}

/**
 * Führt die vollständige Prüfung eines Backlinks aus: Fetch, Analyse, Scoring,
 * Speichern, Historie + Diff. $sistrix enthält optionale SISTRIX-Domaindaten.
 */
function blProcess(int $id, array $sistrix): array {
    $row = blRowById($id);
    if (!$row) throw new RuntimeException('Backlink nicht gefunden');

    $oldLinks = blLinksByBacklink($id);
    $oldSnap  = blSnapshot($row, $oldLinks);

    $fetch   = blFetch($row['source_url']);
    $analyze = blAnalyze($row['source_url'], $fetch);

    $reachable = $fetch['status'] >= 200 && $fetch['status'] < 400 && $fetch['body'] !== '';
    $links     = $analyze['mvv_links'];
    $primaryIdx = blPickPrimary($links);

    $state = [
        'http_status'  => $fetch['status'],
        'reachable'    => $reachable,
        'final_url'    => $fetch['final_url'],
        'indexable'    => $analyze['indexable'],
        'canonical_url'=> $analyze['canonical_url'],
        'mvv_links'    => $links,
        'mvv_in_text'  => $analyze['mvv_in_text'],
        'mvv_mentions' => $analyze['mvv_mentions'],
        'sistrix_data' => $sistrix,
    ];
    $scored = blScore($state);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(<<<SQL
            UPDATE bl_backlinks SET
                source_domain = :dom, final_url = :final, http_status = :status, reachable = :reach,
                redirect_chain = :chain, page_title = :title, canonical_url = :canon, indexable = :idx,
                meta_robots = :robots, has_mvv_link = :haslink, mvv_link_count = :linkcount,
                mvv_in_text = :intext, mvv_mentions = :mentions, published_at = :pub, modified_at = :mod,
                sistrix_data = :sistrix, score = :score, quality_class = :class, risk_level = :risk,
                recommendation = :rec, score_detail = :detail, checked_at = now()
            WHERE id = :id
        SQL);
        $upd->execute([
            ':dom' => blHost($row['source_url']), ':final' => $fetch['final_url'], ':status' => $fetch['status'],
            ':reach' => $reachable ? 1 : 0, ':chain' => json_encode($fetch['chain']), ':title' => $analyze['page_title'],
            ':canon' => $analyze['canonical_url'],
            ':idx' => $analyze['indexable'] === null ? null : ($analyze['indexable'] ? 1 : 0),
            ':robots' => $analyze['meta_robots'], ':haslink' => $links ? 1 : 0, ':linkcount' => count($links),
            ':intext' => $analyze['mvv_in_text'] ? 1 : 0, ':mentions' => $analyze['mvv_mentions'],
            ':pub' => $analyze['published_at'], ':mod' => $analyze['modified_at'],
            ':sistrix' => json_encode($sistrix), ':score' => $scored['score'], ':class' => $scored['quality_class'],
            ':risk' => $scored['risk_level'], ':rec' => $scored['recommendation'],
            ':detail' => json_encode($scored['score_detail']), ':id' => $id,
        ]);

        $pdo->prepare('DELETE FROM bl_mvv_links WHERE backlink_id = :id')->execute([':id' => $id]);
        if ($links) {
            $ins = $pdo->prepare('INSERT INTO bl_mvv_links (backlink_id, target_url, anchor_text, rel_attr, link_position, is_primary) VALUES (:bid, :url, :anchor, :rel, :pos, :prim)');
            foreach ($links as $i => $l) {
                $ins->execute([
                    ':bid' => $id, ':url' => $l['target_url'], ':anchor' => $l['anchor_text'],
                    ':rel' => $l['rel_attr'], ':pos' => $l['link_position'], ':prim' => $i === $primaryIdx ? 1 : 0,
                ]);
            }
        }

        $newRow   = blRowById($id);
        $newLinks = blLinksByBacklink($id);
        $newSnap  = blSnapshot($newRow, $newLinks);
        $changes  = $oldSnap['http_status'] === 0 && $oldSnap['score'] === 0 && !$row['checked_at'] ? [] : blDiff($oldSnap, $newSnap);

        $pdo->prepare('INSERT INTO bl_checks (backlink_id, snapshot, changes) VALUES (:id, :snap, :chg)')
            ->execute([':id' => $id, ':snap' => json_encode($newSnap), ':chg' => json_encode($changes)]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['row' => blRowById($id), 'links' => blLinksByBacklink($id), 'changes' => $changes ?? [], 'fetch_error' => $fetch['error']];
}

/** URL anlegen (falls neu), liefert die id. */
function blInsertUrl(string $url): array {
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return ['id' => 0, 'new' => false, 'error' => 'Ungültige URL'];
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, ['http', 'https'], true)) return ['id' => 0, 'new' => false, 'error' => 'Nur HTTP/HTTPS erlaubt'];

    $st = db()->prepare('SELECT id FROM bl_backlinks WHERE source_url = :u');
    $st->execute([':u' => $url]);
    $existing = $st->fetch();
    if ($existing) return ['id' => (int)$existing['id'], 'new' => false, 'error' => ''];

    $ins = db()->prepare('INSERT INTO bl_backlinks (source_url, source_domain) VALUES (:u, :d) RETURNING id');
    $ins->execute([':u' => $url, ':d' => blHost($url)]);
    return ['id' => (int)$ins->fetchColumn(), 'new' => true, 'error' => ''];
}

/* ============================================================================
 *  XLSX / CSV einlesen
 * ==========================================================================*/

/** Liest URLs aus einer hochgeladenen XLSX- oder CSV-Datei. */
function blParseUpload(string $tmpPath, string $name): array {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $urls = [];
    if ($ext === 'csv' || $ext === 'txt') {
        $fh = fopen($tmpPath, 'r');
        if ($fh) {
            while (($line = fgets($fh)) !== false) {
                foreach (preg_split('/[;,\t]/', $line) as $cell) {
                    $cell = trim($cell, " \t\r\n\"'");
                    if (preg_match('#^https?://#i', $cell)) $urls[] = $cell;
                }
            }
            fclose($fh);
        }
    } elseif ($ext === 'xlsx') {
        if (!class_exists('ZipArchive')) return [];
        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) return [];
        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $x = @simplexml_load_string($ss);
            if ($x) foreach ($x->si as $si) {
                $t = '';
                if (isset($si->t)) $t = (string)$si->t;
                if (isset($si->r)) foreach ($si->r as $r) $t .= (string)$r->t;
                $shared[] = $t;
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet !== false && ($sx = @simplexml_load_string($sheet))) {
            foreach ($sx->sheetData->row as $row) {
                foreach ($row->c as $c) {
                    $type = (string)$c['t']; $v = (string)$c->v;
                    if ($type === 's') $v = $shared[(int)$v] ?? '';
                    elseif ($type === 'inlineStr') $v = (string)$c->is->t;
                    $v = trim($v);
                    if (preg_match('#^https?://#i', $v)) $urls[] = $v;
                }
            }
        }
    }
    // Dedupe, Reihenfolge erhalten
    return array_values(array_unique($urls));
}

/* ============================================================================
 *  Router
 * ==========================================================================*/

if ($action === 'list') {
    $rows = db()->query('SELECT * FROM bl_backlinks ORDER BY created_at DESC, id DESC')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $links = blLinksByBacklink((int)$r['id']);
        $primary = null;
        foreach ($links as $l) { if (!empty($l['is_primary'])) { $primary = $l; break; } }
        if (!$primary && $links) $primary = $links[0];
        $out[] = [
            'id' => (int)$r['id'], 'source_url' => $r['source_url'], 'source_domain' => $r['source_domain'],
            'http_status' => (int)$r['http_status'], 'reachable' => pgBool($r['reachable']),
            'has_mvv_link' => pgBool($r['has_mvv_link']), 'mvv_link_count' => (int)$r['mvv_link_count'],
            'primary_target' => $primary['target_url'] ?? '', 'primary_anchor' => $primary['anchor_text'] ?? '',
            'score' => (int)$r['score'], 'quality_class' => $r['quality_class'], 'risk_level' => $r['risk_level'],
            'checked_at' => $r['checked_at'],
        ];
    }
    jsonOut(['success' => true, 'backlinks' => $out]);
}

if ($action === 'detail') {
    $id = (int)($jsonBody['id'] ?? 0);
    $row = blRowById($id);
    if (!$row) jsonErr('Backlink nicht gefunden', 404);
    $lastCheck = db()->prepare('SELECT checked_at, changes FROM bl_checks WHERE backlink_id = :id ORDER BY id DESC LIMIT 1');
    $lastCheck->execute([':id' => $id]);
    $lc = $lastCheck->fetch();
    jsonOut([
        'success' => true,
        'backlink' => $row,
        'mvv_links' => blLinksByBacklink($id),
        'last_check' => $lc ? ['checked_at' => $lc['checked_at'], 'changes' => json_decode($lc['changes'], true)] : null,
    ]);
}

if ($action === 'add') {
    requireCsrf($jsonBody, $sessionCsrf);
    $ins = blInsertUrl($jsonBody['url'] ?? '');
    if ($ins['error']) jsonErr($ins['error']);
    try {
        $result = blProcess($ins['id'], is_array($jsonBody['sistrix'] ?? null) ? $jsonBody['sistrix'] : []);
    } catch (Throwable $e) {
        jsonErr('Prüfung fehlgeschlagen: ' . $e->getMessage(), 500);
    }
    jsonOut(['success' => true, 'id' => $ins['id'], 'new' => $ins['new'], 'result' => $result]);
}

if ($action === 'check') {
    requireCsrf($jsonBody, $sessionCsrf);
    $id = (int)($jsonBody['id'] ?? 0);
    if (!$id) jsonErr('id fehlt');
    try {
        $result = blProcess($id, is_array($jsonBody['sistrix'] ?? null) ? $jsonBody['sistrix'] : []);
    } catch (Throwable $e) {
        jsonErr('Prüfung fehlgeschlagen: ' . $e->getMessage(), 500);
    }
    jsonOut(['success' => true, 'id' => $id, 'result' => $result]);
}

if ($action === 'import') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!$sessionCsrf || $token !== $sessionCsrf) jsonErr('CSRF-Token ungültig', 403);
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) jsonErr('Keine gültige Datei hochgeladen');
    $urls = blParseUpload($_FILES['file']['tmp_name'], $_FILES['file']['name']);
    if (!$urls) jsonErr('Keine URLs in der Datei gefunden (Spalte mit http(s)-URLs erwartet).');
    $added = []; $skipped = 0;
    foreach ($urls as $u) {
        $r = blInsertUrl($u);
        if ($r['id'] && $r['new']) $added[] = ['id' => $r['id'], 'url' => $u];
        elseif ($r['id']) $skipped++;
    }
    jsonOut(['success' => true, 'added' => $added, 'skipped' => $skipped, 'total' => count($urls)]);
}

if ($action === 'delete') {
    requireCsrf($jsonBody, $sessionCsrf);
    $id = (int)($jsonBody['id'] ?? 0);
    if (!$id) jsonErr('id fehlt');
    db()->prepare('DELETE FROM bl_backlinks WHERE id = :id')->execute([':id' => $id]);
    jsonOut(['success' => true]);
}

if ($action === 'export') {
    $rows = db()->query('SELECT * FROM bl_backlinks ORDER BY created_at DESC, id DESC')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="backlinks_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM für Excel
    fputcsv($out, ['Quell-URL', 'Domain', 'HTTP-Status', 'Finale URL', 'MVV-Link', 'MVV-Links', 'Indexierbar', 'Score', 'Klasse', 'Risiko', 'Empfehlung', 'Geprüft am'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['source_url'], $r['source_domain'], $r['http_status'], $r['final_url'],
            pgBool($r['has_mvv_link']) ? 'ja' : 'nein',
            $r['mvv_link_count'],
            $r['indexable'] === null ? '?' : (pgBool($r['indexable']) ? 'ja' : 'nein'),
            $r['score'], $r['quality_class'], $r['risk_level'], $r['recommendation'], $r['checked_at'],
        ], ';');
    }
    fclose($out);
    exit;
}

jsonErr('Unbekannte Action', 404);
