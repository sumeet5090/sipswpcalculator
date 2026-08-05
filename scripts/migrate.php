<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\DatabaseMigrator;

try {
    $app = new \Core\App();
    $container = $app->boot();
    $migrator = $container->get(DatabaseMigrator::class);
    $migrator->migrate();
    exit(0);
} catch (\Throwable $e) {
    echo "Fatal Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
