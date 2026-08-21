<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\CsvExportService;

class CsvExportServiceTest extends TestCase
{
    private CsvExportService $service;

    protected function setUp(): void
    {
        $this->service = new CsvExportService();
    }

    public function testGenerateOutputsUtf8BomAndHeadersForSipOnly(): void
    {
        $data = [
            [
                'year'                => 1,
                'begin_balance'       => 0,
                'sip_monthly'         => 5000,
                'annual_contribution' => 60000,
                'cumulative_invested' => 60000,
                'interest'            => 4000,
                'combined_total'      => 64000,
            ],
        ];

        $csv = $this->service->generate($data, false, '₹');

        // Check for UTF-8 Byte Order Mark
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        // Check headers
        $this->assertStringContainsString('Year', $csv);
        $this->assertStringContainsString('Begin Balance (₹)', $csv);
        $this->assertStringContainsString('Monthly SIP (₹)', $csv);
        $this->assertStringContainsString('Annual Contribution (₹)', $csv);
        $this->assertStringContainsString('Cumulative Invested (₹)', $csv);
        $this->assertStringContainsString('Interest Earned (₹)', $csv);
        $this->assertStringContainsString('End Balance (₹)', $csv);

        // SWP headers should be absent
        $this->assertStringNotContainsString('Monthly SWP', $csv);
        $this->assertStringNotContainsString('Annual Withdrawal', $csv);

        // Check row values
        $this->assertStringContainsString('1,0,5000,60000,60000,4000,64000', $csv);
    }

    public function testGenerateIncludesSwpColumnsWhenEnabled(): void
    {
        $data = [
            [
                'year'                   => 11,
                'begin_balance'          => 1000000,
                'sip_monthly'            => 0,
                'annual_contribution'    => 0,
                'cumulative_invested'    => 600000,
                'swp_monthly'            => 10000,
                'annual_withdrawal'      => 120000,
                'cumulative_withdrawals' => 120000,
                'interest'               => 70000,
                'combined_total'         => 950000,
            ],
        ];

        $csv = $this->service->generate($data, true, '$');

        $this->assertStringContainsString('Monthly SWP ($)', $csv);
        $this->assertStringContainsString('Annual Withdrawal ($)', $csv);
        $this->assertStringContainsString('Cumulative Withdrawals ($)', $csv);
        $this->assertStringContainsString('11,1000000,0,0,600000,10000,120000,120000,70000,950000', $csv);
    }

    public function testGenerateIncludesTaxColumnsWhenLtcgDataPresent(): void
    {
        $data = [
            [
                'year'                => 5,
                'begin_balance'       => 200000,
                'sip_monthly'         => 5000,
                'annual_contribution' => 60000,
                'cumulative_invested' => 300000,
                'interest'            => 25000,
                'combined_total'      => 325000,
                'ltcg_tax'            => 1500,
                'post_tax_total'      => 323500,
            ],
        ];

        $csv = $this->service->generate($data, false, '₹');

        $this->assertStringContainsString('Est. LTCG Tax (₹)', $csv);
        $this->assertStringContainsString('Post-Tax Balance (₹)', $csv);
        $this->assertStringContainsString('1500,323500', $csv);
    }

    public function testFormulaInjectionSanitizationNeutralizesDdeTriggers(): void
    {
        $data = [
            [
                'year'                => '=cmd|\' /C calc\'!A0',
                'begin_balance'       => '+12345',
                'sip_monthly'         => '@SUM(1,2)',
                'annual_contribution' => "-malicious",
                'cumulative_invested' => "|pipe_command",
                'interest'            => "\t=tab_formula",
                'combined_total'      => -50000, // Safe negative number
            ],
        ];

        $csv = $this->service->generate($data, false, '₹');

        $this->assertStringContainsString("'=cmd|' /C calc'!A0", $csv);
        $this->assertStringContainsString("'+12345", $csv);
        $this->assertStringContainsString("'@SUM(1,2)", $csv);
        $this->assertStringContainsString("'-malicious", $csv);
        $this->assertStringContainsString("'|pipe_command", $csv);
        $this->assertStringContainsString("'\t=tab_formula", $csv);

        // Safe numeric -50000 should NOT be prefixed with a quote
        $this->assertStringContainsString(',-50000', $csv);
    }
}
