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
    private ConfigService $configService;
    private FileUploadService $fileUploadService;
    private HtmlSanitizer $sanitizer;
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
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

    public function testSuccessfulPdfGenerationReturnsHeadersAndBinary(): void
    {
        $mockPdfGenerator = $this->createMock(PdfGeneratorService::class);
        $mockPdfGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('%PDF-1.4 Mock Binary PDF Content');

        $action = new GeneratePdfAction(
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

    public function testSpecializedCalculatorPdfUsesSpecializedContextAndTitle(): void
    {
        $capturedPayload = [];
        $mockPdfGenerator = $this->createMock(PdfGeneratorService::class);
        $mockPdfGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(function (array $data) use (&$capturedPayload): bool {
                    $capturedPayload = $data;
                    return true;
                })
            )
            ->willReturn('%PDF-1.4 Specialized Content');

        $action = new GeneratePdfAction(
            $mockPdfGenerator,
            $this->configService,
            $this->fileUploadService,
            $this->sanitizer,
            $this->calculator
        );

        $request = new Request([], [
            'calculator_type' => 'fd',
            'fd_principal'    => 1000000,
            'fd_rate'         => 7.5,
            'fd_years'        => 5,
            'currency'        => 'INR',
        ], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);

        $response = $action($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Fixed Deposit Maturity Report', $capturedPayload['doc_title'] ?? null);
        $this->assertSame('FDP-', $capturedPayload['proposal_prefix'] ?? null);
        $this->assertStringContainsString('FD_Maturity_Schedule.pdf', $response->getHeader('Content-Disposition') ?? '');
    }

    public function testGeneratePdfExtractsAndSanitizesAdvisorPracticeFieldsAndBase64Logo(): void
    {
        $capturedPayload = [];
        $mockPdfGenerator = $this->createMock(PdfGeneratorService::class);
        $mockPdfGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(function (array $data) use (&$capturedPayload): bool {
                    $capturedPayload = $data;
                    return true;
                })
            )
            ->willReturn('%PDF-1.4 Advisor Proposal Content');

        $action = new GeneratePdfAction(
            $mockPdfGenerator,
            $this->configService,
            $this->fileUploadService,
            $this->sanitizer,
            $this->calculator
        );

        $validPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $request = new Request([], [
            'sip'                => 10000,
            'years'              => 10,
            'rate'               => 12,
            'clientName'         => 'Vikram Mehta',
            'proposalTitle'      => 'Retirement Freedom Roadmap',
            'advisorNotes'       => 'Plan structured for systematic accumulation.',
            'advisorName'        => 'Rajesh V. Sharma',
            'advisorFirm'        => 'Apex Wealth Partners',
            'advisorRegNo'       => 'ARN-189234',
            'advisorContact'     => '+91 98200 12345 | rajesh@apexwealth.in',
            'advisorLogoBase64'  => $validPngBase64,
        ], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);

        $response = $action($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Vikram Mehta', $capturedPayload['client_name']);
        $this->assertSame('Retirement Freedom Roadmap', $capturedPayload['proposal_title']);
        $this->assertSame('Plan structured for systematic accumulation.', $capturedPayload['advisor_notes']);
        $this->assertSame('Rajesh V. Sharma', $capturedPayload['advisor_name']);
        $this->assertSame('Apex Wealth Partners', $capturedPayload['advisor_firm']);
        $this->assertSame('ARN-189234', $capturedPayload['advisor_reg_no']);
        $this->assertSame('+91 98200 12345 | rajesh@apexwealth.in', $capturedPayload['advisor_contact']);
        $this->assertSame($validPngBase64, $capturedPayload['logo_base64']);
    }

    public function testGeneratePdfRejectsSvgOrMalformedLogoDataUriGracefully(): void
    {
        $capturedPayload = [];
        $mockPdfGenerator = $this->createMock(PdfGeneratorService::class);
        $mockPdfGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(function (array $data) use (&$capturedPayload): bool {
                    $capturedPayload = $data;
                    return true;
                })
            )
            ->willReturn('%PDF-1.4 Clean Content');

        $action = new GeneratePdfAction(
            $mockPdfGenerator,
            $this->configService,
            $this->fileUploadService,
            $this->sanitizer,
            $this->calculator
        );

        $svgBase64 = 'data:image/svg+xml;base64,PHN2Zz48Y2lyY2xlIHI9IjUiLz48L3N2Zz4=';

        $request = new Request([], [
            'sip'               => 5000,
            'years'             => 5,
            'rate'              => 12,
            'advisorLogoBase64' => $svgBase64,
        ], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);

        $response = $action($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($capturedPayload['logo_base64'], 'SVG logo data URI must be rejected and normalized to null');
    }
}
