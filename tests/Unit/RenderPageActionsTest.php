<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\RenderAboutAction;
use Controllers\RenderFaqAction;
use Controllers\RenderGlossaryAction;
use Controllers\RenderPrivacyAction;
use Controllers\RenderTermsAction;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\Http\Request;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\ViewRenderer;
use PHPUnit\Framework\TestCase;

class RenderPageActionsTest extends TestCase
{
    private SiteConfig $siteConfig;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;
    private ViewRenderer $viewRenderer;

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
    }

    public function testRenderAboutActionReturnsHtmlResponse(): void
    {
        $action = new RenderAboutAction($this->metaManager, $this->viewRenderer);
        $response = $action(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testRenderFaqActionReturnsHtmlResponse(): void
    {
        $faqRepo = new FaqRepository(__DIR__ . '/../../content/faqs.json');
        $action = new RenderFaqAction($faqRepo, $this->viewRenderer, $this->metaManager);
        $response = $action(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testRenderGlossaryActionReturnsHtmlResponse(): void
    {
        $glossaryRepo = new GlossaryRepository(__DIR__ . '/../../content/glossary.json');
        $action = new RenderGlossaryAction($glossaryRepo, $this->schemaHelper, $this->viewRenderer, $this->metaManager);
        $response = $action(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testRenderPrivacyActionReturnsHtmlResponse(): void
    {
        $action = new RenderPrivacyAction($this->schemaHelper, $this->viewRenderer, $this->metaManager);
        $response = $action(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }

    public function testRenderTermsActionReturnsHtmlResponse(): void
    {
        $action = new RenderTermsAction($this->schemaHelper, $this->viewRenderer, $this->metaManager);
        $response = $action(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type') ?? '');
    }
}
