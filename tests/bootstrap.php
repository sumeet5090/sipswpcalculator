<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Force environment DB_PATH to database/database.test.sqlite
$testDb = __DIR__ . '/../database/database.test.sqlite';
putenv('DB_PATH=' . $testDb);
$_ENV['DB_PATH'] = $testDb;

// Run migrations on the test database before running tests
try {
    $app = new \Core\App();
    $container = $app->boot();
    /** @var \Core\DatabaseMigrator $migrator */
    $migrator = $container->get(\Core\DatabaseMigrator::class);
    $migrator->migrate();
} catch (\Throwable $e) {
    fwrite(STDERR, "PHPUnit Bootstrap: Failed to migrate test database: " . $e->getMessage() . "\n");
    exit(1);
}

// Clean up the test database file after the PHPUnit process finishes
register_shutdown_function(function () use ($testDb) {
    if (file_exists($testDb) && !unlink($testDb)) {
        error_log("PHPUnit Bootstrap: Failed to clean up test database file at {$testDb}");
    }
});
