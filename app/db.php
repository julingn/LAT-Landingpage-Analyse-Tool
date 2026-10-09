<?php
/**
 * db.php — PostgreSQL-Anbindung (PDO) für das Backlink-Monitor-Modul.
 *
 * LAT nutzt sonst file-basierte Persistenz; der Backlink-Monitor braucht
 * jedoch dauerhaften Speicher (Railway-FS ist ephemer). Dafür wird eine
 * PostgreSQL-Datenbank verwendet (Railway → PostgreSQL-Dienst → DATABASE_URL).
 *
 * db()       → gemeinsame PDO-Verbindung (Singleton)
 * db_init()  → legt die bl_*-Tabellen idempotent an (bei jedem Request sicher)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Liefert eine gemeinsame PDO-Verbindung (Singleton). */
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $url = cfg('DATABASE_URL', 'database_url');
    if (!$url) {
        throw new RuntimeException('DATABASE_URL ist nicht gesetzt. Bitte in Railway einen PostgreSQL-Dienst hinzufügen.');
    }

    $p = parse_url($url);
    if ($p === false || empty($p['host'])) {
        throw new RuntimeException('DATABASE_URL konnte nicht gelesen werden.');
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $p['host'],
        $p['port'] ?? 5432,
        ltrim($p['path'] ?? '', '/')
    );

    $pdo = new PDO($dsn, $p['user'] ?? '', rawurldecode($p['pass'] ?? ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/** Prüft, ob eine Datenbank konfiguriert ist (ohne zu verbinden). */
function db_available(): bool {
    return cfg('DATABASE_URL', 'database_url') !== '';
}

/** Legt die Tabellen des Backlink-Monitors idempotent an. */
function db_init(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = db();

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS bl_backlinks (
            id             SERIAL PRIMARY KEY,
            source_url     TEXT NOT NULL UNIQUE,
            source_domain  TEXT NOT NULL DEFAULT '',
            final_url      TEXT NOT NULL DEFAULT '',
            http_status    INT  NOT NULL DEFAULT 0,
            reachable      BOOLEAN NOT NULL DEFAULT FALSE,
            redirect_chain JSONB NOT NULL DEFAULT '[]'::jsonb,
            page_title     TEXT NOT NULL DEFAULT '',
            canonical_url  TEXT NOT NULL DEFAULT '',
            indexable      BOOLEAN,
            meta_robots    TEXT NOT NULL DEFAULT '',
            has_mvv_link   BOOLEAN NOT NULL DEFAULT FALSE,
            mvv_link_count INT  NOT NULL DEFAULT 0,
            mvv_in_text    BOOLEAN NOT NULL DEFAULT FALSE,
            mvv_mentions   INT  NOT NULL DEFAULT 0,
            published_at   TEXT NOT NULL DEFAULT '',
            modified_at    TEXT NOT NULL DEFAULT '',
            sistrix_data   JSONB NOT NULL DEFAULT '{}'::jsonb,
            score          INT  NOT NULL DEFAULT 0,
            quality_class  TEXT NOT NULL DEFAULT '',
            risk_level     TEXT NOT NULL DEFAULT '',
            recommendation TEXT NOT NULL DEFAULT '',
            score_detail   JSONB NOT NULL DEFAULT '{}'::jsonb,
            notes          TEXT NOT NULL DEFAULT '',
            created_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
            checked_at     TIMESTAMPTZ
        )
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS bl_mvv_links (
            id            SERIAL PRIMARY KEY,
            backlink_id   INT NOT NULL REFERENCES bl_backlinks(id) ON DELETE CASCADE,
            target_url    TEXT NOT NULL DEFAULT '',
            anchor_text   TEXT NOT NULL DEFAULT '',
            rel_attr      TEXT NOT NULL DEFAULT '',
            link_position TEXT NOT NULL DEFAULT '',
            is_primary    BOOLEAN NOT NULL DEFAULT FALSE
        )
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS bl_checks (
            id          SERIAL PRIMARY KEY,
            backlink_id INT NOT NULL REFERENCES bl_backlinks(id) ON DELETE CASCADE,
            checked_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
            snapshot    JSONB NOT NULL DEFAULT '{}'::jsonb,
            changes     JSONB NOT NULL DEFAULT '[]'::jsonb
        )
    SQL);

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bl_mvv_links_backlink ON bl_mvv_links(backlink_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bl_checks_backlink ON bl_checks(backlink_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bl_backlinks_domain ON bl_backlinks(source_domain)");

    // Quell-Wertigkeit (SISTRIX + DataForSEO, zusammengeführt) + thematische Treffer — nachträglich ergänzt.
    $pdo->exec("ALTER TABLE bl_backlinks ADD COLUMN IF NOT EXISTS source_metrics JSONB NOT NULL DEFAULT '{}'::jsonb");
    $pdo->exec("ALTER TABLE bl_backlinks ADD COLUMN IF NOT EXISTS thematic_hits JSONB NOT NULL DEFAULT '[]'::jsonb");
    // Datum aus dem Import („Backlink gesetzt", z. B. "09/2026").
    $pdo->exec("ALTER TABLE bl_backlinks ADD COLUMN IF NOT EXISTS link_set_date TEXT NOT NULL DEFAULT ''");
}
