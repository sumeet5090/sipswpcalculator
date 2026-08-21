<?php

declare(strict_types=1);

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use Services\TelemetryPruningService;

class TelemetryPruningServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("
            CREATE TABLE user_calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT
            )
        ");
    }

    public function testPrunesExpiredRecordsCorrectly(): void
    {
        // Insert expired record (> 180 days old)
        $this->pdo->exec("INSERT INTO user_calculations (created_at) VALUES (datetime('now', '-200 days'))");
        // Insert recent record (< 180 days old)
        $this->pdo->exec("INSERT INTO user_calculations (created_at) VALUES (datetime('now', '-10 days'))");

        $service = new TelemetryPruningService($this->pdo, 180);
        $pruned = $service->pruneExpiredRecords();

        $this->assertSame(1, $pruned);

        $remaining = (int) $this->pdo->query("SELECT COUNT(*) FROM user_calculations")->fetchColumn();
        $this->assertSame(1, $remaining);
    }
}
