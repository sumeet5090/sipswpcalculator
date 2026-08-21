<?php

declare(strict_types=1);

namespace Core\Providers;

use Controllers\BlogController;
use Controllers\DownloadCsvAction;
use Controllers\ErrorController;
use Controllers\GeneratePdfAction;
use Controllers\ListResourcesAction;
use Controllers\LogInsightApiAction;
use Controllers\PageController;
use Controllers\ProcessAdminLoginAction;
use Controllers\ProcessAdminLogoutAction;
use Controllers\RenderAboutAction;
use Controllers\RenderFaqAction;
use Controllers\RenderGlossaryAction;
use Controllers\RenderGuideAction;
use Controllers\RenderHomeAction;
use Controllers\RenderPrivacyAction;
use Controllers\RenderTermsAction;
use Controllers\ShowAdminDashboardAction;
use Controllers\ShowAdminLoginAction;
use Controllers\ShowResourceCategoryAction;
use Controllers\ShowResourcePostAction;
use Controllers\SitemapController;
use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\AnonymizedInsightLogger;
use Core\BlogRepository;
use Core\Container;
use Core\ContentManager;
use Core\CurrencyFormatterInterface;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\InsightRepository;
use Core\InvestmentCalculator;
use Core\MetaManager;
use Core\PdfReportTemplate;
use Core\PdfTemplateInterface;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\ViewRenderer;
use Services\ConfigService;
use Services\CsvExportService;
use Services\FilenameSanitizer;
use Services\FileRateLimitStorage;
use Services\FileUploadService;
use Services\HtmlSanitizer;
use Services\PdfGeneratorService;
use Services\RateLimiter;
use Services\RateLimitStorageInterface;
use Services\SessionManager;
use Services\SitemapGenerator;

class ControllerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $routesConfig = $config['routesConfig'] ?? [];

        $container->singleton(RateLimitStorageInterface::class, function () {
            return new FileRateLimitStorage();
        });

        $container->singleton(RateLimiter::class, function (Container $c) {
            return new RateLimiter($c->get(RateLimitStorageInterface::class));
        });

        $container->singleton(PdfTemplateInterface::class, function (Container $c) {
            /** @var ConfigService $configService */
            $configService = $c->get(ConfigService::class);
            $defaults = $configService->getCalculatorDefaults();
            $milestones = $defaults['milestones'] ?? null;
            return new PdfReportTemplate($c->get(CurrencyFormatterInterface::class), $milestones);
        });

        $container->singleton(PdfGeneratorService::class, function (Container $c) {
            return new PdfGeneratorService($c->get(PdfTemplateInterface::class));
        });

        $container->singleton(HtmlSanitizer::class, function () {
            return new HtmlSanitizer();
        });

        $container->singleton(ShowAdminDashboardAction::class, function (Container $c) {
            return new ShowAdminDashboardAction(
                $c->get(InsightRepository::class),
                $c->get(AdminAuthService::class),
                $c->get(AdminDashboardPresenter::class),
                $c->get(ViewRenderer::class),
                $c->get(SessionManager::class)
            );
        });

        $container->singleton(FilenameSanitizer::class, function () {
            return new FilenameSanitizer();
        });

        $container->singleton(ShowAdminLoginAction::class, function (Container $c) {
            return new ShowAdminLoginAction(
                $c->get(ViewRenderer::class),
                $c->get(SessionManager::class)
            );
        });

        $container->singleton(ProcessAdminLoginAction::class, function (Container $c) {
            return new ProcessAdminLoginAction(
                $c->get(AdminAuthService::class),
                $c->get(ViewRenderer::class),
                $c->get(SessionManager::class),
                $c->get(RateLimiter::class),
                $c->get(ConfigService::class)
            );
        });

        $container->singleton(ProcessAdminLogoutAction::class, function (Container $c) {
            return new ProcessAdminLogoutAction(
                $c->get(AdminAuthService::class)
            );
        });

        $container->singleton(LogInsightApiAction::class, function (Container $c) {
            return new LogInsightApiAction(
                $c->get(AnonymizedInsightLogger::class),
                $c->get(RateLimiter::class),
                $c->get(ConfigService::class)
            );
        });

        $container->singleton(RenderHomeAction::class, function (Container $c) {
            return new RenderHomeAction(
                $c->get(MetaManager::class),
                $c->get(ConfigService::class),
                $c->get(FaqRepository::class),
                $c->get(ViewRenderer::class),
                $c->get(SchemaFactory::class)
            );
        });

        $container->singleton(DownloadCsvAction::class, function (Container $c) {
            return new DownloadCsvAction(
                $c->get(InvestmentCalculator::class),
                $c->get(ConfigService::class),
                $c->get(CsvExportService::class),
                $c->get(CurrencyFormatterInterface::class)
            );
        });

        $container->singleton(FileUploadService::class, function () {
            return new FileUploadService();
        });

        $container->singleton(GeneratePdfAction::class, function (Container $c) {
            return new GeneratePdfAction(
                $c->get(RateLimiter::class),
                $c->get(PdfGeneratorService::class),
                $c->get(ConfigService::class),
                $c->get(FileUploadService::class),
                $c->get(HtmlSanitizer::class),
                $c->get(InvestmentCalculator::class),
                $c->get(CurrencyFormatterInterface::class)
            );
        });

        $container->singleton(SitemapGenerator::class, function (Container $c) use ($routesConfig) {
            return new SitemapGenerator(
                $c->get(BlogRepository::class),
                $c->get(SiteConfig::class),
                $routesConfig,
                $c->get(ViewRenderer::class),
                $c->get(ContentManager::class)
            );
        });

        $container->singleton(SitemapController::class, function (Container $c) {
            return new SitemapController(
                $c->get(SitemapGenerator::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(ErrorController::class, function (Container $c) {
            $env = (string) \Core\Env::get('ENVIRONMENT', 'production');
            return new ErrorController($c->get(ViewRenderer::class), $env);
        });

        $container->singleton(ListResourcesAction::class, function (Container $c) {
            return new ListResourcesAction(
                $c->get(BlogRepository::class),
                $c->get(SchemaHelper::class),
                $c->get(MetaManager::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(ShowResourceCategoryAction::class, function (Container $c) {
            return new ShowResourceCategoryAction(
                $c->get(BlogRepository::class),
                $c->get(SchemaHelper::class),
                $c->get(MetaManager::class),
                $c->get(SiteConfig::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(ShowResourcePostAction::class, function (Container $c) {
            return new ShowResourcePostAction(
                $c->get(ContentManager::class),
                $c->get(BlogRepository::class),
                $c->get(MetaManager::class),
                $c->get(SchemaFactory::class),
                $c->get(SiteConfig::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(BlogController::class, function (Container $c) {
            return new BlogController(
                $c->get(ListResourcesAction::class),
                $c->get(ShowResourceCategoryAction::class),
                $c->get(ShowResourcePostAction::class)
            );
        });

        $container->singleton(RenderAboutAction::class, function (Container $c) {
            return new RenderAboutAction(
                $c->get(MetaManager::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(RenderFaqAction::class, function (Container $c) {
            return new RenderFaqAction(
                $c->get(FaqRepository::class),
                $c->get(ViewRenderer::class),
                $c->get(MetaManager::class)
            );
        });

        $container->singleton(RenderGlossaryAction::class, function (Container $c) {
            return new RenderGlossaryAction(
                $c->get(GlossaryRepository::class),
                $c->get(SchemaHelper::class),
                $c->get(ViewRenderer::class),
                $c->get(MetaManager::class)
            );
        });

        $container->singleton(RenderPrivacyAction::class, function (Container $c) {
            return new RenderPrivacyAction(
                $c->get(SchemaHelper::class),
                $c->get(ViewRenderer::class),
                $c->get(MetaManager::class)
            );
        });

        $container->singleton(RenderTermsAction::class, function (Container $c) {
            return new RenderTermsAction(
                $c->get(SchemaHelper::class),
                $c->get(ViewRenderer::class),
                $c->get(MetaManager::class)
            );
        });

        $container->singleton(PageController::class, function (Container $c) {
            return new PageController(
                $c->get(FaqRepository::class),
                $c->get(GlossaryRepository::class),
                $c->get(SchemaHelper::class),
                $c->get(ViewRenderer::class),
                $c->get(MetaManager::class)
            );
        });

        $container->singleton(RenderGuideAction::class, function (Container $c) {
            return new RenderGuideAction(
                $c->get(\Services\GuideRenderer::class)
            );
        });
    }
}
