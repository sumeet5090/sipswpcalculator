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
}
