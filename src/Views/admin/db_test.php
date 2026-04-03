<?php
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create a dummy table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, val TEXT)");
    echo "✅ SQLite is working perfectly!";
}
catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}