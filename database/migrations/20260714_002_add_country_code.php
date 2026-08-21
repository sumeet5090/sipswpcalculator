<?php

declare(strict_types=1);

return new class implements \Core\Database\MigrationInterface {
    public function up(PDO $pdo, bool $silent = false): void
    {
        $cols = $pdo->query("PRAGMA table_info(user_calculations)")->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = array_column($cols, 'name');

        if (!in_array('country_code', $existingCols)) {
            if (!$silent) {
                echo "Adding column 'country_code'...\n";
            }
            $pdo->exec("ALTER TABLE user_calculations ADD COLUMN country_code TEXT");
        } else {
            if (!$silent) {
                echo "Column 'country_code' already exists.\n";
            }
        }
    }
};
