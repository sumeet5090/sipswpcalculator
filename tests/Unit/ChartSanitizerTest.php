<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\PdfReportStylesheet;
use PHPUnit\Framework\TestCase;
use Services\HtmlSanitizer;

class ChartSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer();
    }

    public function testExtractChartDataAcceptsValidPngDataUri(): void
    {
        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $result = $this->sanitizer->extractChartData($validPng);

        $this->assertSame($validPng, $result);
    }

    public function testExtractChartDataRejectsOversizedPayloads(): void
    {
        $header = 'data:image/png;base64,';
        $oversized = $header . str_repeat('A', 5242890);

        $result = $this->sanitizer->extractChartData($oversized);
        $this->assertSame('', $result);
    }

    public function testExtractChartDataRejectsInvalidMimeType(): void
    {
        $svgData = 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=';
        $result = $this->sanitizer->extractChartData($svgData);

        $this->assertSame('', $result);
    }

    public function testExtractChartDataRejectsMalformedBase64(): void
    {
        $corrupt = 'data:image/png;base64,@@@invalid@@@';
        $result = $this->sanitizer->extractChartData($corrupt);

        $this->assertSame('', $result);
    }

    public function testPdfReportStylesheetGeneratesValidStyles(): void
    {
        $stylesheet = new PdfReportStylesheet();
        $css = $stylesheet->getStyles(20);

        $this->assertStringContainsString('.chart-box', $css);
        $this->assertStringContainsString('page-break-inside: avoid', $css);
        $this->assertStringNotContainsString('object-fit', $css);
    }
}
