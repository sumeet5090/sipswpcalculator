<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SeoMetadataValidatorTest extends TestCase
{
    private static int $serverPid = 0;

    /**
     * Start local PHP development server in the background on port 9000.
     */
    public static function setUpBeforeClass(): void
    {
        // Start built-in PHP web server pointing to the root index.php
        $command = sprintf(
            'php -S 127.0.0.1:9000 %s > /dev/null 2>&1 & echo $!',
            escapeshellarg(__DIR__ . '/../../index.php')
        );

        $output = [];
        exec($command, $output);
        self::$serverPid = (int)($output[0] ?? 0);

        // Wait up to 1 second for the server to bind and start responding
        $maxRetries = 10;
        $started = false;
        
        for ($i = 0; $i < $maxRetries; $i++) {
            $socket = @fsockopen('127.0.0.1', 9000, $errno, $errstr, 0.1);
            if ($socket) {
                fclose($socket);
                $started = true;
                break;
            }
            usleep(100000); // 100ms
        }

        if (!$started) {
            throw new \RuntimeException('Failed to start local PHP development server on 127.0.0.1:9000');
        }
    }

    /**
     * Terminate the background development server.
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$serverPid > 0) {
            exec('kill -9 ' . self::$serverPid . ' 2>/dev/null');
        }
    }

    /**
     * Data provider that yields all registered public URLs for testing.
     */
    public static function pageRoutesProvider(): array
    {
        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $paths = [];

        // Home
        $paths['/'] = ['/'];

        // Calculators
        foreach ($routesConfig['calculators'] as $calc) {
            $paths[$calc] = [$calc];
        }

        // Pages
        foreach ($routesConfig['pages'] as $uri => $method) {
            $paths[$uri] = [$uri];
        }

        // Blog Home
        $paths['/resources'] = ['/resources'];

        // Dynamic blog posts
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../../functions.php';
        
        $allPosts = \Core\BlogRepository::getAllPosts();
        foreach ($allPosts as $post) {
            $paths[$post['href']] = [$post['href']];
        }

        return $paths;
    }

    /**
     * Test SEO tags, canonical URL, heading structures, and schema validation.
     */
    #[DataProvider('pageRoutesProvider')]
    public function testPageSeoMetadata(string $path): void
    {
        $url = 'http://127.0.0.1:9000' . $path;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($html === false || $html === '') {
            $this->fail("Failed to query path '$path'. Server did not return a valid response.");
        }

        $this->assertEquals(
            200,
            $statusCode,
            "HTTP request did not return 200 OK for path '$path'. Status code received: {$statusCode}"
        );

        // Load HTML into DOMDocument
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // 1. Heading Integrity Check (Assert EXACTLY one <h1> per page)
        $h1s = $xpath->query('//h1');
        $this->assertEquals(
            1,
            $h1s->length,
            "SEO Rule Violation: Page '$path' must contain exactly one <h1> tag. Found " . $h1s->length
        );
        $h1Text = trim($h1s->item(0)->textContent);
        $this->assertNotEmpty($h1Text, "SEO Rule Violation: <h1> tag on page '$path' is empty.");

        // 2. SEO Title tag Validation
        $titles = $xpath->query('//title');
        $this->assertEquals(
            1,
            $titles->length,
            "SEO Rule Violation: Page '$path' must contain exactly one <title> element."
        );
        $titleText = trim($titles->item(0)->textContent);
        $this->assertNotEmpty($titleText, "SEO Rule Violation: <title> tag on page '$path' is empty.");
        $this->assertGreaterThanOrEqual(
            10,
            strlen($titleText),
            "SEO Rule Violation: Title '$titleText' on page '$path' is too short (Length: " . strlen($titleText) . ", min: 10)."
        );
        $this->assertLessThanOrEqual(
            85,
            strlen($titleText),
            "SEO Rule Violation: Title '$titleText' on page '$path' is too long (Length: " . strlen($titleText) . ", max: 85)."
        );

        // 3. Meta Description tag Validation
        $descriptions = $xpath->query('//meta[@name="description"]');
        $this->assertEquals(
            1,
            $descriptions->length,
            "SEO Rule Violation: Page '$path' must contain exactly one meta description tag."
        );
        $descContent = trim($descriptions->item(0)->getAttribute('content'));
        $this->assertNotEmpty($descContent, "SEO Rule Violation: Meta description tag on page '$path' has empty content.");
        $this->assertGreaterThanOrEqual(
            40,
            strlen($descContent),
            "SEO Rule Violation: Description on page '$path' is too short (Length: " . strlen($descContent) . ", min: 40)."
        );
        $this->assertLessThanOrEqual(
            200,
            strlen($descContent),
            "SEO Rule Violation: Description on page '$path' is too long (Length: " . strlen($descContent) . ", max: 200)."
        );

        // 4. Canonical Link tag Validation
        $canonicals = $xpath->query('//link[@rel="canonical"]');
        $this->assertEquals(
            1,
            $canonicals->length,
            "SEO Rule Violation: Page '$path' must contain exactly one canonical tag."
        );
        $canonicalHref = trim($canonicals->item(0)->getAttribute('href'));
        $this->assertNotEmpty($canonicalHref, "SEO Rule Violation: Canonical link href is empty on page '$path'.");
        $this->assertStringStartsWith(
            'https://sipswpcalculator.com',
            $canonicalHref,
            "SEO Rule Violation: Canonical link '$canonicalHref' must point to https://sipswpcalculator.com root."
        );

        // 5. OpenGraph tag Validation
        $ogTitles = $xpath->query('//meta[@property="og:title"]');
        $this->assertGreaterThanOrEqual(1, $ogTitles->length, "SEO Rule Violation: Missing meta og:title on page '$path'.");
        $this->assertNotEmpty(trim($ogTitles->item(0)->getAttribute('content')), "SEO Rule Violation: og:title is empty on page '$path'.");

        $ogDescriptions = $xpath->query('//meta[@property="og:description"]');
        $this->assertGreaterThanOrEqual(1, $ogDescriptions->length, "SEO Rule Violation: Missing meta og:description on page '$path'.");
        $this->assertNotEmpty(trim($ogDescriptions->item(0)->getAttribute('content')), "SEO Rule Violation: og:description is empty on page '$path'.");

        $ogUrls = $xpath->query('//meta[@property="og:url"]');
        $this->assertGreaterThanOrEqual(1, $ogUrls->length, "SEO Rule Violation: Missing meta og:url on page '$path'.");
        $this->assertEquals(
            $canonicalHref,
            trim($ogUrls->item(0)->getAttribute('content')),
            "SEO Rule Violation: og:url does not match canonical URL on page '$path'."
        );

        // 6. JSON-LD Schema.org Validation
        $schemas = $xpath->query('//script[@type="application/ld+json"]');
        $this->assertGreaterThanOrEqual(
            1,
            $schemas->length,
            "SEO Rule Violation: Page '$path' must contain at least one structured JSON-LD schema script."
        );

        foreach ($schemas as $schemaNode) {
            $json = trim($schemaNode->textContent);
            $this->assertJson($json, "JSON-LD Validation Error: Invalid JSON syntax on page '$path'.");
            
            $data = json_decode($json, true);
            $this->assertArrayHasKey('@context', $data, "JSON-LD Validation Error: Missing '@context' key on page '$path'.");
            $this->assertEquals(
                'https://schema.org',
                $data['@context'],
                "JSON-LD Validation Error: '@context' must be 'https://schema.org' on page '$path'."
            );
            $this->assertArrayHasKey('@type', $data, "JSON-LD Validation Error: Missing '@type' key on page '$path'.");
        }
    }
}
