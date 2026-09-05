<?php

declare(strict_types=1);

namespace Services;

use Core\BlogRepository;
use Core\ContentManager;
use Core\SiteConfig;
use Core\ViewRenderer;

/**
 * SitemapGenerator
 * Dedicated domain service to aggregate all canonical URL nodes and last-modified dates for sitemap.xml.
 */
class SitemapGenerator
{
    private BlogRepository $blogRepository;
    private SiteConfig $siteConfig;
    private array $routesConfig;
    private ViewRenderer $viewRenderer;
    private ContentManager $contentManager;

    public function __construct(
        BlogRepository $blogRepository,
        SiteConfig $siteConfig,
        array $routesConfig,
        ViewRenderer $viewRenderer,
        ContentManager $contentManager
    ) {
        $this->blogRepository = $blogRepository;
        $this->siteConfig = $siteConfig;
        $this->routesConfig = $routesConfig;
        $this->viewRenderer = $viewRenderer;
        $this->contentManager = $contentManager;
    }

    /**
     * Generate an array of sitemap URL objects.
     *
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string, image?: array{loc: string, title: string}}>
     */
    public function generateUrlNodes(): array
    {
        $routesConfig = $this->routesConfig;
        $baseUrl = $this->siteConfig->getBaseUrl();

        $urls = [];

        // 1. Home Page
        $urls[] = [
            'loc' => $baseUrl . '/',
            'lastmod' => $this->viewRenderer->getTemplateModifiedDate('calculators/home'),
            'changefreq' => 'weekly',
            'priority' => '1.0',
            'image' => [
                'loc' => $baseUrl . '/assets/og-image-main.jpg',
                'title' => 'SIP & SWP Calculator Together — Dual Planner'
            ]
        ];

        // 2. Calculators & Milestone Goal Plans
        foreach ($routesConfig['calculators'] ?? [] as $path => $config) {
            $slug = ltrim($path, '/');
            $fileSlug = basename($slug);
            $lastmod = $this->contentManager->getFileModifiedDate('calculators/' . $fileSlug);
            $priority = is_array($config) && isset($config['priority']) ? (string) $config['priority'] : '0.8';
            $changefreq = is_array($config) && isset($config['changefreq']) ? (string) $config['changefreq'] : 'monthly';

            $node = [
                'loc' => $baseUrl . $path,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority
            ];

            try {
                $meta = $this->contentManager->getMetadataOnly('calculators/' . $fileSlug);
                $ogImage = $meta['og_image'] ?? '/assets/og/og-' . $fileSlug . '.jpg';
                $imgUrl = str_starts_with($ogImage, 'http') ? $ogImage : $baseUrl . '/' . ltrim($ogImage, '/');
                $node['image'] = [
                    'loc' => $imgUrl,
                    'title' => $meta['title'] ?? ucfirst(str_replace('-', ' ', $fileSlug))
                ];
            } catch (\Throwable) {
                // Keep node without image if metadata cannot be read
            }

            $urls[] = $node;
        }

        // 3. Blog Posts
        $posts = $this->blogRepository->getAllPosts();
        foreach ($posts as $post) {
            $slug = basename($post['href']);
            $lastmod = $this->blogRepository->getPostModifiedDate($post['seo_category'], $slug);

            $ogImage = $post['og_image'] ?? '/assets/og-image-main.jpg';
            $imgUrl = str_starts_with($ogImage, 'http') ? $ogImage : $baseUrl . '/' . ltrim($ogImage, '/');

            $urls[] = [
                'loc' => $baseUrl . $post['href'],
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.8',
                'image' => [
                    'loc' => $imgUrl,
                    'title' => $post['title'] ?? ''
                ]
            ];
        }

        // 4. Resources Index
        $urls[] = [
            'loc' => $baseUrl . '/resources',
            'lastmod' => $this->viewRenderer->getTemplateModifiedDate('pages/resources'),
            'changefreq' => 'weekly',
            'priority' => '0.7',
            'image' => [
                'loc' => $baseUrl . '/assets/og-image-main.jpg',
                'title' => 'Financial Planning Resources & Calculators'
            ]
        ];

        // 5. Static Pages
        foreach ($routesConfig['pages'] ?? [] as $path => $config) {
            if (is_array($config) && !empty($config['sitemap_exclude'])) {
                continue;
            }

            $slug = ltrim($path, '/');
            $lastmod = $this->viewRenderer->getTemplateModifiedDate('pages/' . $slug);
            $priority = is_array($config) && isset($config['priority']) ? (string) $config['priority'] : '0.5';
            $changefreq = is_array($config) && isset($config['changefreq']) ? (string) $config['changefreq'] : 'yearly';

            $urls[] = [
                'loc' => $baseUrl . $path,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority
            ];
        }

        return $urls;
    }
}
