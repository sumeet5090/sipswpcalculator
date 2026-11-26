<?php

declare(strict_types=1);

namespace Core;

use Controllers\DownloadCsvAction;
use Controllers\GeneratePdfAction;
use Controllers\ListResourcesAction;
use Controllers\LogInsightApiAction;
use Controllers\ProcessAdminLoginAction;
use Controllers\ProcessAdminLogoutAction;
use Controllers\RenderHomeAction;
use Controllers\ShowAdminDashboardAction;
use Controllers\ShowResourceCategoryAction;
use Controllers\ShowResourcePostAction;
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
    private ?Router $router = null;
    /** @var array<string, mixed> */
    private array $routesConfig = [];

    public function __construct(?Container $container = null, ?Router $router = null)
    {
        $this->container = $container ?? new Container();
        $this->router = $router;
    }

    /**
     * Bootstrap dependencies and routes without dispatching.
     */
    public function boot(): void
    {
        date_default_timezone_set('Asia/Kolkata');
        $this->routesConfig = require __DIR__ . '/Config/routes.php';
        $this->registerDependencies();
        if ($this->router === null) {
            /** @var Router $router */
            $router = $this->container->get(Router::class);
            $this->router = $router;
        }
        $this->registerRoutes();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Create and bootstrap a fully configured DI Container.
     */
    public static function createContainer(?Container $container = null): Container
    {
        $app = new self($container);
        $app->boot();
        return $app->getContainer();
    }

    public function getRouter(): Router
    {
        if ($this->router === null) {
            $this->boot();
        }
        if ($this->router === null) {
            throw new \RuntimeException('Router failed to initialize during application boot.');
        }
        return $this->router;
    }

    /**
     * Run the application bootstrap and request lifecycle.
     */
    public function run(?Request $request = null): void
    {
        $request = $request ?? Request::createFromGlobals();

        $this->boot();
        $router = $this->getRouter();

        try {
            $response = $router->dispatch($request);
        } catch (\Core\Exceptions\RouteNotFoundException $e) {
            /** @var \Controllers\ErrorController $errorController */
            $errorController = $this->container->get(\Controllers\ErrorController::class);
            $response = $errorController->render404();
        } catch (\Throwable $e) {
            /** @var \Controllers\ErrorController $errorController */
            $errorController = $this->container->get(\Controllers\ErrorController::class);
            $response = $errorController->render500($e);
        }

        $response->send();
    }

    /**
     * Register core services in the DI Container.
     */
    private function registerDependencies(): void
    {
        $providers = [
            new \Core\Providers\CoreServiceProvider(),
            new \Core\Providers\RepositoryServiceProvider(),
            new \Core\Providers\DomainServiceProvider(),
            new \Core\Providers\ControllerServiceProvider(),
        ];

        foreach ($providers as $provider) {
            $provider->register($this->container, [
                'routesConfig' => $this->routesConfig,
            ]);
        }
    }

    /**
     * Load routes from configuration and register them in the Router.
     */
    private function registerRoutes(): void
    {
        $router = $this->router;
        if ($router === null) {
            throw new \RuntimeException('Router must be initialized before registering routes.');
        }

        // Pipe Global Security & Routing Middleware
        $router->pipe(\Core\Middleware\TrailingSlashRedirectMiddleware::class);
        $router->pipe(\Core\Middleware\SessionMiddleware::class);
        $router->pipe(\Core\Middleware\HoneypotMiddleware::class);
        $router->pipe(\Core\Middleware\AdminCsrfMiddleware::class);

        // Core landing pages & actions
        $router->get('/', [RenderHomeAction::class, '__invoke']);
        $router->post('/', [RenderHomeAction::class, '__invoke']);
        $router->post('/download-csv', [DownloadCsvAction::class, '__invoke']);
        $router->post('/generate-pdf', [GeneratePdfAction::class, '__invoke'], ['middleware.ratelimit.pdf']);

        // Dynamic Calculators Registration
        foreach ($this->routesConfig['calculators'] as $calc => $config) {
            $action = is_array($config) && isset($config['action']) ? $config['action'] : $config;
            $router->get($calc, $action);
            $router->post($calc, $action);
        }

        // Embeddable Calculator Widgets
        $router->get('/embed/{slug}', [\Controllers\RenderEmbedAction::class, '__invoke']);

        // Dynamic Pages Registration
        foreach ($this->routesConfig['pages'] as $uri => $config) {
            $action = is_array($config) && isset($config['action']) ? $config['action'] : $config;
            $router->get($uri, $action);
        }

        // Dynamic Sitemap
        $router->get('/sitemap.xml', [SitemapController::class, 'index']);

        // Admin / Insight Routing
        $router->get('/admin_insights', [ShowAdminDashboardAction::class, '__invoke'], [\Core\Middleware\AdminAuthMiddleware::class]);
        $router->post('/admin_insights', [ProcessAdminLoginAction::class, '__invoke'], ['middleware.ratelimit.admin_auth']);
        $router->post('/admin_insights/logout', [ProcessAdminLogoutAction::class, '__invoke']);
        $router->post('/log_insight', [LogInsightApiAction::class, '__invoke'], ['middleware.ratelimit.insight']);

        // Blog / Resources Routing
        $router->get('/resources', [ListResourcesAction::class, '__invoke']);
        $router->get('/resource', [ListResourcesAction::class, '__invoke']);
        $router->get('/resource/{category}', [ShowResourceCategoryAction::class, '__invoke']);
        $router->get('/resource/{category}/{slug}', [ShowResourcePostAction::class, '__invoke']);

        /** @var RedirectLoader $redirectLoader */
        $redirectLoader = $this->container->get(RedirectLoader::class);
        $redirectLoader->loadAndRegister(__DIR__ . '/../../content/redirects.json', $router);
    }
}
