<?php

declare(strict_types=1);

namespace Core;

/**
 * BlogRepository
 * Dynamically queries and retrieves blog categories and post configurations.
 */
class BlogRepository
{
    public static function getCategories(): array
    {
        return [
            'growth' => [
                'title' => 'Wealth Growth',
                'desc' => 'Master the compounding engines that drive 20-year wealth building for the modern investor.',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>',
                'accent' => 'emerald'
            ],
            'retirement' => [
                'title' => 'Retirement Hub',
                'desc' => 'Expert withdrawal strategies for generating reliable monthly income without outliving your savings.',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'accent' => 'indigo'
            ],
            'comparison' => [
                'title' => 'Strategy Center',
                'desc' => 'Direct comparisons between major investment vehicles and navigating the current tax landscape.',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
                'accent' => 'amber'
            ]
        ];
    }

    /**
     * Parse and build blog list details dynamically from markdown files.
     *
     * @return array
     */
    public static function getAllPosts(): array
    {
        $contentDir = __DIR__ . '/../../content/blog';
        $contentManager = new ContentManager();
        $posts = [];

        foreach (['growth', 'retirement', 'comparison'] as $cat) {
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
                $content = $contentManager->getParsedContent('/blog/' . $cat . '/' . $slug);
                if (!$content) {
                    continue;
                }

                $meta = $content['metadata'];

                // Calculate dynamic read time: count body words and divide by average reading speed (200 wpm)
                $wordCount = str_word_count(strip_tags($content['html']));
                $readTimeVal = (int)ceil($wordCount / 200);
                $readTime = $readTimeVal . ' min';

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
                return $b['featured'] ? -1 : 1;
            }
            return strcmp($b['date'], $a['date']);
        });

        return $posts;
    }
}
