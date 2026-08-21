<?php

declare(strict_types=1);

return new class implements \Core\Database\MigrationInterface {
    public function up(PDO $pdo, bool $silent = false): void
    {
        $cols = $pdo->query("PRAGMA table_info(user_calculations)")->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = array_column($cols, 'name');

        $newColumns = [
            'pdf_has_custom_name' => 'INTEGER DEFAULT 0',
            'inflation_enabled'   => 'INTEGER DEFAULT 0',
            'interaction_count'   => 'INTEGER DEFAULT 1',
            'preset_clicked'      => "TEXT DEFAULT 'none'",
            'exit_action'         => "TEXT DEFAULT 'calc_only'",
        ];

        foreach ($newColumns as $colName => $colType) {
            if (!in_array($colName, $existingCols)) {
                if (!$silent) {
                    echo "Adding column '{$colName}'...\n";
                }
                $pdo->exec("ALTER TABLE user_calculations ADD COLUMN {$colName} {$colType}");
            } else {
                if (!$silent) {
                    echo "Column '{$colName}' already exists.\n";
                }
            }
        }
    }
};
