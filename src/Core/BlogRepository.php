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
    private string $categoriesJsonPath;
    private ?array $cachedCategories = null;
    private ?array $cachedPosts = null;

    public function __construct(
        ContentManager $contentManager,
        ?string $categoriesJsonPath = null
    ) {
        $this->contentManager = $contentManager;
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

        $posts = [];
        $categories = $this->getCategories();
        $categoryList = [];

        foreach ($categories as $slug => $cat) {
            $categoryList[] = (string) $slug;
        }

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
            $dateA = $this->parsePostDate((string) ($a['date'] ?? ''));
            $dateB = $this->parsePostDate((string) ($b['date'] ?? ''));
            return $dateB <=> $dateA;
        });

        $this->cachedPosts = $posts;
        return $this->cachedPosts;
    }

    /**
     * Group all blog posts by their SEO category.
     *
     * @return array<string, array>
     */
    public function getPostsGroupedByCategory(): array
    {
        $allPosts = $this->getAllPosts();
        $postsByCat = [];
        foreach ($allPosts as $post) {
            $cat = (string) ($post['seo_category'] ?? '');
            if ($cat !== '') {
                $postsByCat[$cat][] = $post;
            }
        }
        return $postsByCat;
    }

    private function parsePostDate(string $rawDate): \DateTimeImmutable
    {
        $formats = ['F Y', 'Y-m-d', 'd F Y', 'Y-m', 'M Y', 'F d, Y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            $parsed = \DateTimeImmutable::createFromFormat($fmt, trim($rawDate));
            if ($parsed !== false) {
                return $parsed;
            }
        }
        try {
            return new \DateTimeImmutable($rawDate);
        } catch (\Throwable) {
            return new \DateTimeImmutable('1970-01-01');
        }
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
        $readTime = (string) ($meta['read_time'] ?? '5 min');

        return $this->buildPostData($category, $slug, $meta, $readTime);
    }

    private function buildPostData(string $category, string $slug, array $meta, string $readTime): array
    {
        return [
            'seo_category' => $category,
            'id'           => "{$category}/{$slug}",
            'slug'         => $slug,
            'tag'          => $meta['tag'] ?? 'Guide',
            'tag_color'    => $meta['tag_color'] ?? 'slate',
            'title'        => !empty($meta['title']) ? $meta['title'] : ucfirst(str_replace('-', ' ', $slug)),
            'desc'         => $meta['subtitle'] ?? '',
            'href'         => "/resource/{$category}/{$slug}",
            'featured'     => $meta['featured'] ?? false,
            'read_time'    => $readTime,
            'date'         => $meta['date'] ?? DateConstants::CONTENT_FALLBACK_DATE,
        ];
    }

    /**
     * Get the last modification date of a blog post file.
     */
    public function getPostModifiedDate(string $category, string $slug): string
    {
        return $this->contentManager->getFileModifiedDate('blog/' . $category . '/' . $slug);
    }
}
