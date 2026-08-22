<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\AnonymizedInsightLogger;
use Core\Http\Request;
use Core\InsightPayload;
use PDO;
use PHPUnit\Framework\TestCase;

class AnonymizedInsightLoggerTest extends TestCase
{
    private PDO $pdo;
    private AnonymizedInsightLogger $logger;
    private string $originalErrorLog;

    protected function setUp(): void
    {
        $this->originalErrorLog = (string) ini_get('error_log');
        ini_set('error_log', '/dev/null');

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

        $this->logger = new AnonymizedInsightLogger($this->pdo);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->originalErrorLog);
    }

    public function testLogCalculationInsertsRecordSuccessfully(): void
    {
        $payload = InsightPayload::fromArray([
            'calc_type' => 'SIP',
            'amount' => 10000,
            'duration' => 20,
            'step_up_pct' => 10,
            'currency' => 'INR',
            'pdf_downloaded' => false,
            'interest_rate' => 12.0,
            'sip_amount' => 10000,
            'sip_duration' => 20,
            'final_corpus' => 10000000,
            'total_invested' => 2400000,
            'wealth_multiplier' => 4.17,
            'goal_mode' => 'grow',
            'device_type' => 'mobile',
            'table_viewed' => 1,
            'pdf_has_custom_name' => 0,
            'inflation_enabled' => 0,
            'interaction_count' => 5,
            'preset_clicked' => 'moderate',
            'exit_action' => 'calc_only',
        ]);

        $request = new Request([], [], [
            'HTTP_CF_IPCOUNTRY' => 'IN',
            'HTTP_REFERER' => 'https://google.com/search?q=sip+calculator',
        ]);

        $this->logger->logCalculation($payload, $request);

        $stmt = $this->pdo->query("SELECT * FROM user_calculations");
        $rows = $stmt->fetchAll();

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertSame('SIP', $row['calc_type']);
        $this->assertEquals(10000.0, $row['amount']);
        $this->assertEquals(20, $row['duration']);
        $this->assertSame('INR', $row['currency']);
        $this->assertSame('IN', $row['country_code']);
        $this->assertSame('https://google.com/search?q=sip+calculator', $row['referrer']);
        $this->assertEquals(1, $row['table_viewed']);
        $this->assertEquals(5, $row['interaction_count']);
        $this->assertSame('moderate', $row['preset_clicked']);
    }

    public function testLogCalculationClampsCountryCodeAndReferrer(): void
    {
        $payload = InsightPayload::fromArray([
            'calc_type' => 'SWP',
            'amount' => 50000,
            'duration' => 10,
        ]);

        $longCountry = str_repeat('X', 50);
        $longReferrer = 'https://example.com/' . str_repeat('A', 1000);

        $request = new Request([], [], [
            'HTTP_CF_IPCOUNTRY' => $longCountry,
            'HTTP_REFERER' => $longReferrer,
        ]);

        $this->logger->logCalculation($payload, $request);

        $stmt = $this->pdo->query("SELECT country_code, referrer FROM user_calculations LIMIT 1");
        $row = $stmt->fetch();

        $this->assertSame(10, mb_strlen($row['country_code']));
        $this->assertSame(512, mb_strlen($row['referrer']));
    }

    public function testLogCalculationCatchesAndSwallowsDatabaseExceptionsSilently(): void
    {
        // Drop table to induce a database exception
        $this->pdo->exec("DROP TABLE user_calculations");

        $payload = InsightPayload::fromArray([
            'calc_type' => 'SIP',
            'amount' => 5000,
            'duration' => 10,
        ]);

        $this->expectOutputRegex('/AnonymizedInsightLogger Error:/');

        // Should not throw an uncaught exception
        $this->logger->logCalculation($payload);
    }

    public function testLogCalculationPersistsSeoAndStudioParameters(): void
    {
        $payload = InsightPayload::fromArray([
            'calc_type' => 'SIP',
            'amount' => 25000,
            'duration' => 15,
            'landing_path' => '/swp-calculator',
            'referrer_category' => 'direct',
            'scroll_depth_pct' => 80,
            'dwell_time_seconds' => 95,
            'quick_answer_viewed' => 1,
            'faq_item_expanded' => 'faq-swp-taxation',
            'glossary_term_clicked' => 'swp',
            'hud_shortcut_clicked' => '#calculator',
            'active_studio_tab' => 'city_benchmark',
            'strategy_starter_used' => 'first_crore',
            'guided_wizard_completed' => 1,
            'stress_test_scenario' => 'covid_crash',
            'city_benchmark_city' => 'bengaluru',
            'scenario_diff_saved' => 1,
            'csv_exported' => 1,
            'qr_modal_opened' => 1,
            'tax_waterfall_opened' => 1,
            'goal_pledge_created' => 1,
            'internal_hub_clicked' => '/sip-calculator',
            'cwv_lcp_ms' => 520,
            'cwv_cls' => 0.012,
            'cwv_inp_ms' => 45,
            'connection_speed' => '4g',
            'viewport_bucket' => 'desktop',
        ]);

        $request = new Request([], [], [
            'HTTP_CF_IPCOUNTRY' => 'IN',
            'HTTP_REFERER' => 'https://www.google.com/search?q=sip+calculator',
            'REQUEST_URI' => '/swp-calculator',
        ]);

        $this->logger->logCalculation($payload, $request);

        $stmt = $this->pdo->query("SELECT * FROM user_calculations LIMIT 1");
        $row = $stmt->fetch();

        $this->assertNotEmpty($row);
        $this->assertSame('/swp-calculator', $row['landing_path']);
        // Referrer category auto-resolved to google_organic from referrer URL
        $this->assertSame('google_organic', $row['referrer_category']);
        $this->assertEquals(80, $row['scroll_depth_pct']);
        $this->assertEquals(95, $row['dwell_time_seconds']);
        $this->assertEquals(1, $row['quick_answer_viewed']);
        $this->assertSame('faq-swp-taxation', $row['faq_item_expanded']);
        $this->assertSame('swp', $row['glossary_term_clicked']);
        $this->assertSame('#calculator', $row['hud_shortcut_clicked']);
        $this->assertSame('city_benchmark', $row['active_studio_tab']);
        $this->assertSame('first_crore', $row['strategy_starter_used']);
        $this->assertEquals(1, $row['guided_wizard_completed']);
        $this->assertSame('covid_crash', $row['stress_test_scenario']);
        $this->assertSame('bengaluru', $row['city_benchmark_city']);
        $this->assertEquals(1, $row['scenario_diff_saved']);
        $this->assertEquals(1, $row['csv_exported']);
        $this->assertEquals(1, $row['qr_modal_opened']);
        $this->assertEquals(1, $row['tax_waterfall_opened']);
        $this->assertEquals(1, $row['goal_pledge_created']);
        $this->assertSame('/sip-calculator', $row['internal_hub_clicked']);
        $this->assertEquals(520, $row['cwv_lcp_ms']);
        $this->assertEquals(0.012, $row['cwv_cls']);
        $this->assertEquals(45, $row['cwv_inp_ms']);
        $this->assertSame('4g', $row['connection_speed']);
        $this->assertSame('desktop', $row['viewport_bucket']);
    }
}
