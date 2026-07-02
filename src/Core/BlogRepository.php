<?php

declare(strict_types=1);

namespace Core;

/**
 * BlogRepository
 * Dynamically queries and retrieves blog categories and post configurations.
 */
class BlogRepository
{
    private static array $postConfigs = [
        'sip-for-beginners' => ['tag' => 'Beginner', 'tag_color' => 'emerald', 'featured' => true, 'date' => 'March 2026'],
        '20-year-wealth-blueprint-step-up-sip' => ['tag' => 'Strategy', 'tag_color' => 'emerald', 'date' => 'February 2026'],
        'reach-1-crore-rupees-via-sip' => ['tag' => 'Milestone', 'tag_color' => 'emerald', 'date' => 'January 2026'],
        'inflation-impact-on-sip' => ['tag' => 'Inflation', 'tag_color' => 'emerald', 'date' => 'March 2026'],
        'earning-30k-at-25-investment-blueprint' => ['tag' => 'Blueprint', 'tag_color' => 'emerald', 'date' => 'July 2026'],
        'retirement-planning-4-percent-swp-rule' => ['tag' => 'Strategy', 'tag_color' => 'indigo', 'featured' => true, 'date' => 'March 2026'],
        'sip-vs-swp-wealth-creation-withdrawal-strategy' => ['tag' => 'Lifecycle', 'tag_color' => 'indigo', 'date' => 'February 2026'],
        'swp-retirement-planning' => ['tag' => 'Planning', 'tag_color' => 'indigo', 'date' => 'March 2026'],
        'sip-vs-fd-vs-ppf' => ['tag' => 'Comparison', 'tag_color' => 'amber', 'featured' => true, 'date' => 'March 2026'],
        'swp-vs-fixed-deposit' => ['tag' => 'Comparison', 'tag_color' => 'amber', 'date' => 'February 2026'],
        'swp-vs-annuity-2026' => ['tag' => 'Comparison', 'tag_color' => 'amber', 'date' => 'January 2026'],
        'mutual-fund-tax-2026' => ['tag' => 'Tax', 'tag_color' => 'amber', 'date' => 'March 2026'],
        'mf-returns-benchmarks' => ['tag' => 'Benchmarks', 'tag_color' => 'amber', 'date' => 'January 2026'],
    ];

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

                $config = self::$postConfigs[$slug] ?? [];

                // Calculate dynamic read time: count body words and divide by average reading speed (200 wpm)
                $wordCount = str_word_count(strip_tags($content['html']));
                $readTimeVal = (int)ceil($wordCount / 200);
                $readTime = $readTimeVal . ' min';

                $posts[] = [
                    'category' => $cat,
                    'id' => $cat,
                    'tag' => $config['tag'] ?? 'Guide',
                    'tag_color' => $config['tag_color'] ?? 'slate',
                    'title' => $content['metadata']['title'] ?: ucfirst(str_replace('-', ' ', $slug)),
                    'desc' => $content['metadata']['subtitle'] ?: '',
                    'href' => "/resource/{$cat}/{$slug}",
                    'featured' => $config['featured'] ?? false,
                    'read_time' => $readTime,
                    'date' => $config['date'] ?? 'March 2026'
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
