<?php

declare(strict_types=1);

namespace Services;

/**
 * TelemetryPruningServiceInterface
 * Contract for pruning expired telemetry analytics records.
 */
interface TelemetryPruningServiceInterface
{
    /**
     * Delete calculation records older than the retention period.
     *
     * @return int Number of pruned rows
     */
    public function pruneExpiredRecords(): int;

    /**
     * Opportunistic probabilistic prune (e.g., 1 in N requests).
     */
    public function opportunisticPrune(int $probabilityOneIn = 500): void;
}
