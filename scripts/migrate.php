<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\DatabaseManager;
use Core\DatabaseMigrator;

try {
    $pdo = DatabaseManager::getConnection();
    $migrator = new DatabaseMigrator($pdo);
    $migrator->migrate(false); // false = not silent, output to CLI
    exit(0);
} catch (\Throwable $e) {
    echo "Fatal Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
