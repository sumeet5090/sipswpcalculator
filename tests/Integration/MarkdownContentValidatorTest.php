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

        $contentManager = new \Core\ContentManager(new \Parsedown(), __DIR__ . '/../../content');
        $basePath = str_replace(realpath(__DIR__ . '/../../content'), '', realpath($filePath));
        // Strip .md
        $basePath = substr($basePath, 0, -3);
        $parsed = $contentManager->getParsedContent($basePath);

        $this->assertNotNull($parsed, "ContentManager could not parse file '$fileName'.");
        $this->assertArrayHasKey('metadata', $parsed, "Parsed content missing metadata array.");

        $meta = $parsed['metadata'];
        $title = $meta['title'] ?? '';

        $this->assertNotEmpty($title, "Title in metadata of '$fileName' must not be empty.");

        // Assert title conforms to search engine optimal limits (typically under 85 characters)
        $this->assertLessThanOrEqual(
            85,
            strlen($title),
            "Metadata title '$title' in '$fileName' is too long for SEO. (Length: " . strlen($title) . ", max: 85)"
        );

        $subtitle = $meta['subtitle'] ?? '';
        if (!empty($subtitle)) {
            $this->assertLessThanOrEqual(
                200,
                strlen($subtitle),
                "Subtitle '$subtitle' in '$fileName' is too long. (Length: " . strlen($subtitle) . ", max: 200)"
            );
        }

        // 4. Body Content Presence & Heading Level Check
        $body = $parsed['html'];
        $this->assertNotEmpty($body, "Markdown file '$fileName' has no HTML content in the body section.");

        // Assert no raw H1 heading in markdown content (Twig layout provides single <h1>)
        $this->assertDoesNotMatchRegularExpression(
            '/^#\s+[^\n]+/m',
            $content,
            "Markdown file '$fileName' contains a raw H1 header ('# Title'). Markdown files must start at H2 ('## Section') as the page <h1> is rendered by Twig layouts."
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<h1[\s>]/i',
            $body,
            "Markdown file '$fileName' contains a raw <h1> tag. Markdown files must start at H2 as the page <h1> is rendered by Twig layouts."
        );

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
            $allPosts = (new BlogRepository(new \Core\ContentManager(new \Parsedown(), __DIR__ . '/../../content')))->getAllPosts();

            $found = false;
            $postMetadata = null;

            foreach ($allPosts as $post) {
                if ($post['seo_category'] === $category && basename($post['href']) === $slug) {
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
