<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\DownloadCsvAction;
use Controllers\ErrorController;
use Controllers\GeneratePdfAction;
use Controllers\LogInsightApiAction;
use Controllers\RenderGuideAction;
use Controllers\RenderHomeAction;
use Controllers\ShowAdminDashboardAction;
use Controllers\SitemapController;
use Core\ActionDispatcher;
use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\AnonymizedInsightLogger;
use Core\App;
use Core\BlogRepository;
use Core\Container;
use Core\ContentManager;
use Core\CurrencyFormatterInterface;
use Core\DatabaseMigrator;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\InsightRepository;
use Core\InvestmentCalculator;
use Core\MetaManager;
use Core\PdfTemplateInterface;
use Core\Router;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\Strategies\StrategyFactory;
use Core\Twig\AppTwigExtension;
use Core\ViewRenderer;
use Core\ViteHelper;
use Parsedown;
use PDO;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\CsvExportService;
use Services\FileUploadService;
use Services\GuideRenderer;
use Services\HtmlSanitizer;
use Services\PdfGeneratorService;
use Services\RateLimiter;
use Services\RateLimitStorageInterface;
use Services\SessionManager;

class ServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = App::createContainer();
    }

    public function testCoreServiceProviderBindings(): void
    {
        $this->assertInstanceOf(PDO::class, $this->container->get(PDO::class));
        $this->assertInstanceOf(SiteConfig::class, $this->container->get(SiteConfig::class));
        $this->assertInstanceOf(ViteHelper::class, $this->container->get(ViteHelper::class));
        $this->assertInstanceOf(ConfigService::class, $this->container->get(ConfigService::class));
        $this->assertInstanceOf(\Services\ConfigServiceInterface::class, $this->container->get(\Services\ConfigServiceInterface::class));
        $this->assertInstanceOf(CsvExportService::class, $this->container->get(CsvExportService::class));
        $this->assertInstanceOf(SessionManager::class, $this->container->get(SessionManager::class));
        $this->assertInstanceOf(\Services\SessionManagerInterface::class, $this->container->get(\Services\SessionManagerInterface::class));
        $this->assertInstanceOf(CurrencyFormatterInterface::class, $this->container->get(CurrencyFormatterInterface::class));
        $this->assertInstanceOf(AppTwigExtension::class, $this->container->get(AppTwigExtension::class));
        $this->assertInstanceOf(ViewRenderer::class, $this->container->get(ViewRenderer::class));
        $this->assertInstanceOf(Parsedown::class, $this->container->get(Parsedown::class));
        $this->assertInstanceOf(ContentManager::class, $this->container->get(ContentManager::class));
        $this->assertInstanceOf(DatabaseMigrator::class, $this->container->get(DatabaseMigrator::class));
        $this->assertInstanceOf(AdminAuthService::class, $this->container->get(AdminAuthService::class));
        $this->assertInstanceOf(InvestmentCalculator::class, $this->container->get(InvestmentCalculator::class));
        $this->assertInstanceOf(StrategyFactory::class, $this->container->get(StrategyFactory::class));
        $this->assertInstanceOf(ActionDispatcher::class, $this->container->get(ActionDispatcher::class));
        $this->assertInstanceOf(Router::class, $this->container->get(Router::class));
    }

    public function testRepositoryServiceProviderBindings(): void
    {
        $this->assertInstanceOf(MetaManager::class, $this->container->get(MetaManager::class));
        $this->assertInstanceOf(SchemaHelper::class, $this->container->get(SchemaHelper::class));
        $this->assertInstanceOf(FaqRepository::class, $this->container->get(FaqRepository::class));
        $this->assertInstanceOf(GlossaryRepository::class, $this->container->get(GlossaryRepository::class));
        $this->assertInstanceOf(BlogRepository::class, $this->container->get(BlogRepository::class));
        $this->assertInstanceOf(InsightRepository::class, $this->container->get(InsightRepository::class));
        $this->assertInstanceOf(AnonymizedInsightLogger::class, $this->container->get(AnonymizedInsightLogger::class));
        $this->assertInstanceOf(SchemaFactory::class, $this->container->get(SchemaFactory::class));
    }

    public function testDomainServiceProviderBindings(): void
    {
        $this->assertInstanceOf(AdminDashboardPresenter::class, $this->container->get(AdminDashboardPresenter::class));
        $this->assertInstanceOf(\Services\GuideViewModelBuilder::class, $this->container->get(\Services\GuideViewModelBuilder::class));
        $this->assertInstanceOf(GuideRenderer::class, $this->container->get(GuideRenderer::class));
        $this->assertInstanceOf(\Core\Strategies\SipStrategy::class, $this->container->get(\Core\Strategies\SipStrategy::class));
        $this->assertInstanceOf(\Core\Strategies\SwpStrategy::class, $this->container->get(\Core\Strategies\SwpStrategy::class));
        $this->assertInstanceOf(\Core\Strategies\LumpsumStrategy::class, $this->container->get(\Core\Strategies\LumpsumStrategy::class));
        $this->assertInstanceOf(\Core\Strategies\TargetCorpusStrategy::class, $this->container->get(\Core\Strategies\TargetCorpusStrategy::class));
        $this->assertInstanceOf(\Core\Strategies\ComboStrategy::class, $this->container->get(\Core\Strategies\ComboStrategy::class));
    }

    public function testControllerServiceProviderBindings(): void
    {
        $this->assertInstanceOf(\Core\Middleware\HoneypotMiddleware::class, $this->container->get(\Core\Middleware\HoneypotMiddleware::class));
        $this->assertInstanceOf(\Core\Middleware\AdminCsrfMiddleware::class, $this->container->get(\Core\Middleware\AdminCsrfMiddleware::class));
        $this->assertInstanceOf(RateLimitStorageInterface::class, $this->container->get(RateLimitStorageInterface::class));
        $this->assertInstanceOf(RateLimiter::class, $this->container->get(RateLimiter::class));
        $this->assertInstanceOf(PdfTemplateInterface::class, $this->container->get(PdfTemplateInterface::class));
        $this->assertInstanceOf(PdfGeneratorService::class, $this->container->get(PdfGeneratorService::class));
        $this->assertInstanceOf(HtmlSanitizer::class, $this->container->get(HtmlSanitizer::class));
        $this->assertInstanceOf(FileUploadService::class, $this->container->get(FileUploadService::class));
        $this->assertInstanceOf(ShowAdminDashboardAction::class, $this->container->get(ShowAdminDashboardAction::class));
        $this->assertInstanceOf(LogInsightApiAction::class, $this->container->get(LogInsightApiAction::class));
        $this->assertInstanceOf(RenderHomeAction::class, $this->container->get(RenderHomeAction::class));
        $this->assertInstanceOf(DownloadCsvAction::class, $this->container->get(DownloadCsvAction::class));
        $this->assertInstanceOf(GeneratePdfAction::class, $this->container->get(GeneratePdfAction::class));
        $this->assertInstanceOf(SitemapController::class, $this->container->get(SitemapController::class));
        $this->assertInstanceOf(ErrorController::class, $this->container->get(ErrorController::class));
        $this->assertInstanceOf(\Controllers\ListResourcesAction::class, $this->container->get(\Controllers\ListResourcesAction::class));
        $this->assertInstanceOf(\Controllers\ShowResourceCategoryAction::class, $this->container->get(\Controllers\ShowResourceCategoryAction::class));
        $this->assertInstanceOf(\Controllers\ShowResourcePostAction::class, $this->container->get(\Controllers\ShowResourcePostAction::class));
        $this->assertInstanceOf(RenderGuideAction::class, $this->container->get(RenderGuideAction::class));
    }
}
