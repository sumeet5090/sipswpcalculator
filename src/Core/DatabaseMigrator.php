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
     * Ensure the migrations tracking table exists. Idempotent.
     * Separating this allows bootstrap without running migrations (e.g. tests).
     */
    public function bootstrap(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                executed_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Run all pending migrations.
     *
     * @return array<string> Names of newly executed migration files.
     */
    public function migrate(): array
    {
        $this->bootstrap();

        $stmt = $this->pdo->query("SELECT migration FROM schema_migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        sort($files);

        $executedMigrations = [];

        foreach ($files as $file) {
            $migrationName = basename($file);

            if (!in_array($migrationName, $executed, true)) {
                $migration = require $file;

                try {
                    $inTx = $this->pdo->inTransaction();
                    if (!$inTx) {
                        $this->pdo->beginTransaction();
                    }
                    $migration->up($this->pdo, true);

                    $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (:migration)");
                    $stmt->execute([':migration' => $migrationName]);

                    if (!$inTx && $this->pdo->inTransaction()) {
                        $this->pdo->commit();
                    }
                    $executedMigrations[] = $migrationName;
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    throw $e;
                }
            }
        }

        return $executedMigrations;
    }
}
