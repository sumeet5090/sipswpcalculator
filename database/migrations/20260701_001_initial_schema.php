<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo, bool $silent = false): void
    {
        if (!$silent) {
            echo "Creating 'user_calculations' table...\n";
        }
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                calc_type TEXT NOT NULL,
                amount REAL NOT NULL,
                duration INTEGER NOT NULL,
                step_up_pct REAL DEFAULT 0.0,
                currency TEXT DEFAULT 'INR',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $cols = $pdo->query("PRAGMA table_info(user_calculations)")->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = array_column($cols, 'name');

        $migrations = [
            'pdf_downloaded' => 'INTEGER DEFAULT 0',
            'referrer'       => 'TEXT',
            'interest_rate'  => 'REAL',
            'sip_amount'     => 'REAL',
            'sip_duration'   => 'INTEGER',
            'sip_step_up'    => 'REAL',
            'swp_enabled'    => 'INTEGER DEFAULT 0',
            'swp_withdrawal' => 'REAL',
            'swp_duration'   => 'INTEGER',
            'swp_step_up'    => 'REAL'
        ];

        foreach ($migrations as $col => $typeDefinition) {
            if (!in_array($col, $existingCols)) {
                if (!$silent) {
                    echo "Adding column '{$col}'...\n";
                }
                $pdo->exec("ALTER TABLE user_calculations ADD COLUMN {$col} {$typeDefinition}");
            }
        }

        if (!$silent) {
            echo "Creating indexes...\n";
        }
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_created_at ON user_calculations(created_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_currency ON user_calculations(currency)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_type ON user_calculations(calc_type)");
    }
};
