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
    public const DEFAULT_APP_URL = 'https://sipswpcalculator.com';

    private Container $container;
    private Router $router;
    private array $routesConfig = [];

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    /**
     * Bootstrap dependencies and routes without dispatching.
     */
    public function boot(): Container
    {
        $this->routesConfig = require __DIR__ . '/Config/routes.php';
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
        $providers = [
            new \Core\Providers\CoreServiceProvider(),
            new \Core\Providers\RepositoryServiceProvider(),
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
        // Pipe Global Security Middleware
        $this->router->pipe(\Core\Middleware\CsrfHoneypotMiddleware::class);

        // Core landing pages & actions
        $this->router->get('/', [RenderHomeAction::class, '__invoke']);
        $this->router->post('/', [RenderHomeAction::class, '__invoke']);
        $this->router->post('/download-csv', [\Controllers\DownloadCsvAction::class, '__invoke']);
        $this->router->post('/generate-pdf', [GeneratePdfAction::class, '__invoke']);

        // Dynamic Calculators Registration
        foreach ($this->routesConfig['calculators'] as $calc => $action) {
            $this->router->get($calc, $action);
            $this->router->post($calc, $action);
        }

        // Dynamic Pages Registration
        foreach ($this->routesConfig['pages'] as $uri => $action) {
            $this->router->get($uri, $action);
        }

        // Dynamic Sitemap
        $this->router->get('/sitemap.xml', [SitemapController::class, 'index']);

        // Admin / Insight Routing
        $this->router->get('/admin_insights', [AdminController::class, 'insights']);
        $this->router->post('/admin_insights', [AdminController::class, 'login']);
        $this->router->post('/admin_insights/logout', [AdminController::class, 'logout']);
        $this->router->get('/admin_insights/migrate', [AdminController::class, 'runMigrations']);
        $this->router->get('/log_insight', [AdminController::class, 'logInsight']);
        $this->router->post('/log_insight', [AdminController::class, 'logInsight']);

        // Blog / Resources Routing
        $this->router->get('/resources', [BlogController::class, 'index']);
        $this->router->get('/resource', [BlogController::class, 'index']);
        $this->router->get('/resource/{category}/{slug}', [BlogController::class, 'show']);

        $this->loadRedirects(__DIR__ . '/../../content/redirects.json');
    }

    /**
     * Load dynamic redirects from JSON configuration into Router.
     */
    private function loadRedirects(string $redirectsPath): void
    {
        if (!file_exists($redirectsPath)) {
            return;
        }

        $rawJson = file_get_contents($redirectsPath);
        $redirectsData = json_decode($rawJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($redirectsData)) {
            error_log("Failed to parse content/redirects.json: " . json_last_error_msg());
            return;
        }

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
