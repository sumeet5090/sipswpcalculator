<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculatorLinksValidatorTest extends TestCase
{
    public function testCalculatorLinksFileIsValidAndCoversAllCalculators(): void
    {
        $linksPath = __DIR__ . '/../../content/calculator_links.json';
        $this->assertFileExists($linksPath);

        $raw = (string) file_get_contents($linksPath);
        $this->assertJson($raw);

        $links = json_decode($raw, true);
        $this->assertIsArray($links);

        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $allValidHrefs = array_merge(
            ['/'],
            array_keys($routesConfig['calculators']),
            array_keys($routesConfig['pages']),
            ['/resources']
        );

        foreach (array_keys($routesConfig['calculators']) as $calcPath) {
            $slug = ltrim($calcPath, '/');
            $this->assertArrayHasKey($slug, $links, "calculator_links.json is missing key for '{$slug}'");
            $this->assertIsArray($links[$slug]);
            $this->assertGreaterThanOrEqual(2, count($links[$slug]), "Calculator '{$slug}' must have >= 2 related links");

            foreach ($links[$slug] as $item) {
                $this->assertArrayHasKey('href', $item);
                $this->assertArrayHasKey('title', $item);
                $this->assertArrayHasKey('description', $item);

                $this->assertNotEmpty($item['href']);
                $this->assertNotEmpty($item['title']);
                $this->assertNotEmpty($item['description']);

                // Ensure no self-links
                $this->assertNotEquals(
                    $calcPath,
                    $item['href'],
                    "Calculator '{$slug}' should not link to itself in related calculators"
                );

                // Ensure linked route exists
                $this->assertContains(
                    $item['href'],
                    $allValidHrefs,
                    "Invalid related calculator href: '{$item['href']}' in '{$slug}'"
                );
            }
        }
    }
}
