<?php

declare(strict_types=1);

namespace Core;

/**
 * BlogRepository
 * Dynamically queries and retrieves blog categories and post configurations.
 */
class BlogRepository
{
    private ContentManager $contentManager;
    private string $contentDir;
    private string $categoriesJsonPath;
    private ?array $cachedCategories = null;
    private ?array $cachedPosts = null;

    public function __construct(
        ContentManager $contentManager,
        ?string $contentDir = null,
        ?string $categoriesJsonPath = null
    ) {
        $this->contentManager = $contentManager;
        $this->contentDir = $contentDir ?? (__DIR__ . '/../../content/blog');
        $this->categoriesJsonPath = $categoriesJsonPath ?? (__DIR__ . '/../../content/categories.json');
    }

    public function getCategories(): array
    {
        if ($this->cachedCategories !== null) {
            return $this->cachedCategories;
        }

        $jsonPath = $this->categoriesJsonPath;
        if (!file_exists($jsonPath)) {
            $this->cachedCategories = [];
            return [];
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            error_log("Failed to read content/categories.json at: " . $jsonPath);
            $this->cachedCategories = [];
            return [];
        }
        $decoded = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to parse content/categories.json: " . json_last_error_msg());
            $this->cachedCategories = [];
            return [];
        }

        $this->cachedCategories = is_array($decoded) ? $decoded : [];
        return $this->cachedCategories;
    }

    /**
     * Parse and build blog list details dynamically from markdown files.
     *
     * @return array
     */
    public function getAllPosts(): array
    {
        if ($this->cachedPosts !== null) {
            return $this->cachedPosts;
        }

        $contentDir = $this->contentDir;
        $posts = [];

        $categories = $this->getCategories();
        $categoryList = [];
        foreach ($categories as $key => $cat) {
            $categoryList[] = is_string($key) && !is_numeric($key) ? $key : ($cat['id'] ?? $cat['slug'] ?? '');
        }
        $categoryList = array_filter($categoryList);

        foreach ($categoryList as $cat) {
            $slugs = $this->contentManager->listMarkdownFiles('blog/' . $cat);

            foreach ($slugs as $slug) {
                $meta = $this->contentManager->getMetadataOnly('/blog/' . $cat . '/' . $slug);
                if (!$meta) {
                    continue;
                }

                $readTime = $meta['read_time'] ?? '5 min';
                $posts[] = $this->buildPostData($cat, $slug, $meta, (string) $readTime);
            }
        }

        // Sort: Featured posts first, then sort remaining by date descending
        usort($posts, function ($a, $b) {
            if ($a['featured'] !== $b['featured']) {
                return $b['featured'] ? 1 : -1;
            }
            $dateA = \DateTimeImmutable::createFromFormat('F Y', $a['date']) ?: new \DateTimeImmutable('1970-01-01');
            $dateB = \DateTimeImmutable::createFromFormat('F Y', $b['date']) ?: new \DateTimeImmutable('1970-01-01');
            return $dateB <=> $dateA;
        });

        $this->cachedPosts = $posts;
        return $this->cachedPosts;
    }

    /**
     * Retrieve metadata for a single blog post by category and slug without scanning all files.
     */
    public function getPostBySlug(string $category, string $slug): ?array
    {
        $path = '/blog/' . $category . '/' . $slug;
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            return null;
        }

        $meta = $content['metadata'];
        $readTime = $this->calculateReadTime($content['html']);

        return $this->buildPostData($category, $slug, $meta, $readTime);
    }

    private function buildPostData(string $category, string $slug, array $meta, string $readTime): array
    {
        return [
            'category'  => $category,
            'id'        => "{$category}/{$slug}",
            'slug'      => $slug,
            'tag'       => $meta['tag'] ?? 'Guide',
            'tag_color' => $meta['tag_color'] ?? 'slate',
            'title'     => !empty($meta['title']) ? $meta['title'] : ucfirst(str_replace('-', ' ', $slug)),
            'desc'      => $meta['subtitle'] ?? '',
            'href'      => "/resource/{$category}/{$slug}",
            'featured'  => $meta['featured'] ?? false,
            'read_time' => $readTime,
            'date'      => $meta['date'] ?? DateConstants::CONTENT_FALLBACK_DATE,
        ];
    }

    /**
     * Calculate dynamic read time string based on HTML content word count.
     */
    private function calculateReadTime(string $htmlContent): string
    {
        $wordCount = str_word_count(strip_tags($htmlContent));
        $readTimeVal = (int) ceil($wordCount / 200);
        return $readTimeVal . ' min';
    }

    /**
     * Get the last modification date of a blog post file.
     */
    public function getPostModifiedDate(string $category, string $slug, string $fallbackDate = '2026-03-01'): string
    {
        return $this->contentManager->getFileModifiedDate('blog/' . $category . '/' . $slug, $fallbackDate);
    }
}
