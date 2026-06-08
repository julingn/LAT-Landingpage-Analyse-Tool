<?php
/**
 * router.php — Security-Router für PHP Built-in Server
 *
 * Blockiert direkten Browser-Zugriff auf sensitive Dateien.
 * Genutzt via: php -S 0.0.0.0:8080 -t . router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = '/' . ltrim((string)$uri, '/');

// ── Sensitive Pfade blockieren ───────────────────────────────────────────
$blockedPatterns = [
    '#/\.env(\..*)?$#i',              // .env, .env.local etc.
    '#/app/settings\.json$#i',        // Credentials-Fallback
    '#/app/config\.php$#i',           // Credential-Loader
    '#/app/gsc_domains\.json$#i',     // GSC-Domain-Konfiguration
    '#/app/proxies/#i',               // Proxies nie direkt aus dem Browser abrufbar
    '#/config/#i',                    // config/ Verzeichnis
    '#/nixpacks\.toml$#i',
    '#/Dockerfile$#i',
    '#/README\.md$#i',
    '#/composer\.(json|lock)$#i',
    '#/package(-lock)?\.json$#i',
    '#/\.git/#i',
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $uri)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo '403 Forbidden';
        exit;
    }
}

// ── Statische Dateien direkt ausliefern (CSS, JS, Bilder etc.) ──────────
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath) && !str_ends_with($uri, '.php')) {
    return false; // Built-in Server übernimmt
}

// ── PHP-Routing: Built-in Server übernimmt ──────────────────────────────
return false;
