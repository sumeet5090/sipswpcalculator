<?php
declare(strict_types=1);

/**
 * migrate.php
 * Root-level CLI utility script to trigger SQLite schema migrations in an OOP way.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Forbidden: CLI execution only.');
}

require_once __DIR__ . '/vendor/autoload.php';

$dbPath = __DIR__ . '/database/database.sqlite';
$migrator = new \Core\DatabaseMigrator($dbPath);

try {
    $migrator->migrate();
} catch (\Throwable $e) {
    fwrite(STDERR, "Fatal Error during database migration: " . $e->getMessage() . "\n");
    exit(1);
}
