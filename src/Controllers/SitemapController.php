<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\Http\Response;

class SitemapController
{
    private BlogRepository $blogRepository;

    public function __construct(?BlogRepository $blogRepository = null)
    {
        $this->blogRepository = $blogRepository ?? \Core\Container::getInstance()->get(BlogRepository::class);
    }

    public function index(): Response
    {
        $routesConfig = require __DIR__ . '/../Core/Config/routes.php';
        $baseUrl = 'https://sipswpcalculator.com';

        $urls = [];

        // 1. Home Page
        $urls[] = [
            'loc' => $baseUrl . '/',
            'lastmod' => date('Y-m-d', filemtime(__DIR__ . '/../Views/calculators/home.twig')),
            'changefreq' => 'weekly',
            'priority' => '1.0'
        ];

        // 2. Calculators
        foreach ($routesConfig['calculators'] as $path => $config) {
            $mdFile = __DIR__ . '/../../content/calculators' . $path . '.md';
            $lastmod = file_exists($mdFile) ? date('Y-m-d', filemtime($mdFile)) : $config['date'];

            $priority = in_array($path, ['/sip-calculator', '/swp-calculator']) ? '0.9' : '0.8';

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
            $mdFile = __DIR__ . '/../../content/blog/' . $post['category'] . '/' . basename($post['href']) . '.md';
            $lastmod = file_exists($mdFile) ? date('Y-m-d', filemtime($mdFile)) : date('Y-m-d', strtotime($post['date']));

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
            'lastmod' => date('Y-m-d', filemtime(__DIR__ . '/../Views/pages/resources.twig')),
            'changefreq' => 'weekly',
            'priority' => '0.7'
        ];

        // 5. Static Pages
        $pages = [
            '/about' => '0.5',
            '/faq' => '0.5',
            '/glossary' => '0.5',
            '/privacy' => '0.3',
            '/terms' => '0.3'
        ];

        foreach ($pages as $path => $priority) {
            $twigFile = __DIR__ . '/../Views/pages' . $path . '.twig';
            $lastmod = file_exists($twigFile) ? date('Y-m-d', filemtime($twigFile)) : date('Y-m-d');

            $urls[] = [
                'loc' => $baseUrl . $path,
                'lastmod' => $lastmod,
                'changefreq' => 'yearly',
                'priority' => $priority
            ];
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
