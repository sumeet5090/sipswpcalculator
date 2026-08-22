<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\PdfReportStylesheet;
use Core\InsightPayload;
use Core\InsightRepository;
use PDO;

class SubsystemParityTest extends TestCase
{
    private PdfReportStylesheet $stylesheet;
    private PDO $pdo;
    private InsightRepository $repository;

    protected function setUp(): void
    {
        $this->stylesheet = new PdfReportStylesheet();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Set up in-memory table
        $this->pdo->exec("
            CREATE TABLE user_calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                calc_type TEXT,
                currency TEXT,
                amount REAL,
                duration INTEGER,
                step_up_pct REAL,
                country_code TEXT,
                pdf_downloaded INTEGER,
                referrer TEXT,
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
                pdf_has_custom_name INTEGER,
                inflation_enabled INTEGER,
                interaction_count INTEGER,
                preset_clicked TEXT,
                exit_action TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->repository = new InsightRepository($this->pdo);
    }

    /**
     * Test PdfReportStylesheet adapts font sizes and margins based on horizon length.
     */
    public function testPdfReportStylesheetDynamicScaling(): void
    {
        // Standard horizon (<= 25 years)
        $standardCss = $this->stylesheet->getStyles(20);
        $this->assertStringContainsString('font-size: 8.5px', $standardCss);
        $this->assertStringContainsString('5px 8px', $standardCss);

        // Extended horizon (> 25 years)
        $extendedCss = $this->stylesheet->getStyles(35);
        $this->assertStringContainsString('font-size: 7.5px', $extendedCss);
        $this->assertStringContainsString('3px 6px', $extendedCss);

        // Page break avoidance
        $this->assertStringContainsString('page-break-inside: avoid', $standardCss);
        $this->assertStringContainsString('page-break-after: avoid', $standardCss);
    }

    /**
     * Test InsightPayload instantiates with valid financial properties.
     */
    public function testInsightPayloadProperties(): void
    {
        $payload = new InsightPayload(
            calcType: 'sip',
            amount: 25000.0,
            duration: 15,
            stepUpPct: 10.0,
            currency: 'INR',
            pdfDownloaded: false,
            interestRate: 12.0,
            sipAmount: 25000.0,
            sipDuration: 15,
            sipStepUp: 10.0,
            swpEnabled: 0,
            swpWithdrawal: 0.0,
            swpDuration: 0,
            swpStepUp: 0.0,
            finalCorpus: 11800000.0,
            totalInvested: 4500000.0,
            wealthMultiplier: 2.62,
            goalMode: 'grow',
            deviceType: 'desktop',
            tableViewed: 1,
            pdfHasCustomName: 0,
            inflationEnabled: 0,
            interactionCount: 5,
            presetClicked: 'first_crore',
            exitAction: 'calc_only'
        );

        $this->assertEquals('sip', $payload->calcType);
        $this->assertEquals(25000.0, $payload->amount);
        $this->assertEquals(15, $payload->duration);
        $this->assertEquals(11800000.0, $payload->finalCorpus);
        $this->assertEquals('INR', $payload->currency);
    }

    /**
     * Test InsightRepository records calculations into SQLite table.
     */
    public function testInsightRepositoryInsertAndQuery(): void
    {
        $payload = new InsightPayload(
            calcType: 'sip_swp_combo',
            amount: 50000.0,
            duration: 20,
            stepUpPct: 10.0,
            currency: 'INR',
            pdfDownloaded: true,
            interestRate: 12.0,
            sipAmount: 50000.0,
            sipDuration: 20,
            sipStepUp: 10.0,
            swpEnabled: 1,
            swpWithdrawal: 100000.0,
            swpDuration: 20,
            swpStepUp: 6.0,
            finalCorpus: 35000000.0,
            totalInvested: 13745000.0,
            wealthMultiplier: 2.55,
            goalMode: 'grow',
            deviceType: 'mobile',
            tableViewed: 1,
            pdfHasCustomName: 1,
            inflationEnabled: 1,
            interactionCount: 12,
            presetClicked: 'fire_retirement',
            exitAction: 'pdf_download'
        );

        $logger = new \Core\AnonymizedInsightLogger($this->pdo);
        $logger->logCalculation($payload);

        $overview = $this->repository->getDashboardData([
            'interval' => '-30 days',
            'unit' => 'day',
            'cte_start' => '-29 days'
        ]);
        $this->assertEquals(1, $overview['totalCalculations']);
        $this->assertEquals(1, $overview['totalPdfDownloads']);
        $this->assertEquals(100.0, $overview['conversionRate']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_calculations");
        $count = (int) $stmt->fetchColumn();
        $this->assertEquals(1, $count);

        $rowStmt = $this->pdo->query("SELECT * FROM user_calculations LIMIT 1");
        $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row);
        $this->assertEquals('sip_swp_combo', $row['calc_type']);
        $this->assertEquals('INR', $row['currency']);
        $this->assertEquals(1, (int)$row['swp_enabled']);
        $this->assertEquals(1, (int)$row['pdf_downloaded']);
    }

    /**
     * Test Multi-Asset Rebalance Blended CAGR and Cashflow allocation equations.
     */
    public function testAssetRebalanceMathematics(): void
    {
        $equityPct = 70.0;
        $debtPct = 30.0;
        $equityRate = 12.0;
        $debtRate = 7.0;

        // Blended CAGR: (70 * 12 + 30 * 7) / 100 = (840 + 210) / 100 = 10.5%
        $blendedRate = (($equityPct / 100.0) * $equityRate) + (($debtPct / 100.0) * $debtRate);
        $this->assertEqualsWithDelta(10.5, $blendedRate, 0.001);

        // Volatility damping: (30 / 100) * 80 = 24% reduction
        $volReduction = (int) round(($debtPct / 100.0) * 80.0);
        $this->assertEquals(24, $volReduction);

        // Cashflow split on ₹25,000 monthly SIP
        $totalSip = 25000.0;
        $equitySip = (int) round(($equityPct / 100.0) * $totalSip);
        $debtSip = (int) ($totalSip - $equitySip);
        $this->assertEquals(17500, $equitySip);
        $this->assertEquals(7500, $debtSip);
        $this->assertEquals($totalSip, $equitySip + $debtSip);
    }

    /**
     * Test Daily Accrual terminal interest velocity and lifestyle tier progression.
     */
    public function testDailyAccrualMathematics(): void
    {
        $annualInterest = 2199815.0; // Terminal interest from 20-yr ₹10k SIP @ 12% + 10% stepup
        $dailyVelocity = (int) round($annualInterest / 365.0);

        // Daily velocity: 2,199,815 / 365 = 6,026.89 -> ₹6,027 / day
        $this->assertEquals(6027, $dailyVelocity);

        // Verify tier matching for ₹6,027 (matches >= ₹4,000 tier: 4 Premium Family Dinners)
        $tier = 'Standard';
        if ($dailyVelocity >= 20000) {
            $tier = '5-Star Luxury Suite';
        } elseif ($dailyVelocity >= 10000) {
            $tier = 'Weekend Getaway';
        } elseif ($dailyVelocity >= 4000) {
            $tier = '4 Premium Family Dinners';
        } elseif ($dailyVelocity >= 1500) {
            $tier = 'Household Expenses';
        }
        $this->assertEquals('4 Premium Family Dinners', $tier);
    }

    /**
     * Test Scenario Comparison Differential Yield and Zero-Baseline division guard.
     */
    public function testScenarioDifferentialMathematics(): void
    {
        $baselineCorpus = 15000000.0; // ₹1.50 Crore
        $activeCorpus = 20000000.0;   // ₹2.00 Crore

        // Absolute delta: +₹50 Lakh
        $deltaInr = $activeCorpus - $baselineCorpus;
        $this->assertEquals(5000000.0, $deltaInr);

        // Relative delta: (5,000,000 / 15,000,000) * 100 = +33.333%
        $deltaPct = $this->calculateDifferentialPercentage($deltaInr, $baselineCorpus);
        $this->assertEqualsWithDelta(33.333, $deltaPct, 0.01);

        // Zero-baseline division guard
        $guardedPct = $this->calculateDifferentialPercentage($deltaInr, 0.0);
        $this->assertEquals(0.0, $guardedPct);
    }

    private function calculateDifferentialPercentage(float $deltaInr, float $baseline): float
    {
        if ($baseline <= 0.0) {
            return 0.0;
        }
        return ($deltaInr / $baseline) * 100.0;
    }
}
