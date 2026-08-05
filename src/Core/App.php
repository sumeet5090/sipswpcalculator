<?php

declare(strict_types=1);

namespace Core;

use Controllers\AdminController;
use Controllers\BlogController;
use Controllers\GeneratePdfAction;
use Controllers\PageController;
use Controllers\RenderGuideAction;
use Controllers\RenderHomeAction;
use Controllers\SitemapController;
use Core\Http\Request;

/**
 * App
 * The application kernel class (Front Controller/Bootstrapper).
 * Orchestrates Dependency Injection registration, routes loading, and request dispatching.
 */
class App
{
    private Container $container;
    private Router $router;
    private array $routesConfig;

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
        $this->routesConfig = require __DIR__ . '/Config/routes.php';
    }

    /**
     * Bootstrap dependencies and routes without dispatching.
     */
    public function boot(): Container
    {
        $this->registerDependencies();
        $this->registerRoutes();
        return $this->container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Run the application bootstrap and request lifecycle.
     */
    public function run(?Request $request = null): void
    {
        $request = $request ?? Request::createFromGlobals();

        $this->boot();

        /** @var \Services\SessionManager $sessionManager */
        $sessionManager = $this->container->get(\Services\SessionManager::class);
        $sessionManager->start();

        try {
            $response = $this->router->dispatch($request);
        } catch (\Core\Exceptions\RouteNotFoundException $e) {
            /** @var ViewRenderer $viewRenderer */
            $viewRenderer = $this->container->get(ViewRenderer::class);
            $response = \Controllers\ErrorController::handle404($viewRenderer);
        } catch (\Throwable $e) {
            /** @var ViewRenderer $viewRenderer */
            $viewRenderer = $this->container->get(ViewRenderer::class);
            $response = \Controllers\ErrorController::handle500($e, $viewRenderer);
        }

        $response->send();
    }

    /**
     * Register core services in the DI Container.
     */
    private function registerDependencies(): void
    {
        // Bind managers and helpers as Singletons
        $this->container->singleton(SiteConfig::class, function () {
            return new SiteConfig((string) Env::get('APP_URL', 'https://sipswpcalculator.com'));
        });

        $this->container->singleton(ViteHelper::class, function () {
            return new ViteHelper((string) Env::get('ENVIRONMENT', 'development'));
        });

        $this->container->singleton(\Services\ConfigService::class, function () {
            return new \Services\ConfigService();
        });

        $this->container->singleton(\Services\CsvExportService::class, function () {
            return new \Services\CsvExportService();
        });

        $this->container->singleton(\Services\SessionManager::class, function () {
            return new \Services\SessionManager();
        });

        $this->container->singleton(\Services\RateLimiter::class, function () {
            return new \Services\RateLimiter();
        });

        $this->container->singleton(ViewRenderer::class, function (Container $c) {
            return new ViewRenderer(
                $c->get(\Services\SessionManager::class),
                $c->get(ViteHelper::class),
                (string) Env::get('ENVIRONMENT', 'development'),
                (string) Env::get('APP_URL', 'https://sipswpcalculator.com')
            );
        });

        $this->container->singleton(\Parsedown::class, function () {
            return new \Parsedown();
        });

        $this->container->singleton(ContentManager::class, function (Container $c) {
            return new ContentManager($c->get(\Parsedown::class));
        });

        $this->container->singleton(DatabaseMigrator::class, function (Container $c) {
            return new DatabaseMigrator($c->get(\PDO::class));
        });

        $this->container->singleton(AdminAuthService::class, function (Container $c) {
            return new AdminAuthService(
                $c->get(\Services\SessionManager::class),
                (string) Env::get('ADMIN_INSIGHTS_PASSWORD', '')
            );
        });

        $this->container->singleton(\Core\Strategies\StrategyFactory::class, function (Container $c) {
            return new \Core\Strategies\StrategyFactory(
                $c->get(\Services\ConfigService::class)
            );
        });

        $this->container->singleton(\Controllers\AdminController::class, function (Container $c) {
            return new \Controllers\AdminController(
                $c->get(InsightRepository::class),
                $c->get(AnonymizedInsightLogger::class),
                $c->get(AdminAuthService::class),
                $c->get(AdminDashboardPresenter::class),
                $c->get(DatabaseMigrator::class),
                $c->get(\Services\RateLimiter::class),
                $c->get(ViewRenderer::class)
            );
        });

        $this->container->singleton(\Controllers\RenderHomeAction::class, function (Container $c) {
            return new \Controllers\RenderHomeAction(
                $c->get(MetaManager::class),
                $c->get(\Services\ConfigService::class),
                $c->get(\Services\CsvExportService::class),
                $c->get(FaqRepository::class),
                $c->get(InvestmentCalculator::class),
                $c->get(\Services\SessionManager::class),
                $c->get(ViewRenderer::class)
            );
        });

        $this->container->singleton(\Controllers\GeneratePdfAction::class, function (Container $c) {
            return new \Controllers\GeneratePdfAction(
                $c->get(\Services\RateLimiter::class),
                $c->get(\Services\SessionManager::class)
            );
        });

        $this->container->singleton(MetaManager::class, function (Container $c) {
            return new MetaManager($c->get(SiteConfig::class));
        });

        $this->container->singleton(SchemaHelper::class, function (Container $c) {
            return new SchemaHelper($c->get(SiteConfig::class));
        });

        $this->container->singleton(FaqRepository::class, function () {
            return new FaqRepository();
        });

        $this->container->singleton(BlogRepository::class, function (Container $c) {
            return new BlogRepository($c->get(ContentManager::class));
        });

        $this->container->singleton(\PDO::class, function () {
            $dbPath = (string) Env::get('DB_PATH', __DIR__ . '/../../database/database.sqlite');
            $dir = dirname($dbPath);
            if (!file_exists($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException("Failed to create database directory: {$dir}");
            }
            if (!file_exists($dbPath) && touch($dbPath) === false) {
                throw new \RuntimeException("Failed to create database file: {$dbPath}");
            }
            return new \PDO('sqlite:' . $dbPath, null, null, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        });

        $this->container->singleton(InsightRepository::class, function (Container $c) {
            return new InsightRepository($c->get(\PDO::class));
        });

        $this->container->singleton(AnonymizedInsightLogger::class, function (Container $c) {
            return new AnonymizedInsightLogger($c->get(\PDO::class));
        });

        $this->container->singleton(\Core\Factories\SchemaFactory::class, function (Container $c) {
            return new \Core\Factories\SchemaFactory(
                $c->get(SchemaHelper::class),
                $c->get(SiteConfig::class)
            );
        });

        // Bind Controllers needing explicit DI
        $routesConfig = $this->routesConfig;
        $this->container->singleton(SitemapController::class, function (Container $c) use ($routesConfig) {
            return new SitemapController(
                $c->get(BlogRepository::class),
                $c->get(SiteConfig::class),
                $routesConfig
            );
        });

        $this->container->singleton(BlogController::class, function (Container $c) {
            return new BlogController(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaHelper::class),
                $c->get(BlogRepository::class),
                $c->get(\Core\Factories\SchemaFactory::class),
                $c->get(SiteConfig::class),
                $c->get(ViewRenderer::class)
            );
        });

        $this->container->singleton(PageController::class, function (Container $c) {
            return new PageController(
                $c->get(FaqRepository::class),
                $c->get(BlogRepository::class),
                $c->get(SchemaHelper::class),
                $c->get(ViewRenderer::class)
            );
        });

        // Bind the Strategy GuideRenderer
        $this->container->singleton(\Services\GuideRenderer::class, function (Container $c) {
            return new \Services\GuideRenderer(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(\Core\Factories\SchemaFactory::class),
                $c->get(FaqRepository::class),
                $c->get(BlogRepository::class),
                $c->get(\Core\Strategies\StrategyFactory::class),
                $c->get(ViewRenderer::class)
            );
        });
    }

    /**
     * Load routes from configuration and register them in the Router.
     */
    private function registerRoutes(): void
    {
        // Core landing pages
        $this->router->get('/', [RenderHomeAction::class, '__invoke']);
        $this->router->post('/', [RenderHomeAction::class, '__invoke']);
        $this->router->post('/generate-pdf', [GeneratePdfAction::class, '__invoke']);

        // Dynamic Calculators Registration
        foreach ($this->routesConfig['calculators'] as $calc => $action) {
            $this->router->get($calc, $action);
            $this->router->post($calc, $action);
            $this->router->redirect($calc . '.php', $calc);
        }

        // Dynamic Pages Registration
        foreach ($this->routesConfig['pages'] as $uri => $action) {
            $this->router->get($uri, $action);
            $this->router->redirect($uri . '.php', $uri);
        }

        // Dynamic Sitemap
        $this->router->get('/sitemap.xml', [SitemapController::class, 'index']);

        // Admin / Insight Routing
        $this->router->get('/admin_insights', [AdminController::class, 'insights']);
        $this->router->post('/admin_insights', [AdminController::class, 'insights']);
        $this->router->redirect('/admin_insights.php', '/admin_insights');
        $this->router->get('/admin_insights/migrate', [AdminController::class, 'runMigrations']);
        $this->router->get('/log_insight', [AdminController::class, 'logInsight']);
        $this->router->post('/log_insight', [AdminController::class, 'logInsight']);
        $this->router->redirect('/log_insight.php', '/log_insight');

        // Blog / Resources Routing
        $this->router->get('/resources', [BlogController::class, 'index']);
        $this->router->get('/resource', [BlogController::class, 'index']);
        $this->router->get('/resource/{category}/{slug}', [BlogController::class, 'show']);

        // Load Dynamic Redirects from JSON
        $redirectsPath = __DIR__ . '/../../content/redirects.json';
        if (file_exists($redirectsPath)) {
            $rawJson = file_get_contents($redirectsPath);
            $redirectsData = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Failed to parse content/redirects.json: " . json_last_error_msg());
            } elseif (is_array($redirectsData)) {
                if (isset($redirectsData['blog_redirects']) && is_array($redirectsData['blog_redirects'])) {
                    foreach ($redirectsData['blog_redirects'] as $slug => $target) {
                        $this->router->redirect("/resource/{$slug}", "/resource/{$target}");
                    }
                }

                if (isset($redirectsData['stubs']) && is_array($redirectsData['stubs'])) {
                    foreach ($redirectsData['stubs'] as $old => $new) {
                        $this->router->redirect($old, $new);
                        $this->router->redirect($old . '.php', $new);
                    }
                }
            }
        }
    }
}
