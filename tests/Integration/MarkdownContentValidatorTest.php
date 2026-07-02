<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Core\BlogRepository;

class MarkdownContentValidatorTest extends TestCase
{
    /**
     * Dynamically discovers and yields all markdown files (calculators and blog posts).
     */
    public static function markdownFilesProvider(): array
    {
        $contentDir = __DIR__ . '/../../content';
        $files = [];

        // Discover calculators
        $calcFiles = glob($contentDir . '/calculators/*.md');
        if ($calcFiles) {
            foreach ($calcFiles as $f) {
                $files['calculators/' . basename($f)] = [$f, 'calculator', null];
            }
        }

        // Discover blog posts
        $categories = ['growth', 'retirement', 'comparison'];
        foreach ($categories as $cat) {
            $blogFiles = glob($contentDir . '/blog/' . $cat . '/*.md');
            if ($blogFiles) {
                foreach ($blogFiles as $f) {
                    $files['blog/' . $cat . '/' . basename($f)] = [$f, 'blog', $cat];
                }
            }
        }

        return $files;
    }

    /**
     * Verifies frontmatter structure, heading constraints, relative links,
     * local image presence, and repository alignment for all content.
     */
    #[DataProvider('markdownFilesProvider')]
    public function testMarkdownFileStructure(string $filePath, string $type, ?string $category): void
    {
        $fileName = basename($filePath);
        $this->assertFileExists($filePath, "File '$fileName' does not exist.");
        $this->assertIsReadable($filePath, "File '$fileName' is not readable.");

        $content = file_get_contents($filePath);
        $this->assertNotEmpty($content, "Markdown file '$fileName' must not be empty.");

        $lines = explode("\n", $content);

        // 1. Heading Heading 1 Validation
        $this->assertStringStartsWith(
            '# ',
            $lines[0],
            "Markdown file '$fileName' must start with an H1 heading beginning with '# '."
        );
        $title = trim(substr($lines[0], 2));
        $this->assertNotEmpty($title, "H1 title heading in '$fileName' must not be empty.");

        // Assert title conforms to search engine optimal limits (typically under 85 characters)
        $this->assertLessThanOrEqual(
            85,
            strlen($title),
            "Heading title '$title' in '$fileName' is too long for SEO. (Length: " . strlen($title) . ", max: 85)"
        );

        // 2. Validate Frontmatter Separator (---) Position (look up to line 5 / index 4)
        $separatorIndex = -1;
        for ($i = 1; $i <= 4; $i++) {
            if (isset($lines[$i]) && trim($lines[$i]) === '---') {
                $separatorIndex = $i;
                break;
            }
        }

        $this->assertGreaterThan(
            0,
            $separatorIndex,
            "Markdown file '$fileName' is missing frontmatter separator line '---' in the first 5 lines."
        );

        // 3. Subtitle Validation (if separator is found after line 2, line 2 must be the subtitle description)
        if ($separatorIndex > 1) {
            $subtitle = trim($lines[1]);
            $this->assertNotEmpty($subtitle, "Subtitle description on line 2 of '$fileName' must not be empty.");
            $this->assertLessThanOrEqual(
                200,
                strlen($subtitle),
                "Subtitle '$subtitle' in '$fileName' is too long. (Length: " . strlen($subtitle) . ", max: 200)"
            );
        }

        // 4. Body Content Presence
        $bodyLines = array_slice($lines, $separatorIndex + 1);
        $body = trim(implode("\n", $bodyLines));
        $this->assertNotEmpty($body, "Markdown file '$fileName' has no content in the body section.");

        // 5. Relative Markdown Link Check (detects links pointing to .md files which will break routes)
        // RegEx matches standard relative markdown links ending in .md, e.g. [Anchor](guide.md)
        $this->assertDoesNotMatchRegularExpression(
            '/\[.*?\]\((?!http|https|#)(.*?\.md)\)/i',
            $body,
            "Markdown file '$fileName' contains a relative link pointing to a '.md' file. Link targets must use clean routed URLs."
        );

        // 6. Verify Local Referenced Images
        // RegEx matches local image references, e.g., ![Alt text](/assets/images/pic.png)
        if (preg_match_all('/!\[.*?\]\((?!http|https)(.*?)\)/i', $body, $matches)) {
            foreach ($matches[1] as $imagePath) {
                // Strip query parameters/hashes from image paths
                if (false !== $pos = strpos($imagePath, '?')) {
                    $imagePath = substr($imagePath, 0, $pos);
                }
                if (false !== $pos = strpos($imagePath, '#')) {
                    $imagePath = substr($imagePath, 0, $pos);
                }

                $fullImagePath = __DIR__ . '/../../' . ltrim($imagePath, '/');
                $this->assertFileExists(
                    $fullImagePath,
                    "Markdown file '$fileName' references local image '$imagePath' that does not exist at '$fullImagePath'."
                );
            }
        }

        // 7. Verify Alignment with BlogRepository (Blog posts only)
        if ($type === 'blog') {
            $slug = basename($filePath, '.md');
            $allPosts = BlogRepository::getAllPosts();

            $found = false;
            $postMetadata = null;

            foreach ($allPosts as $post) {
                if ($post['category'] === $category && basename($post['href']) === $slug) {
                    $found = true;
                    $postMetadata = $post;
                    break;
                }
            }

            $this->assertTrue(
                $found,
                "Blog post file '$slug.md' in '$category' category is not registered or active in BlogRepository configs."
            );

            $this->assertNotEmpty($postMetadata['tag'], "Blog post '$slug' must have a non-empty tag config.");
            $this->assertNotEmpty($postMetadata['tag_color'], "Blog post '$slug' must have a non-empty tag_color config.");
            $this->assertNotEmpty($postMetadata['date'], "Blog post '$slug' must have a non-empty date config.");
        }
    }
}
