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

// Discover and load .env from current directory or parent release directories
$envDirs = array_filter([
    __DIR__,
    dirname(__DIR__),
    dirname(__DIR__, 2),
], 'is_dir');

foreach ($envDirs as $envDir) {
    if (file_exists($envDir . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable($envDir);
        $dotenv->safeLoad();
        break;
    }
}

try {
    $container = \Core\App::createContainer();
    $migrator = $container->get(\Core\DatabaseMigrator::class);
    $migrator->migrate();
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Fatal Error during database migration: " . $e->getMessage() . "\n");
    exit(1);
}
