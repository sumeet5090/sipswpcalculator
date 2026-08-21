<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\BlogRepository;
use Core\ContentManager;
use Core\SiteConfig;
use Core\ViewRenderer;
use Parsedown;
use PHPUnit\Framework\TestCase;
use Services\SitemapGenerator;

class SitemapGeneratorTest extends TestCase
{
    public function testGenerateUrlNodesContainsExpectedSections(): void
    {
        $siteConfig = new SiteConfig('https://sipswpcalculator.com');
        $contentManager = new ContentManager(new Parsedown(), __DIR__ . '/../../content');
        $blogRepository = new BlogRepository($contentManager);
        $viewRenderer = new ViewRenderer(new \Core\ViteHelper('testing'), 'testing', 'https://sipswpcalculator.com', __DIR__ . '/../../src/Views');

        $routesConfig = [
            'calculators' => [
                '/sip-calculator' => ['priority' => '0.9', 'changefreq' => 'monthly'],
            ],
            'pages' => [
                '/about' => ['priority' => '0.5', 'changefreq' => 'yearly'],
            ],
        ];

        $generator = new SitemapGenerator(
            $blogRepository,
            $siteConfig,
            $routesConfig,
            $viewRenderer,
            $contentManager
        );

        $urls = $generator->generateUrlNodes();

        $this->assertNotEmpty($urls);

        $locs = array_column($urls, 'loc');
        $this->assertContains('https://sipswpcalculator.com/', $locs);
        $this->assertContains('https://sipswpcalculator.com/sip-calculator', $locs);
        $this->assertContains('https://sipswpcalculator.com/about', $locs);
        $this->assertContains('https://sipswpcalculator.com/resources', $locs);

        foreach ($urls as $url) {
            $this->assertArrayHasKey('loc', $url);
            $this->assertArrayHasKey('lastmod', $url);
            $this->assertArrayHasKey('changefreq', $url);
            $this->assertArrayHasKey('priority', $url);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $url['lastmod']);
        }
    }
}
