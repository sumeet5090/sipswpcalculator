<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\ContentManager;
use Core\CurrencyHelper;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CsrfHoneypotMiddleware;
use Core\PdfTemplateInterface;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\Twig\AppTwigExtension;
use Core\ViteHelper;
use Parsedown;
use PHPUnit\Framework\TestCase;
use Services\CsvExportService;
use Services\HtmlSanitizer;
use Services\PdfGeneratorService;
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

    public function testTwigJsonIslandFilterEscapesHtmlEntitiesAndScriptTags(): void
    {
        $extension = new AppTwigExtension(new ViteHelper('testing'), new CurrencyHelper());
        $filters = $extension->getFilters();

        $jsonIslandFilter = null;
        foreach ($filters as $filter) {
            if ($filter->getName() === 'json_island') {
                $jsonIslandFilter = $filter;
                break;
            }
        }

        $this->assertNotNull($jsonIslandFilter, 'json_island filter must be registered');

        $callable = $jsonIslandFilter->getCallable();
        $this->assertIsCallable($callable);

        $maliciousPayload = [
            'tag' => '</script><script>alert("XSS")</script>',
            'quotes' => "single' and double\"",
            'ampersand' => 'A & B',
            'numeric' => 5000,
        ];

        $encoded = $callable($maliciousPayload);

        // Verify HTML/Script tags are hex-escaped
        $this->assertStringNotContainsString('</script>', $encoded);
        $this->assertStringNotContainsString('<script>', $encoded);
        $this->assertStringContainsString('\u003C\/script\u003E', $encoded);
        $this->assertStringContainsString('\u0026', $encoded);
        $this->assertStringContainsString('\u0027', $encoded);
        $this->assertStringContainsString('\u0022', $encoded);
    }

    public function testPdfGeneratorServiceConfiguresCustomCacheDirectories(): void
    {
        $tempFontDir = sys_get_temp_dir() . '/sec_fonts_' . uniqid();
        $tempPdfDir = sys_get_temp_dir() . '/sec_pdf_' . uniqid();

        $mockTemplate = $this->createStub(PdfTemplateInterface::class);
        $service = new PdfGeneratorService($mockTemplate, $tempFontDir, $tempPdfDir);

        $this->assertEquals($tempFontDir, $service->getFontDir());
        $this->assertEquals($tempPdfDir, $service->getTempDir());
        $this->assertTrue(is_dir($tempFontDir));
        $this->assertTrue(is_dir($tempPdfDir));

        // Cleanup
        rmdir($tempFontDir);
        rmdir($tempPdfDir);
    }

    public function testParsedownSafeModeEscapesRawScriptTags(): void
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);

        $dirtyMarkdown = "Here is some text with <script>alert('XSS')</script> embedded.";
        $rendered = $parsedown->text($dirtyMarkdown);

        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
    }

    public function testResponseCsvIncludesXAccelBufferingNo(): void
    {
        $response = Response::csv("Year,Amount\n1,1000\n", "test.csv");
        $headers = $response->getHeaders();

        $this->assertArrayHasKey('X-Accel-Buffering', $headers);
        $this->assertEquals('no', $headers['X-Accel-Buffering']);
    }

    public function testSchemaHelperGeneratesHexEscapedJson(): void
    {
        $siteConfig = new SiteConfig('https://sipswpcalculator.com');
        $schemaHelper = new SchemaHelper($siteConfig, 'Sumeet Boga', 'SIP SWP Calculator');

        $faqSchema = $schemaHelper->getFAQ([
            'What is SIP? </script><script>' => 'Systematic Investment Plan & more.'
        ]);

        $this->assertStringNotContainsString('</script>', $faqSchema);
        $this->assertStringContainsString('\u003C/script\u003E', $faqSchema);
        $this->assertStringContainsString('\u0026', $faqSchema);
    }

    public function testCsrfHoneypotMiddlewareAllowsPublicExportAndBlocksBots(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new CsrfHoneypotMiddleware($sessionManager);

        $nextCalled = false;
        $next = function (Request $req) use (&$nextCalled): Response {
            $nextCalled = true;
            return new Response('OK', 200);
        };

        // 1. Clean public export POST should pass through without CSRF requirement
        $publicReq = new Request([], ['sip' => '25000', 'years' => '10'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/download-csv']);
        $resp = $middleware->process($publicReq, $next);
        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $resp->getStatusCode());

        // 2. Bot request with honeypot field filled should be blocked (403)
        $botCalled = false;
        $botNext = function (Request $req) use (&$botCalled): Response {
            $botCalled = true;
            return new Response('OK', 200);
        };
        $botReq = new Request([], ['website_url' => 'https://spam.com', 'sip' => '25000'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/download-csv']);
        $botResp = $middleware->process($botReq, $botNext);
        $this->assertFalse($botCalled);
        $this->assertEquals(403, $botResp->getStatusCode());
        $this->assertStringContainsString('Automated request detected', $botResp->getBody());

        // 3. Admin POST request without CSRF token should be blocked (403)
        $adminCalled = false;
        $adminNext = function (Request $req) use (&$adminCalled): Response {
            $adminCalled = true;
            return new Response('OK', 200);
        };
        $adminReq = new Request([], ['password' => 'secret'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin_insights/login']);
        $adminResp = $middleware->process($adminReq, $adminNext);
        $this->assertFalse($adminCalled);
        $this->assertEquals(403, $adminResp->getStatusCode());
        $this->assertStringContainsString('Invalid security token', $adminResp->getBody());
    }
}
