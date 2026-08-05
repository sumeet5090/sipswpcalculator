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

    public function __construct(ContentManager $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    public function getCategories(): array
    {
        $jsonPath = __DIR__ . '/../../content/categories.json';
        if (!file_exists($jsonPath)) {
            return [];
        }

        $jsonContent = file_get_contents($jsonPath);
        $decoded = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to parse content/categories.json: " . json_last_error_msg());
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Parse and build blog list details dynamically from markdown files.
     *
     * @return array
     */
    public function getAllPosts(): array
    {
        $contentDir = __DIR__ . '/../../content/blog';
        $posts = [];

        $categories = $this->getCategories();
        $categoryList = [];
        foreach ($categories as $key => $cat) {
            $categoryList[] = is_string($key) && !is_numeric($key) ? $key : ($cat['id'] ?? $cat['slug'] ?? '');
        }
        $categoryList = array_filter($categoryList);

        foreach ($categoryList as $cat) {
            $dir = $contentDir . '/' . $cat;
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.md');
            if (!$files) {
                continue;
            }

            foreach ($files as $file) {
                $slug = basename($file, '.md');
                $content = $this->contentManager->getParsedContent('/blog/' . $cat . '/' . $slug);
                if (!$content) {
                    continue;
                }

                $meta = $content['metadata'];

                $readTime = $this->calculateReadTime($content['html']);

                $posts[] = [
                    'category' => $cat,
                    'id' => $cat,
                    'tag' => $meta['tag'] ?? 'Guide',
                    'tag_color' => $meta['tag_color'] ?? 'slate',
                    'title' => !empty($meta['title']) ? $meta['title'] : ucfirst(str_replace('-', ' ', $slug)),
                    'desc' => $meta['subtitle'] ?? '',
                    'href' => "/resource/{$cat}/{$slug}",
                    'featured' => $meta['featured'] ?? false,
                    'read_time' => $readTime,
                    'date' => $meta['date'] ?? 'March 2026'
                ];
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

        return $posts;
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

        return [
            'category' => $category,
            'id' => $category,
            'tag' => $meta['tag'] ?? 'Guide',
            'tag_color' => $meta['tag_color'] ?? 'slate',
            'title' => !empty($meta['title']) ? $meta['title'] : ucfirst(str_replace('-', ' ', $slug)),
            'desc' => $meta['subtitle'] ?? '',
            'href' => "/resource/{$category}/{$slug}",
            'featured' => $meta['featured'] ?? false,
            'read_time' => $readTime,
            'date' => $meta['date'] ?? 'March 2026'
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
        $mdFile = __DIR__ . '/../../content/blog/' . $category . '/' . $slug . '.md';
        return file_exists($mdFile) ? date('Y-m-d', filemtime($mdFile)) : $fallbackDate;
    }
}
