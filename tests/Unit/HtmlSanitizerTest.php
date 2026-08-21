<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\HtmlSanitizer;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer();
    }

    public function testSanitizeTextStripsTagsAndClampsLength(): void
    {
        $raw = '<b>Hello</b> <i>World!</i>';
        $cleaned = $this->sanitizer->sanitizeText($raw, 11);

        $this->assertSame('Hello World', $cleaned);
    }

    public function testSanitizeTextHandlesMultibyteUtf8Characters(): void
    {
        $raw = 'नमस्ते भारत';
        $cleaned = $this->sanitizer->sanitizeText($raw, 6);

        $this->assertSame('नमस्ते', $cleaned);
    }

    public function testExtractChartDataAcceptsValidBase64DataUri(): void
    {
        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $result = $this->sanitizer->extractChartData($validPng);

        $this->assertSame($validPng, $result);
    }

    public function testExtractChartDataRejectsOversizedOrInvalidData(): void
    {
        $this->assertSame('', $this->sanitizer->extractChartData('not-a-data-uri'));
        $this->assertSame('', $this->sanitizer->extractChartData('data:image/svg+xml;base64,PHN2Zz48L3N2Zz4='));
    }

    public function testSanitizeTableHtmlAutoRepairsUnclosedTags(): void
    {
        $unclosedTable = '<table><tr><td>Year 1<td>5000';
        $clean = $this->sanitizer->sanitizeTableHtml($unclosedTable);

        $this->assertStringContainsString('<table>', $clean);
        $this->assertStringContainsString('</table>', $clean);
        $this->assertStringContainsString('<tr>', $clean);
        $this->assertStringContainsString('</tr>', $clean);
        $this->assertStringContainsString('<td>Year 1</td>', $clean);
    }

    public function testSanitizeTableHtmlStripsDangerousEventHandlersAndStyles(): void
    {
        $malicious = '<table><tr onclick="alert(1)" style="position: fixed; top: 0;"><td style="background: url(http://evil.com/x.png);">Data</td></tr></table>';
        $clean = $this->sanitizer->sanitizeTableHtml($malicious);

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('position: fixed', $clean);
        $this->assertStringNotContainsString('url(', $clean);
        $this->assertStringContainsString('Data', $clean);
    }

    public function testSanitizeTableHtmlReturnsFallbackOnEmptyInput(): void
    {
        $clean = $this->sanitizer->sanitizeTableHtml('   ');

        $this->assertSame('<table><tr><td>No data</td></tr></table>', $clean);
    }
}
