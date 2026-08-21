<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\PdfReportStylesheet;
use PHPUnit\Framework\TestCase;

class PdfReportStylesheetTest extends TestCase
{
    private PdfReportStylesheet $stylesheet;

    protected function setUp(): void
    {
        $this->stylesheet = new PdfReportStylesheet();
    }

    public function testGetStylesForStandardSchedules25YearsOrLess(): void
    {
        $css = $this->stylesheet->getStyles(20);

        $this->assertStringContainsString("font-family: 'DejaVu Sans', sans-serif;", $css);
        $this->assertStringContainsString('font-size: 8.5px;', $css);
        $this->assertStringContainsString('padding: 5px 8px;', $css);
        $this->assertStringContainsString('margin-top: 16px;', $css);
        $this->assertStringContainsString('thead { display: table-header-group; }', $css);
        $this->assertStringContainsString('tr { page-break-inside: avoid; }', $css);
        $this->assertStringContainsString('.chart-box { text-align: center;', $css);
        $this->assertStringContainsString('@page { margin: 24px 32px 28px 32px; }', $css);
    }

    public function testGetStylesForMultiDecadeSchedulesOver25Years(): void
    {
        $css = $this->stylesheet->getStyles(30);

        $this->assertStringContainsString("font-family: 'DejaVu Sans', sans-serif;", $css);
        $this->assertStringContainsString('font-size: 7.5px;', $css);
        $this->assertStringContainsString('padding: 3px 6px;', $css);
        $this->assertStringContainsString('margin-top: 10px;', $css);
        $this->assertStringContainsString('thead { display: table-header-group; }', $css);
        $this->assertStringContainsString('tr { page-break-inside: avoid; }', $css);
    }
}
