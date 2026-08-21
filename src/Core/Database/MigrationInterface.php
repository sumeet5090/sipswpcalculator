<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;

/**
 * MigrationInterface
 * Formal contract for all database schema migration classes.
 */
interface MigrationInterface
{
    /**
     * Apply the migration up transformations.
     *
     * @param PDO $pdo Active database connection
     * @param bool $silent Suppress console output if true
     * @return void
     */
    public function up(PDO $pdo, bool $silent = false): void;
}
