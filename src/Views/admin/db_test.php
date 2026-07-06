<?php

$dbPath = $_ENV['DB_PATH'] ?? getenv('DB_PATH') ?: __DIR__ . '/../../../database/database.sqlite';

try {
    $dir = dirname($dbPath);
    if (!file_exists($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!file_exists($dbPath)) {
        @touch($dbPath);
        @chmod($dbPath, 0664);
    }
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create a dummy table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, val TEXT)");
    echo "✅ SQLite is working perfectly!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
