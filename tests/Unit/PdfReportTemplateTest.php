<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\CurrencyHelper;
use Core\PdfReportStylesheet;
use Core\PdfReportTemplate;
use PHPUnit\Framework\TestCase;

class PdfReportTemplateTest extends TestCase
{
    private PdfReportTemplate $template;

    protected function setUp(): void
    {
        $currencyHelper = new CurrencyHelper();
        $stylesheet = new PdfReportStylesheet();
        $this->template = new PdfReportTemplate($currencyHelper, null, $stylesheet);
    }

    public function testRenderOutputsCompleteHtmlStructureForSip(): void
    {
        $inputs = [
            'client_name'       => 'Rajesh Kumar',
            'advisor_name'      => 'Priya Mehta',
            'sip'               => 10000,
            'years'             => 15,
            'rate'              => 12.0,
            'stepup'            => 10.0,
            'lumpsum'           => 50000,
            'currency_symbol'   => '₹',
            'summary_invested'  => '₹38.15 Lakh',
            'summary_corpus'    => '₹1.15 Crore',
            'summary_withdrawn' => '0',
            'raw_invested'      => 3815000,
            'raw_corpus'        => 11500000,
            'raw_withdrawn'     => 0,
            'table_html'        => '<table><tr><th>Year</th></tr><tr><td>1</td></tr></table>',
            'chart_base64'      => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];

        $html = $this->template->render($inputs);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Rajesh Kumar', $html);
        $this->assertStringContainsString('Priya Mehta', $html);
        $this->assertStringContainsString('DejaVu Sans', $html);
        $this->assertStringContainsString('Year-by-Year Breakdown', $html);
        $this->assertStringContainsString('3.01x', $html); // 11.5M / 3.815M ≈ 3.01x
    }

    public function testRenderIncludesSwpPhaseWhenConfigured(): void
    {
        $inputs = [
            'client_name'       => 'Anita Desai',
            'swp_withdrawal'    => 50000,
            'swp_years'         => 10,
            'swp_rate'          => 8.0,
            'years'             => 20,
            'summary_invested'  => '₹50 Lakh',
            'summary_corpus'    => '₹80 Lakh',
            'summary_withdrawn' => '₹60 Lakh',
            'raw_invested'      => 5000000,
            'raw_corpus'        => 8000000,
            'raw_withdrawn'     => 6000000,
        ];

        $html = $this->template->render($inputs);

        $this->assertStringContainsString('Retirement Income (SWP)', $html);
        $this->assertStringContainsString('Total Withdrawn', $html);
        $this->assertStringContainsString('2.80x', $html); // (8M + 6M) / 5M = 2.80x
    }

    public function testRenderEscapesXssInUserInputs(): void
    {
        $inputs = [
            'client_name'       => '<script>alert("xss")</script>',
            'advisor_name'      => '<img src=x onerror=alert(1)>',
            'custom_disclaimer' => '<iframe src="evil.com"></iframe>',
        ];

        $html = $this->template->render($inputs);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
