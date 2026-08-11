<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\Http\Response;
use Core\SiteConfig;
use Core\ViewRenderer;

class SitemapController
{
    private BlogRepository $blogRepository;
    private SiteConfig $siteConfig;
    private array $routesConfig;
    private ViewRenderer $viewRenderer;

    public function __construct(
        BlogRepository $blogRepository,
        SiteConfig $siteConfig,
        array $routesConfig,
        ViewRenderer $viewRenderer
    ) {
        $this->blogRepository = $blogRepository;
        $this->siteConfig = $siteConfig;
        $this->routesConfig = $routesConfig;
        $this->viewRenderer = $viewRenderer;
    }

    public function index(): Response
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
        foreach (array_keys($routesConfig['calculators']) as $path) {
            $slug = ltrim($path, '/');
            $lastmod = $this->viewRenderer->getTemplateModifiedDate('calculators/' . $slug);
            $priority = in_array($path, ['/sip-calculator', '/swp-calculator'], true) ? '0.9' : '0.8';

            $urls[] = [
                'loc' => $baseUrl . $path,
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
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
        $configuredPages = array_keys($routesConfig['pages'] ?? []);
        foreach ($configuredPages as $path) {
            $slug = ltrim($path, '/');
            $lastmod = $this->viewRenderer->getTemplateModifiedDate('pages/' . $slug);
            $priority = in_array($path, ['/privacy', '/terms'], true) ? '0.3' : '0.5';

            $urls[] = [
                'loc' => $baseUrl . $path,
                'lastmod' => $lastmod,
                'changefreq' => 'yearly',
                'priority' => $priority
            ];
        }

        $xml = $this->viewRenderer->render('sitemap.xml', ['urls' => $urls]);

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
