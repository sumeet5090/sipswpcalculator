<?php

declare(strict_types=1);

/**
 * SIP/SWP Calculator - Database Migration Utility
 *
 * Safely adds missing columns (pdf_downloaded, referrer) to the existing SQLite database.
 * Usage: Run via CLI `php migrate_db.php` or access via browser.
 */

// 1. Basic security check (prevent accidental public execution if possible)
// On production, you might want to add a password check or IP whitelist here.

require_once __DIR__ . '/../../AnonymizedInsightLogger.php';

$dbPath = __DIR__ . '/../../../database/database.sqlite';

echo "--- SIP/SWP Database Migration ---\n";

if (!file_exists($dbPath)) {
    echo "Error: Database file not found at $dbPath\n";
    exit(1);
}

try {
    echo "Connecting to database...\n";

    // The AnonymizedInsightLogger constructor automatically runs migrations
    $logger = new AnonymizedInsightLogger($dbPath);

    echo "Schema validation triggered via Logger.\n";

    // Double-verify columns exist (manual fallback check)
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cols = $pdo->query("PRAGMA table_info(user_calculations)")->fetchAll(PDO::FETCH_ASSOC);
    $existingCols = array_column($cols, 'name');

    $required = ['pdf_downloaded', 'referrer'];
    foreach ($required as $col) {
        if (!in_array($col, $existingCols)) {
            echo "Column '$col' is still missing. Attempting manual ALTER TABLE...\n";
            $type = ($col === 'pdf_downloaded') ? "INTEGER DEFAULT 0" : "TEXT";
            $pdo->exec("ALTER TABLE user_calculations ADD COLUMN $col $type");
            echo "Successfully added $col.\n";
        } else {
            echo "Column '$col' verified: OK.\n";
        }
    }

    echo "\nMigration COMPLETED successfully.\n";
} catch (\Throwable $e) {
    echo "\nFATAL ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}
