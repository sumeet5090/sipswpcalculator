<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\AdminDashboardPresenter;
use Core\AnonymizedInsightLogger;
use Core\InsightPayload;
use Core\InsightRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class SpecializedTelemetryTest extends TestCase
{
    private PDO $pdo;
    private AnonymizedInsightLogger $logger;
    private InsightRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec("
            CREATE TABLE user_calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                calc_type TEXT,
                amount REAL,
                duration INTEGER,
                step_up_pct REAL DEFAULT 0,
                currency TEXT DEFAULT 'INR',
                country_code TEXT,
                pdf_downloaded INTEGER DEFAULT 0,
                referrer TEXT,
                interest_rate REAL,
                sip_amount REAL,
                sip_duration INTEGER,
                sip_step_up REAL,
                swp_enabled INTEGER DEFAULT 0,
                swp_withdrawal REAL,
                swp_duration INTEGER,
                swp_step_up REAL,
                final_corpus REAL,
                total_invested REAL,
                wealth_multiplier REAL,
                goal_mode TEXT,
                device_type TEXT,
                table_viewed INTEGER DEFAULT 0,
                pdf_has_custom_name INTEGER DEFAULT 0,
                inflation_enabled INTEGER DEFAULT 0,
                interaction_count INTEGER DEFAULT 1,
                preset_clicked TEXT DEFAULT 'none',
                exit_action TEXT DEFAULT 'calc_only',
                landing_path TEXT DEFAULT '/',
                referrer_category TEXT DEFAULT 'direct',
                utm_source TEXT,
                utm_medium TEXT,
                scroll_depth_pct INTEGER DEFAULT 0,
                dwell_time_seconds INTEGER DEFAULT 0,
                quick_answer_viewed INTEGER DEFAULT 0,
                faq_item_expanded TEXT DEFAULT 'none',
                glossary_term_clicked TEXT DEFAULT 'none',
                hud_shortcut_clicked TEXT DEFAULT 'none',
                active_studio_tab TEXT DEFAULT 'city_benchmark',
                strategy_starter_used TEXT DEFAULT 'none',
                guided_wizard_completed INTEGER DEFAULT 0,
                stress_test_scenario TEXT DEFAULT 'none',
                city_benchmark_city TEXT DEFAULT 'none',
                scenario_diff_saved INTEGER DEFAULT 0,
                csv_exported INTEGER DEFAULT 0,
                qr_modal_opened INTEGER DEFAULT 0,
                tax_waterfall_opened INTEGER DEFAULT 0,
                goal_pledge_created INTEGER DEFAULT 0,
                internal_hub_clicked TEXT DEFAULT 'none',
                cwv_lcp_ms INTEGER,
                cwv_cls REAL,
                cwv_inp_ms INTEGER,
                connection_speed TEXT,
                viewport_bucket TEXT DEFAULT 'desktop',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->logger = new AnonymizedInsightLogger($this->pdo);
        $this->repository = new InsightRepository($this->pdo);
    }

    public function testSpecializedCalculatorsPayloadsLogAndAggregateCorrectly(): void
    {
        $specializedPayloads = [
            [
                'calc_type' => 'Compound Interest',
                'amount' => 500000.0,
                'duration' => 10,
                'interest_rate' => 12.0,
                'total_invested' => 500000.0,
                'final_corpus' => 1552924.0,
                'wealth_multiplier' => 3.11,
                'goal_mode' => 'compound_interest',
            ],
            [
                'calc_type' => 'CAGR',
                'amount' => 100000.0,
                'duration' => 5,
                'interest_rate' => 20.11,
                'total_invested' => 100000.0,
                'final_corpus' => 250000.0,
                'wealth_multiplier' => 2.5,
                'goal_mode' => 'cagr',
            ],
            [
                'calc_type' => 'EMI',
                'amount' => 3000000.0,
                'duration' => 20,
                'interest_rate' => 8.5,
                'sip_amount' => 26035.0,
                'total_invested' => 3000000.0,
                'final_corpus' => 6248316.0,
                'wealth_multiplier' => 2.08,
                'goal_mode' => 'emi',
            ],
            [
                'calc_type' => 'Inflation',
                'amount' => 50000.0,
                'duration' => 15,
                'interest_rate' => 6.0,
                'inflation_enabled' => 1,
                'total_invested' => 50000.0,
                'final_corpus' => 119828.0,
                'wealth_multiplier' => 2.4,
                'goal_mode' => 'inflation',
            ],
            [
                'calc_type' => 'PPF',
                'amount' => 150000.0,
                'duration' => 15,
                'interest_rate' => 7.1,
                'total_invested' => 2250000.0,
                'final_corpus' => 4068209.0,
                'wealth_multiplier' => 1.81,
                'goal_mode' => 'ppf',
            ],
            [
                'calc_type' => 'Fixed Deposit',
                'amount' => 500000.0,
                'duration' => 3,
                'interest_rate' => 7.5,
                'total_invested' => 500000.0,
                'final_corpus' => 624858.0,
                'wealth_multiplier' => 1.25,
                'goal_mode' => 'fd',
            ],
        ];

        foreach ($specializedPayloads as $raw) {
            $payload = InsightPayload::fromArray($raw);
            $this->logger->logCalculation($payload);
        }

        // Verify all 6 records were inserted into SQLite
        $count = (int) $this->pdo->query("SELECT COUNT(*) FROM user_calculations")->fetchColumn();
        $this->assertSame(6, $count);

        // Verify calc_types are recorded with exact names
        $calcTypes = $this->pdo->query("SELECT calc_type, COUNT(*) as cnt FROM user_calculations GROUP BY calc_type ORDER BY calc_type ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->assertArrayHasKey('Compound Interest', $calcTypes);
        $this->assertArrayHasKey('CAGR', $calcTypes);
        $this->assertArrayHasKey('EMI', $calcTypes);
        $this->assertArrayHasKey('Inflation', $calcTypes);
        $this->assertArrayHasKey('PPF', $calcTypes);
        $this->assertArrayHasKey('Fixed Deposit', $calcTypes);

        // Verify Dashboard Repository returns calcTypeBreakdown
        $data = $this->repository->getDashboardData(['interval' => '-30 days']);
        $this->assertArrayHasKey('calcTypeBreakdown', $data);
        $this->assertCount(6, $data['calcTypeBreakdown']);

        // Verify Presenter formats calcTypeLabels and calcTypeData for Data Island
        $presenter = new AdminDashboardPresenter();
        $viewData = $presenter->formatForView($data);

        $this->assertArrayHasKey('calcTypeLabels', $viewData);
        $this->assertArrayHasKey('calcTypeData', $viewData);
        $this->assertStringContainsString('Compound Interest', $viewData['calcTypeLabels']);
        $this->assertStringContainsString('Fixed Deposit', $viewData['calcTypeLabels']);
        $this->assertArrayHasKey('calcTypeLabels', $viewData['chartPayload']);
        $this->assertArrayHasKey('calcTypeData', $viewData['chartPayload']);
    }
}
