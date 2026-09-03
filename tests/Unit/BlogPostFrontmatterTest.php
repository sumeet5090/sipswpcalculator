<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BlogPostFrontmatterTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function blogFilesProvider(): array
    {
        $blogDir = dirname(__DIR__, 2) . '/content/blog';
        $files = glob($blogDir . '/*/*.md');
        $dataset = [];

        foreach ($files ?: [] as $file) {
            $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $file);
            $dataset[$relativePath] = [$file, $relativePath];
        }

        return $dataset;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('blogFilesProvider')]
    public function testBlogPostHasRequiredFrontmatter(string $filePath, string $relativePath): void
    {
        $this->assertFileExists($filePath, "Blog markdown file missing: {$filePath}");

        $content = (string) file_get_contents($filePath);
        $this->assertStringStartsWith('---', $content, "Missing YAML frontmatter start in {$relativePath}");

        // Extract frontmatter
        $hasFrontmatter = preg_match('/^---\s*\n(.*?)\n---/s', $content, $matches);
        $this->assertSame(1, $hasFrontmatter, "Failed to parse YAML frontmatter in {$relativePath}");

        $frontmatter = $matches[1];

        // 1. Title validation
        $this->assertMatchesRegularExpression('/^title:\s*.+/m', $frontmatter, "Missing 'title' in {$relativePath}");
        preg_match('/^title:\s*["\']?(.*?)["\']?\s*$/m', $frontmatter, $titleMatches);
        $title = trim($titleMatches[1] ?? '');
        $this->assertNotEmpty($title, "Title cannot be empty in {$relativePath}");
        $this->assertLessThanOrEqual(
            65,
            mb_strlen($title),
            "Title '{$title}' in {$relativePath} is too long (" . mb_strlen($title) . " chars). Maximum allowed is 65."
        );

        // 2. Meta description validation
        $this->assertMatchesRegularExpression('/^meta_desc:\s*.+/m', $frontmatter, "Missing 'meta_desc' in {$relativePath}");
        preg_match('/^meta_desc:\s*["\']?(.*?)["\']?\s*$/m', $frontmatter, $descMatches);
        $desc = trim($descMatches[1] ?? '');
        $this->assertNotEmpty($desc, "meta_desc cannot be empty in {$relativePath}");
        $this->assertGreaterThanOrEqual(
            120,
            mb_strlen($desc),
            "meta_desc in {$relativePath} is too short (" . mb_strlen($desc) . " chars). Minimum required is 120."
        );
        $this->assertLessThanOrEqual(
            160,
            mb_strlen($desc),
            "meta_desc in {$relativePath} is too long (" . mb_strlen($desc) . " chars). Maximum allowed is 160."
        );

        // 3. Date presence
        $this->assertMatchesRegularExpression('/^date:\s*.+/m', $frontmatter, "Missing 'date' in {$relativePath}");
    }
}
