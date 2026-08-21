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
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
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
            'priority' => '1.0'
        ];

        // 2. Calculators
        foreach ($routesConfig['calculators'] ?? [] as $path => $config) {
            $slug = ltrim($path, '/');
            $lastmod = $this->contentManager->getFileModifiedDate('calculators/' . $slug);
            $priority = is_array($config) && isset($config['priority']) ? (string) $config['priority'] : '0.8';
            $changefreq = is_array($config) && isset($config['changefreq']) ? (string) $config['changefreq'] : 'monthly';

            $urls[] = [
                'loc' => $baseUrl . $path,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority
            ];
        }

        // 3. Blog Posts
        $posts = $this->blogRepository->getAllPosts();
        foreach ($posts as $post) {
            $slug = basename($post['href']);
            $lastmod = $this->blogRepository->getPostModifiedDate($post['seo_category'], $slug);

            $urls[] = [
                'loc' => $baseUrl . $post['href'],
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.8'
            ];
        }

        // 4. Resources Index
        $urls[] = [
            'loc' => $baseUrl . '/resources',
            'lastmod' => $this->viewRenderer->getTemplateModifiedDate('pages/resources'),
            'changefreq' => 'weekly',
            'priority' => '0.7'
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
