<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\InsightRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class InsightRepositoryTest extends TestCase
{
    private PDO $pdo;
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
                step_up_pct REAL,
                currency TEXT,
                interest_rate REAL,
                sip_amount REAL,
                sip_duration INTEGER,
                sip_step_up REAL,
                swp_enabled INTEGER,
                swp_withdrawal REAL,
                swp_duration INTEGER,
                swp_step_up REAL,
                final_corpus REAL,
                total_invested REAL,
                wealth_multiplier REAL,
                goal_mode TEXT,
                device_type TEXT,
                table_viewed INTEGER,
                pdf_downloaded INTEGER,
                pdf_has_custom_name INTEGER,
                inflation_enabled INTEGER,
                interaction_count INTEGER,
                preset_clicked TEXT,
                exit_action TEXT,
                referrer TEXT,
                landing_path TEXT,
                referrer_category TEXT,
                utm_source TEXT,
                utm_medium TEXT,
                scroll_depth_pct INTEGER,
                dwell_time_seconds INTEGER,
                quick_answer_viewed INTEGER,
                faq_item_expanded TEXT,
                glossary_term_clicked TEXT,
                hud_shortcut_clicked TEXT,
                active_studio_tab TEXT,
                strategy_starter_used TEXT,
                guided_wizard_completed INTEGER,
                stress_test_scenario TEXT,
                city_benchmark_city TEXT,
                scenario_diff_saved INTEGER,
                csv_exported INTEGER,
                qr_modal_opened INTEGER,
                tax_waterfall_opened INTEGER,
                goal_pledge_created INTEGER,
                internal_hub_clicked TEXT,
                cwv_lcp_ms INTEGER,
                cwv_cls REAL,
                cwv_inp_ms INTEGER,
                connection_speed TEXT,
                viewport_bucket TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->repository = new InsightRepository($this->pdo);
    }

    public function testGetDashboardDataReturnsValidStructure(): void
    {
        $range = [
            'label' => '24 Hours',
            'interval' => '-1 day',
            'unit' => 'hour',
            'cte_start' => '-23 hours',
        ];

        $data = $this->repository->getDashboardData($range);

        $this->assertArrayHasKey('totalCalculations', $data);
        $this->assertArrayHasKey('avgStepUpPct', $data);
        $this->assertArrayHasKey('totalAllTime', $data);
        $this->assertArrayHasKey('calcTypeBreakdown', $data);
        $this->assertArrayHasKey('totalPdfDownloads', $data);
        $this->assertArrayHasKey('conversionRate', $data);
        $this->assertArrayHasKey('dailyVolume', $data);
        $this->assertArrayHasKey('currencyDist', $data);
        $this->assertArrayHasKey('deviceDist', $data);
        $this->assertArrayHasKey('goalModeDist', $data);
        $this->assertArrayHasKey('tableViewEngagement', $data);
        $this->assertArrayHasKey('avgFinalCorpus', $data);
        $this->assertArrayHasKey('avgWealthMultiplier', $data);
        $this->assertArrayHasKey('b2bAdvisorRate', $data);
        $this->assertArrayHasKey('inflationRate', $data);
        $this->assertArrayHasKey('avgIterations', $data);
        $this->assertArrayHasKey('referrerDist', $data);
        $this->assertArrayHasKey('studioTabDist', $data);
        $this->assertArrayHasKey('strategyStarterDist', $data);
        $this->assertArrayHasKey('avgScrollDepth', $data);
        $this->assertArrayHasKey('avgDwellTime', $data);
    }

    public function testGetWeeklyCalculationCountReturnsCountOrFloor(): void
    {
        // With empty table, should return floor (2000)
        $count = $this->repository->getWeeklyCalculationCount();
        $this->assertGreaterThanOrEqual(2000, $count);

        // Insert 2500 calculations in past 2 days
        $stmt = $this->pdo->prepare("
            INSERT INTO user_calculations (calc_type, amount, duration, created_at)
            VALUES ('sip', 10000, 15, datetime('now', '-2 days'))
        ");
        for ($i = 0; $i < 2050; $i++) {
            $stmt->execute();
        }

        $countWithData = $this->repository->getWeeklyCalculationCount();
        $this->assertEquals(2050, $countWithData);
    }

    public function testConsolidatedScalarMetricsComputesAccurateAggregations(): void
    {
        $this->pdo->exec("
            INSERT INTO user_calculations (
                calc_type, amount, duration, step_up_pct, interest_rate, sip_amount,
                swp_enabled, swp_withdrawal, table_viewed, pdf_downloaded, pdf_has_custom_name,
                inflation_enabled, interaction_count, scroll_depth_pct, dwell_time_seconds,
                final_corpus, wealth_multiplier, created_at
            ) VALUES
            ('SIP', 50000, 10, 10.0, 12.0, 5000, 0, 0, 1, 1, 1, 1, 5, 80, 120, 1500000, 2.5, datetime('now', '-2 hours')),
            ('SIP', 25000, 20, 0.0, 15.0, 2500, 1, 10000, 0, 0, 0, 0, 3, 50, 60, 3500000, 3.8, datetime('now', '-3 hours')),
            ('SWP', 1000000, 15, 0.0, 8.0, 0, 1, 20000, 1, 1, 0, 0, 4, 90, 180, 2000000, 1.9, datetime('now', '-5 hours'))
        ");

        $range = [
            'label' => '24 Hours',
            'interval' => '-1 day',
            'unit' => 'hour',
            'cte_start' => '-23 hours',
        ];

        $data = $this->repository->getDashboardData($range);

        $this->assertSame(3, $data['totalCalculations']);
        $this->assertSame(10.0, $data['avgStepUpPct']);
        $this->assertSame(2, $data['totalPdfDownloads']);
        $this->assertSame(66.7, $data['conversionRate']); // 2/3 ≈ 66.7%
        $this->assertSame(2, $data['totalSIP']);
        $this->assertSame(1, $data['stepUpSIP']);
        $this->assertSame(1, $data['flatSIP']);
        $this->assertSame(50.0, $data['stepUpAdoptionRate']);
        $this->assertSame(15.0, $data['avgDurationSIP']); // (10 + 20) / 2
        $this->assertSame(15.0, $data['avgDurationSWP']);
        $this->assertEqualsWithDelta(11.6666, $data['avgInterestRate'], 0.01); // (12 + 15 + 8) / 3
        $this->assertSame(2, $data['totalSWPEnabled']);
        $this->assertSame(3750.0, $data['avgSipAmount']); // (5000 + 2500) / 2
        $this->assertSame(15000.0, $data['avgSwpWithdrawal']); // (10000 + 20000) / 2
        $this->assertSame(66.7, $data['tableViewEngagement']);
        $this->assertSame(50.0, $data['b2bAdvisorRate']); // 1 / 2
        $this->assertSame(33.3, $data['inflationRate']); // 1 / 3
        $this->assertSame(4.0, $data['avgIterations']); // (5 + 3 + 4) / 3
        $this->assertSame(73.3, $data['avgScrollDepth']); // (80 + 50 + 90) / 3
        $this->assertSame(120.0, $data['avgDwellTime']); // (120 + 60 + 180) / 3
    }
}
