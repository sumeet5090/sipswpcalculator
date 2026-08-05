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
    }
}
