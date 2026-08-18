<?php

declare(strict_types=1);

namespace Core;

use Controllers\AdminAuthAction;
use Controllers\BlogController;
use Controllers\DownloadCsvAction;
use Controllers\GeneratePdfAction;
use Controllers\LogInsightApiAction;
use Controllers\PageController;
use Controllers\RenderGuideAction;
use Controllers\RenderHomeAction;
use Controllers\ShowAdminDashboardAction;
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

        try {
            $response = $this->router->dispatch($request);
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
        // Pipe Global Security & Routing Middleware
        $this->router->pipe(\Core\Middleware\TrailingSlashRedirectMiddleware::class);
        $this->router->pipe(\Core\Middleware\SessionMiddleware::class);
        $this->router->pipe(\Core\Middleware\CsrfHoneypotMiddleware::class);

        // Core landing pages & actions
        $this->router->get('/', [RenderHomeAction::class, '__invoke']);
        $this->router->post('/', [RenderHomeAction::class, '__invoke']);
        $this->router->post('/download-csv', [DownloadCsvAction::class, '__invoke']);
        $this->router->post('/generate-pdf', [GeneratePdfAction::class, '__invoke']);

        // Dynamic Calculators Registration
        foreach ($this->routesConfig['calculators'] as $calc => $config) {
            $action = is_array($config) && isset($config['action']) ? $config['action'] : $config;
            $this->router->get($calc, $action);
            $this->router->post($calc, $action);
        }

        // Dynamic Pages Registration
        foreach ($this->routesConfig['pages'] as $uri => $config) {
            $action = is_array($config) && isset($config['action']) ? $config['action'] : $config;
            $this->router->get($uri, $action);
        }

        // Dynamic Sitemap
        $this->router->get('/sitemap.xml', [SitemapController::class, 'index']);

        // Admin / Insight Routing
        $this->router->get('/admin_insights', [ShowAdminDashboardAction::class, '__invoke']);
        $this->router->post('/admin_insights', [AdminAuthAction::class, 'login']);
        $this->router->post('/admin_insights/logout', [AdminAuthAction::class, 'logout']);
        $this->router->post('/log_insight', [LogInsightApiAction::class, '__invoke']);

        // Blog / Resources Routing
        $this->router->get('/resources', [BlogController::class, 'index']);
        $this->router->get('/resource', [BlogController::class, 'index']);
        $this->router->get('/resource/{category}/{slug}', [BlogController::class, 'show']);

        /** @var RedirectLoader $redirectLoader */
        $redirectLoader = $this->container->get(RedirectLoader::class);
        $redirectLoader->loadAndRegister(__DIR__ . '/../../content/redirects.json', $this->router);
    }
}
