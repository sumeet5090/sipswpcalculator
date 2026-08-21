<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\GeneratePdfAction;
use Core\CurrencyHelper;
use Core\Exceptions\RateLimitExceededException;
use Core\Http\Request;
use Core\InvestmentCalculator;
use Core\PdfReportTemplate;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\FileUploadService;
use Services\HtmlSanitizer;
use Services\PdfGeneratorService;
use Services\RateLimiter;
use Services\RateLimitStorageInterface;

class GeneratePdfActionTest extends TestCase
{
    /** @var RateLimitStorageInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RateLimitStorageInterface $mockStorage;
    private RateLimiter $rateLimiter;
    private ConfigService $configService;
    private FileUploadService $fileUploadService;
    private HtmlSanitizer $sanitizer;
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->mockStorage = $this->createMock(RateLimitStorageInterface::class);
        $this->rateLimiter = new RateLimiter($this->mockStorage);
        $this->configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $this->fileUploadService = new FileUploadService();
        $this->sanitizer = new HtmlSanitizer();
        $this->calculator = new InvestmentCalculator();
    }

    public function testMethodNotAllowedForGetRequest(): void
    {
        $template = new PdfReportTemplate(new CurrencyHelper());
        $pdfService = new PdfGeneratorService($template);

        $action = new GeneratePdfAction(
            $this->rateLimiter,
            $pdfService,
            $this->configService,
            $this->fileUploadService,
            $this->sanitizer,
            $this->calculator
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $action($request);

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testRateLimitExceededReturns429(): void
    {
        $this->mockStorage->expects($this->once())
            ->method('checkAndIncrement')
            ->willThrowException(new RateLimitExceededException('Rate limit exceeded.'));

        $template = new PdfReportTemplate(new CurrencyHelper());
        $pdfService = new PdfGeneratorService($template);

        $action = new GeneratePdfAction(
            $this->rateLimiter,
            $pdfService,
            $this->configService,
            $this->fileUploadService,
            $this->sanitizer,
            $this->calculator
        );

        $request = new Request([], [
            'sip' => 5000,
            'years' => 10,
        ], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);

        $response = $action($request);

        $this->assertSame(429, $response->getStatusCode());
    }

    public function testSuccessfulPdfGenerationReturnsHeadersAndBinary(): void
    {
        $mockPdfGenerator = $this->createMock(PdfGeneratorService::class);
        $mockPdfGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('%PDF-1.4 Mock Binary PDF Content');

        $action = new GeneratePdfAction(
            $this->rateLimiter,
            $mockPdfGenerator,
            $this->configService,
            $this->fileUploadService,
            $this->sanitizer,
            $this->calculator
        );

        $request = new Request([], [
            'sip'        => 5000,
            'years'      => 5,
            'rate'       => 12,
            'clientName' => 'Aarav Sharma',
        ], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);

        $response = $action($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeader('Content-Type'));
        $this->assertSame('no', $response->getHeader('X-Accel-Buffering'));
        $this->assertStringContainsString('Financial_Report_for_Aarav_Sharma.pdf', $response->getHeader('Content-Disposition') ?? '');
        $this->assertStringContainsString('%PDF-1.4 Mock Binary PDF Content', $response->getBody());
    }
}
