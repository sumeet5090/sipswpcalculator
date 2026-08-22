<?php

declare(strict_types=1);

return new class implements \Core\Database\MigrationInterface {
    public function up(PDO $pdo, bool $silent = false): void
    {
        $cols = $pdo->query("PRAGMA table_info(user_calculations)")->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = array_column($cols, 'name');

        $newColumns = [
            'landing_path'            => "TEXT DEFAULT '/'",
            'referrer_category'       => "TEXT DEFAULT 'direct'",
            'utm_source'              => "TEXT DEFAULT NULL",
            'utm_medium'              => "TEXT DEFAULT NULL",
            'scroll_depth_pct'        => 'INTEGER DEFAULT 0',
            'dwell_time_seconds'      => 'INTEGER DEFAULT 0',
            'quick_answer_viewed'     => 'INTEGER DEFAULT 0',
            'faq_item_expanded'       => "TEXT DEFAULT 'none'",
            'glossary_term_clicked'   => "TEXT DEFAULT 'none'",
            'hud_shortcut_clicked'    => "TEXT DEFAULT 'none'",
            'active_studio_tab'       => "TEXT DEFAULT 'city_benchmark'",
            'strategy_starter_used'   => "TEXT DEFAULT 'none'",
            'guided_wizard_completed' => 'INTEGER DEFAULT 0',
            'stress_test_scenario'    => "TEXT DEFAULT 'none'",
            'city_benchmark_city'     => "TEXT DEFAULT 'none'",
            'scenario_diff_saved'     => 'INTEGER DEFAULT 0',
            'csv_exported'            => 'INTEGER DEFAULT 0',
            'qr_modal_opened'         => 'INTEGER DEFAULT 0',
            'tax_waterfall_opened'    => 'INTEGER DEFAULT 0',
            'goal_pledge_created'     => 'INTEGER DEFAULT 0',
            'internal_hub_clicked'    => "TEXT DEFAULT 'none'",
            'cwv_lcp_ms'              => 'INTEGER DEFAULT NULL',
            'cwv_cls'                 => 'REAL DEFAULT NULL',
            'cwv_inp_ms'              => 'INTEGER DEFAULT NULL',
            'connection_speed'        => 'TEXT DEFAULT NULL',
            'viewport_bucket'         => "TEXT DEFAULT 'desktop'",
        ];

        foreach ($newColumns as $colName => $colType) {
            if (!in_array($colName, $existingCols, true)) {
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

        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_landing_path ON user_calculations(landing_path)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_referrer_category ON user_calculations(referrer_category)");
    }
};
