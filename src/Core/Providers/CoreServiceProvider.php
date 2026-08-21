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
        $defaultDbPath = __DIR__ . '/../../../database/database.sqlite';
        $sharedCandidates = [
            dirname(__DIR__, 4) . '/shared/database.sqlite',
            dirname(__DIR__, 3) . '/shared/database.sqlite',
        ];
        foreach ($sharedCandidates as $candidate) {
            if (file_exists($candidate) || file_exists(dirname($candidate))) {
                if (file_exists($candidate)) {
                    $defaultDbPath = $candidate;
                    break;
                }
            }
        }
        $dbPath = (string) Env::get('DB_PATH', $defaultDbPath);

        $container->singleton(PDO::class, function () use ($dbPath) {
            $dir = dirname($dbPath);
            if (!file_exists($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException("Failed to create database directory: {$dir}");
            }
            if (!file_exists($dbPath) && touch($dbPath) === false) {
                throw new \RuntimeException("Failed to create database file: {$dbPath}");
            }
            $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec('PRAGMA journal_mode = WAL;');
            $pdo->exec('PRAGMA synchronous = NORMAL;');
            $pdo->exec('PRAGMA busy_timeout = 5000;');
            return $pdo;
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

        $container->singleton(\Core\CurrencyFormatterInterface::class, function () {
            return new \Core\CurrencyHelper();
        });

        $container->singleton(\Core\Twig\AppTwigExtension::class, function (Container $c) {
            return new \Core\Twig\AppTwigExtension(
                $c->get(ViteHelper::class),
                $c->get(\Core\CurrencyFormatterInterface::class)
            );
        });

        $container->singleton(ViewRenderer::class, function (Container $c) use ($environment, $appUrl) {
            $viewsDir = __DIR__ . '/../../Views';
            $cacheDir = __DIR__ . '/../../../var/cache/twig';
            return new ViewRenderer(
                $c->get(ViteHelper::class),
                $environment,
                $appUrl,
                $viewsDir,
                file_exists($cacheDir) ? $cacheDir : null,
                $c->get(\Core\CurrencyFormatterInterface::class),
                $c->get(\Core\Twig\AppTwigExtension::class)
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
            return new StrategyFactory($c->get(ConfigService::class), null, $c);
        });

        $container->singleton(\Core\Middleware\SessionMiddleware::class, function (Container $c) {
            return new \Core\Middleware\SessionMiddleware($c->get(SessionManager::class));
        });

        $container->singleton(\Core\Middleware\CsrfHoneypotMiddleware::class, function (Container $c) {
            return new \Core\Middleware\CsrfHoneypotMiddleware(
                $c->get(SessionManager::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(\Core\ActionDispatcher::class, function (Container $c) {
            return new \Core\ActionDispatcher($c);
        });

        $container->singleton(\Core\Router::class, function (Container $c) {
            return new \Core\Router($c, $c->get(\Core\ActionDispatcher::class));
        });
    }
}
