<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculatorFrontmatterTest extends TestCase
{
    public function testAllCalculatorsHaveRequiredFrontmatterFields(): void
    {
        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $calculatorsDir = __DIR__ . '/../../content/calculators';

        foreach (array_keys($routesConfig['calculators']) as $path) {
            $slug = ltrim($path, '/');
            $filePath = $calculatorsDir . '/' . $slug . '.md';

            $this->assertFileExists($filePath, "Calculator markdown file missing: {$filePath}");

            $content = (string) file_get_contents($filePath);
            $this->assertStringStartsWith('---', $content, "Missing YAML frontmatter start in {$slug}.md");

            // Extract frontmatter
            preg_match('/^---\s*\n(.*?)\n---/s', $content, $matches);
            $this->assertNotEmpty($matches, "Failed to parse YAML frontmatter in {$slug}.md");

            $frontmatter = $matches[1];

            $this->assertMatchesRegularExpression('/^title:\s*.+/m', $frontmatter, "Missing title in {$slug}.md");
            $this->assertMatchesRegularExpression('/^type:\s*["\']?calculator["\']?/m', $frontmatter, "Type must be 'calculator' in {$slug}.md");
            $this->assertMatchesRegularExpression('/^keywords:\s*.+/m', $frontmatter, "Missing keywords in {$slug}.md");
            $this->assertMatchesRegularExpression('/^schema_name:\s*.+/m', $frontmatter, "Missing schema_name in {$slug}.md");
            $this->assertMatchesRegularExpression('/^meta_desc:\s*.+/m', $frontmatter, "Missing meta_desc in {$slug}.md");
        }
    }
}
