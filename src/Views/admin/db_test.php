<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

try {
    $pdo = \Core\DatabaseManager::getConnection();

    // Create a dummy table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, val TEXT)");
    echo "✅ SQLite is working perfectly!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
