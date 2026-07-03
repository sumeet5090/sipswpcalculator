<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * DatabaseMigrator
 * Dedicated object to perform SQLite migrations (create tables, add missing schema columns).
 */
class DatabaseMigrator
{
    private string $dbPath;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
    }

    /**
     * Run all migrations in a CLI context.
     */
    public function migrate(): void
    {
        echo "--- Starting SQLite Database Migration ---\n";

        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            echo "Creating database directory: {$dir}...\n";
            mkdir($dir, 0755, true);
        }

        echo "Connecting to SQLite database: {$this->dbPath}...\n";
        $pdo = new PDO("sqlite:" . $this->dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Create base table if not exists
        echo "Verifying 'user_calculations' table...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                calc_type TEXT NOT NULL,
                amount REAL NOT NULL,
                duration INTEGER NOT NULL,
                step_up_pct REAL DEFAULT 0.0,
                currency TEXT DEFAULT 'INR',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 2. Perform table column existence validations & add missing columns
        $cols = $pdo->query("PRAGMA table_info(user_calculations)")->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = array_column($cols, 'name');

        $migrations = [
            'pdf_downloaded' => 'INTEGER DEFAULT 0',
            'referrer'       => 'TEXT',
            'interest_rate'  => 'REAL',
            'sip_amount'     => 'REAL',
            'sip_duration'   => 'INTEGER',
            'sip_step_up'    => 'REAL',
            'swp_enabled'    => 'INTEGER DEFAULT 0',
            'swp_withdrawal' => 'REAL',
            'swp_duration'   => 'INTEGER',
            'swp_step_up'    => 'REAL'
        ];

        foreach ($migrations as $col => $typeDefinition) {
            if (!in_array($col, $existingCols)) {
                echo "Adding missing column '{$col}' ({$typeDefinition})...\n";
                $pdo->exec("ALTER TABLE user_calculations ADD COLUMN {$col} {$typeDefinition}");
            } else {
                echo "Column '{$col}' exists: OK.\n";
            }
        }

        echo "--- SQLite Migration Completed Successfully ---\n";
    }
}
