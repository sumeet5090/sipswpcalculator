<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class HtmlMarkupValidatorTest extends IntegrationTestCase
{
    /**
     * Start local PHP development server in the background on port 9005.
     */
    public static function setUpBeforeClass(): void
    {
        self::startLocalServer(9005);
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
        $allPosts = (new \Core\BlogRepository(new \Core\ContentManager(new \Parsedown())))->getAllPosts();
        foreach ($allPosts as $post) {
            $paths[$post['href']] = [$post['href']];
        }

        return $paths;
    }

    /**
     * Test HTML markup for duplicate ID attributes.
     */
    #[DataProvider('pageRoutesProvider')]
    public function testHtmlHasNoDuplicateIds(string $path): void
    {
        $url = 'http://127.0.0.1:9005' . $path;

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
            "HTTP request did not return 200 OK for path '$path'. Status code: {$statusCode}"
        );

        // Load HTML into DOMDocument
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $elementsWithId = $xpath->query('//*[@id]');

        $ids = [];
        foreach ($elementsWithId as $element) {
            if ($element instanceof \DOMElement) {
                $id = $element->getAttribute('id');
                if ($id !== '') {
                    $ids[] = $id;
                }
            }
        }

        $uniqueIds = array_unique($ids);

        // Find duplicates for helpful assertion message
        $duplicates = array_diff_assoc($ids, $uniqueIds);

        $this->assertEmpty(
            $duplicates,
            sprintf(
                "HTML Validation Error: Page '%s' has duplicate element IDs: [%s]",
                $path,
                implode(', ', array_unique($duplicates))
            )
        );
    }
}
