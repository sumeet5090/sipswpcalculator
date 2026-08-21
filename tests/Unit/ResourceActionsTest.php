<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\ListResourcesAction;
use Controllers\ShowResourceCategoryAction;
use Controllers\ShowResourcePostAction;
use Core\BlogRepository;
use Core\ContentManager;
use Core\Exceptions\RouteNotFoundException;
use Core\Factories\SchemaFactory;
use Core\Http\Request;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\ViewRenderer;
use Parsedown;
use PHPUnit\Framework\TestCase;

class ResourceActionsTest extends TestCase
{
    private SiteConfig $siteConfig;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;
    private ViewRenderer $viewRenderer;
    private ContentManager $contentManager;
    private BlogRepository $blogRepository;
    private SchemaFactory $schemaFactory;

    protected function setUp(): void
    {
        $this->siteConfig = new SiteConfig('https://sipswpcalculator.com');
        $this->metaManager = new MetaManager($this->siteConfig);
        $this->schemaHelper = new SchemaHelper($this->siteConfig, 'Sumeet Boga', 'SIP SWP Calculator');
        $this->viewRenderer = new ViewRenderer(
            new \Core\ViteHelper('testing'),
            'testing',
            'https://sipswpcalculator.com',
            __DIR__ . '/../../src/Views'
        );
        $this->contentManager = new ContentManager(new Parsedown(), __DIR__ . '/../../content');
        $this->blogRepository = new BlogRepository($this->contentManager);
        $this->schemaFactory = new SchemaFactory($this->schemaHelper, $this->siteConfig);
    }

    public function testListResourcesActionReturnsHtmlResponse(): void
    {
        $action = new ListResourcesAction(
            $this->blogRepository,
            $this->schemaHelper,
            $this->metaManager,
            $this->viewRenderer
        );

        $response = $action(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testShowResourceCategoryActionReturnsHtmlResponse(): void
    {
        $action = new ShowResourceCategoryAction(
            $this->blogRepository,
            $this->schemaHelper,
            $this->metaManager,
            $this->siteConfig,
            $this->viewRenderer
        );

        $response = $action('growth', new Request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testShowResourceCategoryActionThrowsForInvalidCategory(): void
    {
        $action = new ShowResourceCategoryAction(
            $this->blogRepository,
            $this->schemaHelper,
            $this->metaManager,
            $this->siteConfig,
            $this->viewRenderer
        );

        $this->expectException(RouteNotFoundException::class);
        $action('non-existent-category-xyz', new Request());
    }

    public function testShowResourcePostActionReturnsHtmlResponse(): void
    {
        $action = new ShowResourcePostAction(
            $this->contentManager,
            $this->blogRepository,
            $this->metaManager,
            $this->schemaFactory,
            $this->siteConfig,
            $this->viewRenderer
        );

        $response = $action('growth', 'sip-for-beginners', new Request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testShowResourcePostActionThrowsForInvalidSlug(): void
    {
        $action = new ShowResourcePostAction(
            $this->contentManager,
            $this->blogRepository,
            $this->metaManager,
            $this->schemaFactory,
            $this->siteConfig,
            $this->viewRenderer
        );

        $this->expectException(RouteNotFoundException::class);
        $action('growth', 'non-existent-article-slug-xyz', new Request());
    }
}
