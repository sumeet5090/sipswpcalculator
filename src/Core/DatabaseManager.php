<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * DatabaseManager
 * Centralized service to establish SQLite database connection, resolve paths, and ensure directory/file exist.
 */
class DatabaseManager
{
    private static ?PDO $pdo = null;
    private static ?string $dbPath = null;

    /**
     * Get the configured database path.
     *
     * @return string
     */
    public static function getPath(): string
    {
        if (self::$dbPath === null) {
            self::$dbPath = $_ENV['DB_PATH'] ?? getenv('DB_PATH') ?: __DIR__ . '/../../database/database.sqlite';
        }

        return self::$dbPath;
    }

    /**
     * Get the PDO connection singleton.
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $path = self::getPath();

            $dir = dirname($path);
            if (!file_exists($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (!file_exists($path)) {
                @touch($path);
                @chmod($path, 0664);
            }

            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }
}
