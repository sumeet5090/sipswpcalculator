<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Twig\AppTwigExtension;
use Core\ViteHelper;
use Core\CurrencyHelper;

class DataIslandSecurityTest extends TestCase
{
    private AppTwigExtension $extension;

    protected function setUp(): void
    {
        $viteHelper = new ViteHelper(__DIR__ . '/../../dist/.vite/manifest.json');
        $currencyHelper = new CurrencyHelper();
        $this->extension = new AppTwigExtension($viteHelper, $currencyHelper);
    }

    /**
     * Test that json_island filter escapes HTML tags, ampersands, and quotes.
     */
    public function testJsonIslandHexEscaping(): void
    {
        $maliciousPayload = [
            'title' => '<script>alert("XSS")</script>',
            'formula' => 'a > b & c < d',
            'quote' => "Investor's \"Target\""
        ];

        $filters = $this->extension->getFilters();
        $jsonIslandFilter = null;
        foreach ($filters as $filter) {
            if ($filter->getName() === 'json_island') {
                $jsonIslandFilter = $filter->getCallable();
                break;
            }
        }

        $this->assertNotNull($jsonIslandFilter, "json_island filter must be registered.");
        $escaped = $jsonIslandFilter($maliciousPayload);

        // Assert no raw HTML tags or script breakout characters exist
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringNotContainsString('</script>', $escaped);
        $this->assertStringNotContainsString('<', $escaped);
        $this->assertStringNotContainsString('>', $escaped);
        $this->assertStringNotContainsString('&', $escaped);

        // Assert hex entities are present
        $this->assertStringContainsString('\u003C', $escaped);
        $this->assertStringContainsString('\u003E', $escaped);
        $this->assertStringContainsString('\u0026', $escaped);
        $this->assertStringContainsString('\u0022', $escaped);
        $this->assertStringContainsString('\u0027', $escaped);
    }

    /**
     * Test that all Twig templates embedding Data Islands use json_island filter.
     */
    public function testTwigDataIslandsUseJsonIslandFilter(): void
    {
        $viewsDir = __DIR__ . '/../../src/Views';
        $this->assertDirectoryExists($viewsDir);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsDir)
        );

        $checkedCount = 0;
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $content = file_get_contents($file->getPathname());
                $this->assertIsString($content);

                // Find any <script type="application/json"> blocks
                if (preg_match_all('/<script\s+type=[\'"]application\/json[\'"][^>]*>(.*?)<\/script>/s', $content, $matches)) {
                    foreach ($matches[1] as $block) {
                        $trimmed = trim($block);
                        // If it contains Twig expression {{ ... }}, assert it uses json_island
                        if (preg_match('/\{\{\s*(.*?)\s*\}\}/', $trimmed, $twigMatches)) {
                            $expr = $twigMatches[1];
                            $this->assertStringContainsString(
                                'json_island',
                                $expr,
                                "File {$file->getFilename()} contains a JSON Data Island that does not use the json_island filter: {$expr}"
                            );
                            $checkedCount++;
                        }
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $checkedCount, "Asserted at least one JSON Data Island was validated.");
    }
}
