<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\AdminAuthService;
use Core\Container;
use Core\ContentManager;
use Core\DatabaseMigrator;
use Core\Env;
use Core\InvestmentCalculator;
use Core\SiteConfig;
use Core\Strategies\StrategyFactory;
use Core\ViewRenderer;
use Core\ViteHelper;
use Parsedown;
use PDO;
use Services\ConfigService;
use Services\CsvExportService;
use Services\SessionManager;

class CoreServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $appUrl = (string) Env::get('APP_URL', 'https://sipswpcalculator.com');
        $environment = (string) Env::get('ENVIRONMENT', 'development');
        $dbPath = (string) Env::get('DB_PATH', __DIR__ . '/../../../database/database.sqlite');

        $container->singleton(PDO::class, function () use ($dbPath) {
            $dir = dirname($dbPath);
            if (!file_exists($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException("Failed to create database directory: {$dir}");
            }
            if (!file_exists($dbPath) && touch($dbPath) === false) {
                throw new \RuntimeException("Failed to create database file: {$dbPath}");
            }
            return new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        });

        $container->singleton(SiteConfig::class, function () use ($appUrl) {
            return new SiteConfig($appUrl);
        });

        $container->singleton(ViteHelper::class, function () use ($environment) {
            return new ViteHelper($environment);
        });

        $container->singleton(ConfigService::class, function () {
            return new ConfigService(__DIR__ . '/../../../content/calculator_defaults.json');
        });

        $container->singleton(CsvExportService::class, function () {
            return new CsvExportService();
        });

        $container->singleton(SessionManager::class, function () {
            return new SessionManager();
        });

        $container->singleton(ViewRenderer::class, function (Container $c) use ($environment, $appUrl) {
            return new ViewRenderer(
                $c->get(SessionManager::class),
                $c->get(ViteHelper::class),
                $environment,
                $appUrl
            );
        });

        $container->singleton(Parsedown::class, function () {
            return new Parsedown();
        });

        $container->singleton(ContentManager::class, function (Container $c) {
            return new ContentManager($c->get(Parsedown::class), __DIR__ . '/../../../content');
        });

        $container->singleton(DatabaseMigrator::class, function (Container $c) {
            return new DatabaseMigrator($c->get(PDO::class));
        });

        $container->singleton(AdminAuthService::class, function (Container $c) {
            return new AdminAuthService(
                $c->get(SessionManager::class),
                (string) Env::get('ADMIN_INSIGHTS_PASSWORD', '')
            );
        });

        $container->singleton(InvestmentCalculator::class, function () {
            return new InvestmentCalculator();
        });

        $container->singleton(StrategyFactory::class, function (Container $c) {
            return new StrategyFactory(
                $c->get(ConfigService::class),
                null,
                $c
            );
        });
    }
}
