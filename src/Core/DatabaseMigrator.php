<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * DatabaseMigrator
 * Dedicated object to perform SQLite migrations via timestamped migration files.
 */
class DatabaseMigrator
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(PDO $pdo, ?string $migrationsPath = null)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../database/migrations';
    }

    /**
     * Run all pending migrations.
     */
    public function migrate(bool $silent = false): void
    {
        if (!$silent) {
            echo "--- Starting SQLite Database Migration ---\n";
        }

        // 1. Ensure schema_migrations table exists
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                executed_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 2. Get already executed migrations
        $stmt = $this->pdo->query("SELECT migration FROM schema_migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 3. Scan migrations directory
        if (!is_dir($this->migrationsPath)) {
            if (!$silent) {
                echo "Migrations directory not found: {$this->migrationsPath}\n";
            }
            return;
        }

        $files = glob($this->migrationsPath . '/*.php');
        sort($files);

        $executedCount = 0;

        foreach ($files as $file) {
            $migrationName = basename($file);

            if (!in_array($migrationName, $executed)) {
                if (!$silent) {
                    echo "Migrating: {$migrationName}\n";
                }

                $migration = require $file;

                try {
                    $this->pdo->beginTransaction();
                    $migration->up($this->pdo, $silent);

                    $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (:migration)");
                    $stmt->execute([':migration' => $migrationName]);

                    $this->pdo->commit();
                    $executedCount++;

                    if (!$silent) {
                        echo "Migrated:  {$migrationName}\n";
                    }
                } catch (\Throwable $e) {
                    $this->pdo->rollBack();
                    if (!$silent) {
                        echo "Migration Failed: {$migrationName} - " . $e->getMessage() . "\n";
                    }
                    throw $e;
                }
            }
        }

        if ($executedCount === 0 && !$silent) {
            echo "Nothing to migrate.\n";
        }

        if (!$silent) {
            echo "--- SQLite Migration Completed Successfully ---\n";
        }
    }
}
