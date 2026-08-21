<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\LogInsightApiAction;
use Core\AnonymizedInsightLogger;
use Core\Exceptions\RateLimitExceededException;
use Core\Http\Request;
use PDO;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\RateLimiter;
use Services\RateLimitStorageInterface;

class LogInsightApiActionTest extends TestCase
{
    private PDO $pdo;
    private AnonymizedInsightLogger $logger;
    private RateLimiter $rateLimiter;
    private ConfigService $configService;
    /** @var RateLimitStorageInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RateLimitStorageInterface $mockStorage;

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
        $this->mockStorage = $this->createMock(RateLimitStorageInterface::class);
        $this->rateLimiter = new RateLimiter($this->mockStorage);
        $this->configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
    }

    public function testMethodNotAllowedForGetRequest(): void
    {
        $action = new LogInsightApiAction($this->logger, $this->rateLimiter, $this->configService);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);

        $response = $action($request);

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testPayloadTooLargeReturns413(): void
    {
        $action = new LogInsightApiAction($this->logger, $this->rateLimiter, $this->configService);
        $hugeBody = json_encode(['data' => str_repeat('X', 70000)]);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST'], [], (string) $hugeBody);

        $response = $action($request);

        $this->assertSame(413, $response->getStatusCode());
    }

    public function testInvalidPayloadMissingRequiredFieldsReturns400(): void
    {
        $action = new LogInsightApiAction($this->logger, $this->rateLimiter, $this->configService);
        $body = json_encode(['currency' => 'INR']); // Missing calc_type, amount, duration
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST'], [], (string) $body);

        $response = $action($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRateLimitExceededReturns429(): void
    {
        $this->mockStorage->expects($this->once())
            ->method('checkAndIncrement')
            ->willThrowException(new RateLimitExceededException('Rate limit exceeded.'));

        $action = new LogInsightApiAction($this->logger, $this->rateLimiter, $this->configService);
        $body = json_encode(['calc_type' => 'SIP', 'amount' => 5000, 'duration' => 10]);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], (string) $body);

        $response = $action($request);

        $this->assertSame(429, $response->getStatusCode());
    }

    public function testValidPostRequestReturns204AndPersistsRecord(): void
    {
        $action = new LogInsightApiAction($this->logger, $this->rateLimiter, $this->configService);
        $body = json_encode([
            'calc_type' => 'SIP',
            'amount' => 5000,
            'duration' => 10,
            'step_up_pct' => 5,
            'currency' => 'INR',
        ]);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], (string) $body);

        $response = $action($request);

        $this->assertSame(204, $response->getStatusCode());

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_calculations");
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }
}
