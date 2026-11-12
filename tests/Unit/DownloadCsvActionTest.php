<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\DownloadCsvAction;
use Core\Http\Request;
use Core\InvestmentCalculator;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\CsvExportService;

class DownloadCsvActionTest extends TestCase
{
    private DownloadCsvAction $action;

    protected function setUp(): void
    {
        $calculator = new InvestmentCalculator();
        $configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $csvService = new CsvExportService();

        $this->action = new DownloadCsvAction($calculator, $configService, $csvService);
    }

    public function testInvokeReturnsValidCsvResponseWithHeaders(): void
    {
        $request = new Request([], [
            'sip'      => 5000,
            'years'    => 5,
            'rate'     => 12,
            'stepup'   => 0,
            'lumpsum'  => 0,
            'currency' => 'INR',
        ], ['REQUEST_METHOD' => 'POST']);

        $response = ($this->action)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('attachment; filename="SIP_SWP_Yearly_Report.csv"', $response->getHeader('Content-Disposition') ?? '');

        $body = $response->getBody();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('Begin Balance (₹)', $body);
    }

    public function testInvokeResolvesCurrencySymbolsCorrectly(): void
    {
        $currencies = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        foreach ($currencies as $code => $symbol) {
            $request = new Request([], [
                'sip'      => 1000,
                'years'    => 2,
                'rate'     => 8,
                'currency' => $code,
            ], ['REQUEST_METHOD' => 'POST']);

            $response = ($this->action)($request);
            $this->assertStringContainsString("Begin Balance ({$symbol})", $response->getBody());
        }
    }

    public function testInvokeHandlesSwpOnlyPayload(): void
    {
        $request = new Request([], [
            'corpus'         => 10000000,
            'swp_withdrawal' => 60000,
            'swp_years'      => 10,
            'swp_rate'       => 8,
            'swp_stepup'     => 5,
        ], ['REQUEST_METHOD' => 'POST']);

        $response = ($this->action)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=utf-8', $response->getHeader('Content-Type'));
        $body = $response->getBody();
        $this->assertStringContainsString('Monthly SWP (₹)', $body);
        $this->assertStringContainsString('Cumulative Withdrawals (₹)', $body);
        $this->assertStringContainsString('10000000', $body);
    }

    public function testInvokeExportsSpecializedCalculatorsWithModeSpecificHeadersAndFilename(): void
    {
        $cagrReq = new Request([], [
            'calculator_type' => 'cagr',
            'cagr_initial'    => 100000,
            'cagr_final'      => 250000,
            'cagr_years'      => 5,
            'currency'        => 'INR',
        ], ['REQUEST_METHOD' => 'POST']);

        $cagrResp = ($this->action)($cagrReq);
        $this->assertSame(200, $cagrResp->getStatusCode());
        $this->assertStringContainsString('CAGR_Yearly_Report.csv', $cagrResp->getHeader('Content-Disposition') ?? '');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $cagrResp->getBody());
        $this->assertStringContainsString('Begin Value (₹)', $cagrResp->getBody());
        $this->assertStringContainsString('Compounded Gain (₹)', $cagrResp->getBody());

        $emiReq = new Request([], [
            'calculator_type' => 'emi',
            'emi_principal'   => 3000000,
            'emi_rate'        => 8.5,
            'emi_years'       => 20,
            'currency'        => 'INR',
        ], ['REQUEST_METHOD' => 'POST']);

        $emiResp = ($this->action)($emiReq);
        $this->assertSame(200, $emiResp->getStatusCode());
        $this->assertStringContainsString('Loan_EMI_Amortization_Schedule.csv', $emiResp->getHeader('Content-Disposition') ?? '');
        $this->assertStringContainsString('Monthly EMI (₹)', $emiResp->getBody());
    }
}
