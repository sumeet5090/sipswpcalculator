<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\BlogRepository;
use Core\ContentManager;
use Core\Exceptions\RouteNotFoundException;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\Strategies\StrategyFactory;
use Core\ViewRenderer;
use Core\ViteHelper;
use Parsedown;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\GuideRenderer;
use Services\GuideViewModelBuilder;

class GuideViewModelBuilderTest extends TestCase
{
    private GuideViewModelBuilder $builder;

    protected function setUp(): void
    {
        $siteConfig = new SiteConfig('https://sipswpcalculator.com');
        $metaManager = new MetaManager($siteConfig);
        $schemaHelper = new SchemaHelper($siteConfig, 'Sumeet Boga', 'SIP SWP Calculator');
        $contentManager = new ContentManager(new Parsedown(), __DIR__ . '/../../content');
        $faqRepository = new FaqRepository(__DIR__ . '/../../content/faqs.json');
        $blogRepository = new BlogRepository($contentManager);
        $schemaFactory = new SchemaFactory($schemaHelper, $siteConfig);
        $configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $strategyFactory = new StrategyFactory($configService);

        $this->builder = new GuideViewModelBuilder(
            $contentManager,
            $metaManager,
            $schemaFactory,
            $faqRepository,
            $blogRepository,
            $strategyFactory,
            $configService
        );
    }

    public function testBuildValidCalculatorGuide(): void
    {
        $result = $this->builder->build('sip-calculator');

        $this->assertArrayHasKey('layout', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('calculators/calculator-guide', $result['layout']);

        $data = $result['data'];
        $this->assertArrayHasKey('sip', $data);
        $this->assertArrayHasKey('years', $data);
        $this->assertArrayHasKey('rate', $data);
        $this->assertArrayHasKey('stepup', $data);
        $this->assertArrayHasKey('content_html', $data);
        $this->assertArrayHasKey('active_page', $data);
        $this->assertSame('sip-calculator', $data['active_page']);
        $this->assertArrayHasKey('calculator_type', $data);
        $this->assertSame('sip', $data['calculator_type']);
    }

    public function testBuildRetirementCalculatorUsesComboStrategy(): void
    {
        $result = $this->builder->build('retirement-calculator');

        $data = $result['data'];
        $this->assertArrayHasKey('calculator_type', $data);
        $this->assertSame('combo', $data['calculator_type']);
    }

    public function testBuildSip5000PerMonthOverridesDefaultSip(): void
    {
        $result = $this->builder->build('sip-5000-per-month');

        $data = $result['data'];
        $this->assertArrayHasKey('sip', $data);
        $this->assertSame(5000.0, (float) $data['sip']);
        $this->assertSame('sip', $data['calculator_type']);
    }

    public function testBuildReach5CroreOverridesTargetCorpus(): void
    {
        $result = $this->builder->build('reach-5-crore-via-sip');

        $data = $result['data'];
        $this->assertArrayHasKey('target_corpus', $data);
        $this->assertSame(50000000.0, (float) $data['target_corpus']);
        $this->assertSame('target_corpus', $data['calculator_type']);
    }

    public function testBuildThrowsForInvalidSlug(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->builder->build('non-existent-calculator-guide-xyz');
    }

    public function testShowLumpsumVisibilityMatrixAndEmbedGuardrail(): void
    {
        foreach (['sip-calculator', 'sip-step-up-calculator', 'retirement-calculator', 'lumpsum-calculator'] as $slug) {
            $vm = $this->builder->build($slug);
            $this->assertTrue(
                (bool) ($vm['data']['show_lumpsum'] ?? false),
                "Route '{$slug}' must expose show_lumpsum = true"
            );
        }

        foreach (['swp-calculator', 'reach-5-crore-via-sip'] as $slug) {
            $vm = $this->builder->build($slug);
            $this->assertFalse(
                (bool) ($vm['data']['show_lumpsum'] ?? true),
                "Route '{$slug}' must keep show_lumpsum = false"
            );
        }

        $viewRenderer = new ViewRenderer(
            new ViteHelper('testing'),
            'testing',
            'https://sipswpcalculator.com',
            __DIR__ . '/../../src/Views'
        );
        $renderer = new GuideRenderer($this->builder, $viewRenderer);

        $pageHtml = $renderer->render('sip-calculator')->getContent();
        $this->assertStringContainsString('id="lumpsum-toggle-btn"', $pageHtml);
        $this->assertStringContainsString('id="lumpsum-drawer"', $pageHtml);

        $embedHtml = $renderer->renderEmbed('sip-calculator')->getContent();
        $this->assertStringNotContainsString('id="lumpsum-toggle-btn"', $embedHtml);
        $this->assertStringNotContainsString('id="lumpsum-drawer"', $embedHtml);
    }
}
