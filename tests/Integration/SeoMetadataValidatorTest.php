<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SeoMetadataValidatorTest extends IntegrationTestCase
{
    /**
     * Start local PHP development server in the background on port 9000.
     */
    public static function setUpBeforeClass(): void
    {
        self::startLocalServer();
    }

    /**
     * Terminate the background development server.
     */
    public static function tearDownAfterClass(): void
    {
        self::stopLocalServer();
    }

    /**
     * Data provider that yields all registered public URLs for testing.
     *
     * @return array<string, array{0: string}>
     */
    public static function pageRoutesProvider(): array
    {
        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $paths = [];

        // Home
        $paths['/'] = ['/'];

        // Calculators
        foreach (array_keys($routesConfig['calculators']) as $calc) {
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

        $allPosts = (new \Core\BlogRepository(new \Core\ContentManager(new \Parsedown(), __DIR__ . '/../../content')))->getAllPosts();
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
        $url = self::getBaseUrl($path);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!is_string($html) || $html === '') {
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
        $this->assertInstanceOf(\DOMNodeList::class, $h1s);
        $this->assertEquals(
            1,
            $h1s->length,
            "SEO Rule Violation: Page '$path' must contain exactly one <h1> tag. Found " . $h1s->length
        );
        $h1Node = $h1s->item(0);
        $this->assertInstanceOf(\DOMElement::class, $h1Node);
        $h1Text = trim($h1Node->textContent);
        $this->assertNotEmpty($h1Text, "SEO Rule Violation: <h1> tag on page '$path' is empty.");

        // 2. SEO Title tag Validation
        $titles = $xpath->query('//title');
        $this->assertInstanceOf(\DOMNodeList::class, $titles);
        $this->assertEquals(
            1,
            $titles->length,
            "SEO Rule Violation: Page '$path' must contain exactly one <title> element."
        );
        $titleNode = $titles->item(0);
        $this->assertInstanceOf(\DOMElement::class, $titleNode);
        $titleText = trim($titleNode->textContent);
        $this->assertNotEmpty($titleText, "SEO Rule Violation: <title> tag on page '$path' is empty.");
        $this->assertGreaterThanOrEqual(
            10,
            strlen($titleText),
            "SEO Rule Violation: Title '$titleText' on page '$path' is too short (Length: " . strlen($titleText) . ", min: 10)."
        );
        $this->assertLessThanOrEqual(
            65,
            strlen($titleText),
            "SEO Rule Violation: Title '$titleText' on page '$path' is too long (Length: " . strlen($titleText) . ", max: 65)."
        );

        // 3. Meta Description tag Validation
        $descriptions = $xpath->query('//meta[@name="description"]');
        $this->assertInstanceOf(\DOMNodeList::class, $descriptions);
        $this->assertEquals(
            1,
            $descriptions->length,
            "SEO Rule Violation: Page '$path' must contain exactly one meta description tag."
        );
        $descNode = $descriptions->item(0);
        $this->assertInstanceOf(\DOMElement::class, $descNode);
        $descContent = trim($descNode->getAttribute('content'));
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
        $this->assertInstanceOf(\DOMNodeList::class, $canonicals);
        $this->assertEquals(
            1,
            $canonicals->length,
            "SEO Rule Violation: Page '$path' must contain exactly one canonical tag."
        );
        $canonicalNode = $canonicals->item(0);
        $this->assertInstanceOf(\DOMElement::class, $canonicalNode);
        $canonicalHref = trim($canonicalNode->getAttribute('href'));
        $this->assertNotEmpty($canonicalHref, "SEO Rule Violation: Canonical link href is empty on page '$path'.");
        $expectedBaseUrl = (new \Core\SiteConfig((string) \Core\Env::get('APP_URL', 'https://sipswpcalculator.com')))->getBaseUrl();
        $expectedCanonical = rtrim($expectedBaseUrl, '/') . '/' . ltrim($path, '/');

        $this->assertEquals(
            $expectedCanonical,
            $canonicalHref,
            "SEO Rule Violation: Canonical link '$canonicalHref' must point to exactly '$expectedCanonical'."
        );

        // 5. OpenGraph tag Validation
        $ogTitles = $xpath->query('//meta[@property="og:title"]');
        $this->assertInstanceOf(\DOMNodeList::class, $ogTitles);
        $this->assertGreaterThanOrEqual(1, $ogTitles->length, "SEO Rule Violation: Missing meta og:title on page '$path'.");
        $ogTitleNode = $ogTitles->item(0);
        $this->assertInstanceOf(\DOMElement::class, $ogTitleNode);
        $this->assertNotEmpty(trim($ogTitleNode->getAttribute('content')), "SEO Rule Violation: og:title is empty on page '$path'.");

        $ogDescriptions = $xpath->query('//meta[@property="og:description"]');
        $this->assertInstanceOf(\DOMNodeList::class, $ogDescriptions);
        $this->assertGreaterThanOrEqual(1, $ogDescriptions->length, "SEO Rule Violation: Missing meta og:description on page '$path'.");
        $ogDescNode = $ogDescriptions->item(0);
        $this->assertInstanceOf(\DOMElement::class, $ogDescNode);
        $ogDescContent = trim($ogDescNode->getAttribute('content'));
        $this->assertNotEmpty($ogDescContent, "SEO Rule Violation: og:description is empty on page '$path'.");
        $this->assertGreaterThanOrEqual(
            40,
            strlen($ogDescContent),
            "SEO Rule Violation: Description on page '$path' is too short (Length: " . strlen($ogDescContent) . ", min: 40)."
        );
        $this->assertLessThanOrEqual(
            200,
            strlen($ogDescContent),
            "SEO Rule Violation: Description on page '$path' is too long (Length: " . strlen($ogDescContent) . ", max: 200)."
        );

        $ogUrls = $xpath->query('//meta[@property="og:url"]');
        $this->assertInstanceOf(\DOMNodeList::class, $ogUrls);
        $this->assertGreaterThanOrEqual(1, $ogUrls->length, "SEO Rule Violation: Missing meta og:url on page '$path'.");
        $ogUrlNode = $ogUrls->item(0);
        $this->assertInstanceOf(\DOMElement::class, $ogUrlNode);
        $this->assertEquals(
            $canonicalHref,
            trim($ogUrlNode->getAttribute('content')),
            "SEO Rule Violation: og:url does not match canonical URL on page '$path'."
        );

        $ogTypes = $xpath->query('//meta[@property="og:type"]');
        $this->assertInstanceOf(\DOMNodeList::class, $ogTypes);
        $this->assertGreaterThanOrEqual(1, $ogTypes->length, "SEO Rule Violation: Missing meta og:type on page '$path'.");
        $ogTypeNode = $ogTypes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $ogTypeNode);
        $expectedOgType = (str_starts_with($path, '/resource/') && substr_count(trim($path, '/'), '/') >= 2) ? 'article' : 'website';
        $this->assertEquals(
            $expectedOgType,
            trim($ogTypeNode->getAttribute('content')),
            "SEO Rule Violation: og:type must be '$expectedOgType' on page '$path'."
        );

        $authors = $xpath->query('//meta[@name="author"]');
        $this->assertInstanceOf(\DOMNodeList::class, $authors);
        $this->assertGreaterThanOrEqual(1, $authors->length, "SEO Rule Violation: Missing meta name=author on page '$path'.");
        $authorNode = $authors->item(0);
        $this->assertInstanceOf(\DOMElement::class, $authorNode);
        $this->assertNotEmpty(trim($authorNode->getAttribute('content')), "SEO Rule Violation: author meta is empty on page '$path'.");

        // 6. Robots Directive Validation
        $robots = $xpath->query('//meta[@name="robots"]');
        $this->assertInstanceOf(\DOMNodeList::class, $robots);
        $this->assertGreaterThanOrEqual(1, $robots->length, "SEO Rule Violation: Missing meta name=robots on page '$path'.");
        $robotsNode = $robots->item(0);
        $this->assertInstanceOf(\DOMElement::class, $robotsNode);
        $robotsContent = trim($robotsNode->getAttribute('content'));
        if (in_array($path, ['/privacy', '/terms'], true)) {
            $this->assertStringContainsString('noindex', $robotsContent, "Page '$path' should be noindex.");
        } else {
            $this->assertStringContainsString('max-image-preview:large', $robotsContent, "Page '$path' missing max-image-preview:large directive.");
            $this->assertStringContainsString('max-snippet:-1', $robotsContent, "Page '$path' missing max-snippet:-1 directive.");
        }

        // 7. JSON-LD Schema.org Validation
        $schemas = $xpath->query('//script[@type="application/ld+json"]');
        $this->assertInstanceOf(\DOMNodeList::class, $schemas);
        $this->assertGreaterThanOrEqual(
            1,
            $schemas->length,
            "SEO Rule Violation: Page '$path' must contain at least one structured JSON-LD schema script."
        );

        $schemaTypes = [];
        foreach ($schemas as $schemaNode) {
            $this->assertInstanceOf(\DOMNode::class, $schemaNode);
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
            $schemaTypes[] = $data['@type'];
        }

        // Assert no duplicate schema types exist on the page
        $this->assertEquals(
            count($schemaTypes),
            count(array_unique($schemaTypes)),
            "SEO Rule Violation: Duplicate schema types detected on page '$path': " . implode(', ', array_diff_assoc($schemaTypes, array_unique($schemaTypes)))
        );

        // Calculator routes must have SoftwareApplication, FAQPage, and HowTo schemas
        if (str_contains($path, 'calculator') || $path === '/') {
            $this->assertContains(
                'SoftwareApplication',
                $schemaTypes,
                "Calculator page '$path' is missing SoftwareApplication schema."
            );
            $this->assertContains(
                'FAQPage',
                $schemaTypes,
                "Calculator page '$path' is missing FAQPage schema."
            );
            $this->assertContains(
                'HowTo',
                $schemaTypes,
                "Calculator page '$path' is missing HowTo schema."
            );
        }
    }
}
