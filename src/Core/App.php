<?php

declare(strict_types=1);

namespace Core;

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
        $this->container = Container::getInstance();
        $this->router = new Router();
        $this->routesConfig = require __DIR__ . '/Config/routes.php';
    }

    /**
     * Run the application bootstrap and request lifecycle.
     */
    public function run(): void
    {
        $this->registerDependencies();
        $this->registerRoutes();

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        $this->router->dispatch($uri, $method);
    }

    /**
     * Register core services in the DI Container.
     */
    private function registerDependencies(): void
    {
        // Bind managers and helpers as Singletons
        $this->container->singleton(ContentManager::class, function () {
            return new ContentManager();
        });

        $this->container->singleton(MetaManager::class, function () {
            return new MetaManager();
        });

        $this->container->singleton(SchemaHelper::class, function () {
            return new SchemaHelper();
        });

        $this->container->singleton(BlogRepository::class, function () {
            return new BlogRepository();
        });

        $this->container->singleton(InsightRepository::class, function () {
            return new InsightRepository();
        });

        $this->container->singleton(AnonymizedInsightLogger::class, function () {
            return new AnonymizedInsightLogger();
        });

        // Bind the Strategy GuideRenderer
        $this->container->singleton(\Services\GuideRenderer::class, function (Container $c) {
            return new \Services\GuideRenderer(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaHelper::class)
            );
        });
    }

    /**
     * Load routes from configuration and register them in the Router.
     */
    private function registerRoutes(): void
    {
        // Core landing pages
        $this->router->get('/', 'CalculatorController@home');
        $this->router->post('/', 'CalculatorController@home');
        $this->router->post('/generate-pdf', 'CalculatorController@generatePdf');

        // Dynamic Calculators Registration
        foreach ($this->routesConfig['calculators'] as $calc => $config) {
            $this->router->get($calc, $config['action']);
            $this->router->post($calc, $config['action']);
            $this->router->redirect($calc . '.php', $calc);
        }

        // Dynamic Pages Registration
        foreach ($this->routesConfig['pages'] as $uri => $action) {
            $this->router->get($uri, $action);
            $this->router->redirect($uri . '.php', $uri);
        }

        // Admin / Insight Routing
        $this->router->get('/admin_insights', 'AdminController@insights');
        $this->router->post('/admin_insights', 'AdminController@insights');
        $this->router->redirect('/admin_insights.php', '/admin_insights');
        $this->router->get('/log_insight', 'AdminController@logInsight');
        $this->router->post('/log_insight', 'AdminController@logInsight');
        $this->router->redirect('/log_insight.php', '/log_insight');

        // Blog / Resources Routing
        $this->router->get('/resources', 'BlogController@index');
        $this->router->get('/resource', 'BlogController@index');
        $this->router->get('/resource/{category}/{slug}', 'BlogController@show');

        // Dynamic Blog Redirects
        foreach ($this->routesConfig['blog_redirects'] as $slug => $target) {
            if (strpos($target, '/') !== false) {
                $this->router->redirect("/resource/{$slug}", "/resource/{$target}");
            } else {
                $this->router->redirect("/resource/{$slug}", "/resource/{$target}/{$slug}");
            }
        }

        // Dynamic Stubs Redirects
        foreach ($this->routesConfig['stubs'] as $old => $new) {
            $this->router->redirect($old, $new);
            $this->router->redirect($old . '.php', $new);
        }
    }
}
