<?php

declare(strict_types=1);

namespace Core\Providers;

use Controllers\AdminController;
use Controllers\BlogController;
use Controllers\DownloadCsvAction;
use Controllers\ErrorController;
use Controllers\GeneratePdfAction;
use Controllers\PageController;
use Controllers\RenderHomeAction;
use Controllers\SitemapController;
use Core\AdminDashboardPresenter;
use Core\AdminAuthService;
use Core\AnonymizedInsightLogger;
use Core\BlogRepository;
use Core\Container;
use Core\ContentManager;
use Core\DatabaseMigrator;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\InsightRepository;
use Core\InvestmentCalculator;
use Core\MetaManager;
use Core\Middleware\CsrfHoneypotMiddleware;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\Strategies\StrategyFactory;
use Core\ViewRenderer;
use Services\ConfigService;
use Services\CsvExportService;
use Services\GuideRenderer;
use Services\PdfGeneratorService;
use Services\RateLimiter;
use Services\SessionManager;

class ControllerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $routesConfig = $config['routesConfig'] ?? [];

        $container->singleton(CsrfHoneypotMiddleware::class, function (Container $c) {
            return new CsrfHoneypotMiddleware($c->get(SessionManager::class));
        });

        $container->singleton(\Services\RateLimitStorageInterface::class, function () {
            return new \Services\FileRateLimitStorage();
        });

        $container->singleton(RateLimiter::class, function (Container $c) {
            return new RateLimiter($c->get(\Services\RateLimitStorageInterface::class));
        });

        $container->singleton(PdfGeneratorService::class, function () {
            return new PdfGeneratorService();
        });

        $container->singleton(\Controllers\ShowAdminDashboardAction::class, function (Container $c) {
            return new \Controllers\ShowAdminDashboardAction(
                $c->get(InsightRepository::class),
                $c->get(AdminAuthService::class),
                $c->get(AdminDashboardPresenter::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(\Controllers\AdminAuthAction::class, function (Container $c) {
            return new \Controllers\AdminAuthAction(
                $c->get(AdminAuthService::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(\Controllers\LogInsightApiAction::class, function (Container $c) {
            return new \Controllers\LogInsightApiAction(
                $c->get(AnonymizedInsightLogger::class),
                $c->get(RateLimiter::class)
            );
        });

        $container->singleton(AdminController::class, function (Container $c) {
            return new AdminController(
                $c->get(InsightRepository::class),
                $c->get(AnonymizedInsightLogger::class),
                $c->get(AdminAuthService::class),
                $c->get(AdminDashboardPresenter::class),
                $c->get(RateLimiter::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(RenderHomeAction::class, function (Container $c) {
            return new RenderHomeAction(
                $c->get(MetaManager::class),
                $c->get(ConfigService::class),
                $c->get(FaqRepository::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(DownloadCsvAction::class, function (Container $c) {
            return new DownloadCsvAction(
                $c->get(InvestmentCalculator::class),
                $c->get(ConfigService::class),
                $c->get(CsvExportService::class)
            );
        });

        $container->singleton(\Services\FileUploadService::class, function () {
            return new \Services\FileUploadService();
        });

        $container->singleton(GeneratePdfAction::class, function (Container $c) {
            return new GeneratePdfAction(
                $c->get(RateLimiter::class),
                $c->get(SessionManager::class),
                $c->get(PdfGeneratorService::class),
                $c->get(ConfigService::class),
                $c->get(\Services\FileUploadService::class)
            );
        });

        $container->singleton(SitemapController::class, function (Container $c) use ($routesConfig) {
            return new SitemapController(
                $c->get(BlogRepository::class),
                $c->get(SiteConfig::class),
                $routesConfig,
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(ErrorController::class, function (Container $c) {
            return new ErrorController($c->get(ViewRenderer::class));
        });

        $container->singleton(BlogController::class, function (Container $c) {
            return new BlogController(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaHelper::class),
                $c->get(BlogRepository::class),
                $c->get(SchemaFactory::class),
                $c->get(SiteConfig::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(PageController::class, function (Container $c) {
            return new PageController(
                $c->get(FaqRepository::class),
                $c->get(GlossaryRepository::class),
                $c->get(SchemaHelper::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(GuideRenderer::class, function (Container $c) {
            return new GuideRenderer(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaFactory::class),
                $c->get(FaqRepository::class),
                $c->get(BlogRepository::class),
                $c->get(StrategyFactory::class),
                $c->get(ViewRenderer::class)
            );
        });
    }
}
