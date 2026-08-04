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
        $this->container->singleton(\Services\ConfigService::class, function () {
            return new \Services\ConfigService();
        });

        $this->container->singleton(\Services\CsvExportService::class, function () {
            return new \Services\CsvExportService();
        });

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

        $this->container->singleton(\Core\Factories\SchemaFactory::class, function (Container $c) {
            return new \Core\Factories\SchemaFactory($c->get(SchemaHelper::class));
        });

        // Bind the Strategy GuideRenderer
        $this->container->singleton(\Services\GuideRenderer::class, function (Container $c) {
            return new \Services\GuideRenderer(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(\Core\Factories\SchemaFactory::class)
            );
        });
    }

    /**
     * Load routes from configuration and register them in the Router.
     */
    private function registerRoutes(): void
    {
        // Core landing pages
        $this->router->get('/', 'RenderHomeAction');
        $this->router->post('/', 'RenderHomeAction');
        $this->router->post('/generate-pdf', 'GeneratePdfAction');

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

        // Dynamic Sitemap
        $this->router->get('/sitemap.xml', 'SitemapController@index');

        // Admin / Insight Routing
        $this->router->get('/admin_insights', 'AdminController@insights');
        $this->router->post('/admin_insights', 'AdminController@insights');
        $this->router->redirect('/admin_insights.php', '/admin_insights');
        $this->router->get('/admin_insights/migrate', 'AdminController@runMigrations');
        $this->router->get('/log_insight', 'AdminController@logInsight');
        $this->router->post('/log_insight', 'AdminController@logInsight');
        $this->router->redirect('/log_insight.php', '/log_insight');

        // Blog / Resources Routing
        $this->router->get('/resources', 'BlogController@index');
        $this->router->get('/resource', 'BlogController@index');
        $this->router->get('/resource/{category}/{slug}', 'BlogController@show');

        // Load Dynamic Redirects from JSON
        $redirectsPath = __DIR__ . '/../../content/redirects.json';
        if (file_exists($redirectsPath)) {
            $redirectsData = json_decode(file_get_contents($redirectsPath), true);

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
