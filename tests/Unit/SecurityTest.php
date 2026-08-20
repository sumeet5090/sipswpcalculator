<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\ContentManager;
use Core\Http\Request;
use Parsedown;
use PHPUnit\Framework\TestCase;
use Services\CsvExportService;
use Services\HtmlSanitizer;
use Services\SessionManager;

class SecurityTest extends TestCase
{
    public function testCsvExportNeutralizesFormulaInjection(): void
    {
        $csvService = new CsvExportService();
        $maliciousData = [
            [
                'year' => '=SUM(1+1)',
                'begin_balance' => '+1000',
                'sip_monthly' => '-500',
                'annual_contribution' => '@IMPORTXML',
                'cumulative_invested' => "\t=cmd|' /C calc'!A0",
                'interest' => 12345.67,
                'combined_total' => 500000.0,
            ]
        ];

        $csv = $csvService->generate($maliciousData, false, '₹');

        // Verify all formula characters are prefixed with single quote
        $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $this->assertStringContainsString("'+1000", $csv);
        $this->assertStringContainsString("-500", $csv);
        $this->assertStringContainsString("'@IMPORTXML", $csv);
        $this->assertStringContainsString("=cmd|", $csv);
        // Genuine numbers should not have quote prefix
        $this->assertStringContainsString("12345.67", $csv);
    }

    public function testHtmlSanitizerStripsDangerousCssAndScriptAttributes(): void
    {
        $sanitizer = new HtmlSanitizer();

        $dirtyTable = '<table style="position: fixed; top: 0; left: 0; width: 100%; height: 10000px;" onclick="alert(1)">
            <thead><tr><th style="expression(alert(2))">Year</th></tr></thead>
            <tbody><tr><td>1</td></tr></tbody>
        </table>';

        $clean = $sanitizer->sanitizeTableHtml($dirtyTable);

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('expression', $clean);
        $this->assertStringNotContainsString('position: fixed', $clean);
        $this->assertStringContainsString('<table>', $clean);
        $this->assertStringContainsString('<td>1</td>', $clean);
    }

    public function testHtmlSanitizerClampsChartDataSize(): void
    {
        $sanitizer = new HtmlSanitizer();

        $validChart = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $this->assertEquals($validChart, $sanitizer->extractChartData($validChart));

        // Excessively large chart data (> 5MB)
        $giantChart = 'data:image/png;base64,' . str_repeat('A', 6000000);
        $this->assertEquals('', $sanitizer->extractChartData($giantChart));

        // Invalid URI format
        $invalid = 'javascript:alert(1)';
        $this->assertEquals('', $sanitizer->extractChartData($invalid));
    }

    public function testRequestUriConsecutiveSlashesNormalized(): void
    {
        $req = new Request([], [], [
            'REQUEST_URI' => '///admin_insights//test?foo=1',
            'REQUEST_METHOD' => 'GET'
        ]);

        $this->assertEquals('/admin_insights/test', $req->getUri());
    }

    public function testRequestClientIpResolution(): void
    {
        // 1. Direct connection
        $reqDirect = new Request([], [], ['REMOTE_ADDR' => '203.0.113.195']);
        $this->assertEquals('203.0.113.195', $reqDirect->getClientIp());

        // 2. Cloudflare connecting IP
        $reqCf = new Request([], [], [
            'REMOTE_ADDR' => '172.68.1.1',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.42'
        ]);
        $this->assertEquals('198.51.100.42', $reqCf->getClientIp());

        // 3. Invalid IP fallback
        $reqInvalid = new Request([], [], [
            'REMOTE_ADDR' => 'not-an-ip',
            'HTTP_CF_CONNECTING_IP' => 'malicious<script>'
        ]);
        $this->assertEquals('127.0.0.1', $reqInvalid->getClientIp());
    }

    public function testSessionManagerVerifyCsrfTokenHandlesNonStringSafely(): void
    {
        $session = new SessionManager();
        $this->assertFalse($session->verifyCsrfToken(''));
        $this->assertFalse($session->verifyCsrfToken(['array_payload']));
    }

    public function testContentManagerRejectsPathTraversal(): void
    {
        $tempDir = sys_get_temp_dir() . '/sec_content_' . uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir . '/allowed.md', '# Allowed');

        $contentManager = new ContentManager(new Parsedown(), $tempDir);

        // Valid access
        $valid = $contentManager->getParsedContent('allowed');
        $this->assertNotNull($valid);

        // Path traversal attempts
        $this->assertNull($contentManager->getParsedContent('../../../etc/passwd'));
        $this->assertNull($contentManager->getParsedContent('..%2f..%2fsecret'));

        // Cleanup
        unlink($tempDir . '/allowed.md');
        rmdir($tempDir);
    }
}
