<?php

declare(strict_types=1);

namespace Services;

use PDO;

/**
 * TelemetryPruningService
 * Dedicated maintenance service to prune expired telemetry records from the database.
 */
class TelemetryPruningService
{
    private PDO $pdo;
    private int $retentionDays;

    public function __construct(PDO $pdo, int $retentionDays = 180)
    {
        $this->pdo = $pdo;
        $this->retentionDays = max(1, $retentionDays);
    }

    /**
     * Delete calculation records older than the retention period.
     *
     * @return int Number of pruned rows
     */
    public function pruneExpiredRecords(): int
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_calculations WHERE created_at < datetime('now', :retention)");
            $stmt->execute([':retention' => "-{$this->retentionDays} days"]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log("TelemetryPruningService Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Opportunistic probabilistic prune (e.g., 1 in N requests).
     */
    public function opportunisticPrune(int $probabilityOneIn = 500): void
    {
        if (random_int(1, max(1, $probabilityOneIn)) === 1) {
            $this->pruneExpiredRecords();
        }
    }
}
