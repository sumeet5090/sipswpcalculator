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
}
