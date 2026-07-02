<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Core\Router;

class RouterIntegrityTest extends TestCase
{
    private Router $router;
    private array $routesConfig;

    protected function setUp(): void
    {
        $this->routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $this->router = new Router();

        // Register routes exactly as index.php does
        $this->router->get('/', 'CalculatorController@home');
        $this->router->post('/', 'CalculatorController@home');
        $this->router->post('/generate-pdf', 'CalculatorController@generatePdf');

        // Dynamic Calculators Registration
        foreach ($this->routesConfig['calculators'] as $calc) {
            $slugParts = explode('-', ltrim($calc, '/'));
            $methodName = array_shift($slugParts);
            foreach ($slugParts as $part) {
                $methodName .= ucfirst($part);
            }

            $this->router->get($calc, "CalculatorController@{$methodName}");
            $this->router->post($calc, "CalculatorController@{$methodName}");
            $this->router->redirect($calc . '.php', $calc);
        }

        // Dynamic Pages Registration
        foreach ($this->routesConfig['pages'] as $uri => $method) {
            $this->router->get($uri, "PageController@{$method}");
            $this->router->redirect($uri . '.php', $uri);
        }

        // Admin / Insight Routing
        $this->router->get('/admin_insights', 'AdminController@insights');
        $this->router->post('/admin_insights', 'AdminController@insights');
        $this->router->redirect('/admin_insights.php', '/admin_insights');
        $this->router->get('/log_insight', 'AdminController@logInsight');
        $this->router->post('/log_insight', 'AdminController@logInsight');
        $this->router->redirect('/log_insight.php', '/log_insight');

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

    /**
     * Helper to check if a URI resolves to any registered GET route.
     */
    private function resolvesToGetRoute(string $path): bool
    {
        $routes = $this->router->getRoutes()['GET'] ?? [];

        if (false !== $pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }

        if (isset($routes[$path])) {
            return true;
        }

        $path = rtrim($path, '/');
        if (isset($routes[$path])) {
            return true;
        }

        foreach ($routes as $route => $action) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\.-]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test: Ensure every registered controller class and action method actually exists.
     */
    public function testControllerActionsExist(): void
    {
        $routes = $this->router->getRoutes();
        foreach ($routes as $method => $mappings) {
            foreach ($mappings as $uri => $actionStr) {
                $parts = explode('@', $actionStr);
                $controllerClass = "\\Controllers\\" . $parts[0];
                $action = $parts[1];

                $this->assertTrue(
                    class_exists($controllerClass),
                    "Controller class '$controllerClass' does not exist for route '$uri' ($method)"
                );
                $this->assertTrue(
                    method_exists($controllerClass, $action),
                    "Action method '$action' does not exist in controller '$controllerClass' for route '$uri' ($method)"
                );
            }
        }
    }

    /**
     * Test: Ensure there are no circular redirects (e.g. A -> B -> A).
     */
    public function testNoCircularRedirects(): void
    {
        $redirects = $this->router->getRedirects();

        foreach (array_keys($redirects) as $source) {
            $visited = [];
            $current = $source;
            $chain = [];

            while (isset($redirects[$current])) {
                if (in_array($current, $visited, true)) {
                    $chain[] = $current;
                    $this->fail("Circular redirect detected: " . implode(" -> ", $chain));
                }
                $visited[] = $current;
                $chain[] = $current;
                $current = $redirects[$current];
            }
        }

        $this->expectNotToPerformAssertions();
    }

    /**
     * Test: Ensure every redirect destination target resolves to a valid GET route.
     */
    public function testRedirectTargetsAreValid(): void
    {
        $redirects = $this->router->getRedirects();
        foreach ($redirects as $source => $target) {
            $this->assertTrue(
                $this->resolvesToGetRoute($target),
                "Redirect target '$target' for source '$source' does not resolve to a registered GET route."
            );
        }
    }

    /**
     * Test: Ensure sitemap.xml exists, is valid XML, and maps 1:1 with active public routes.
     */
    public function testSitemapIntegrity(): void
    {
        $sitemapPath = __DIR__ . '/../../sitemap.xml';
        $this->assertFileExists($sitemapPath, 'sitemap.xml is missing from root');

        $xml = simplexml_load_file($sitemapPath);
        $this->assertNotFalse($xml, 'sitemap.xml is not valid XML');

        $sitemapPaths = [];
        foreach ($xml->url as $url) {
            $loc = (string)$url->loc;
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $sitemapPaths[] = $path;

            $this->assertTrue(
                $this->resolvesToGetRoute($path),
                "Sitemap URL '$loc' (path '$path') does not resolve to a valid GET route."
            );
        }

        $routes = $this->router->getRoutes()['GET'] ?? [];
        $ignoredRoutes = [
            '/admin_insights',
            '/log_insight',
            '/resource', // Generic fallback redirect/canonical checks
            '/resource/{category}/{slug}' // General parameter matching
        ];

        foreach (array_keys($routes) as $route) {
            if (in_array($route, $ignoredRoutes, true) || strpos($route, '{') !== false) {
                continue;
            }

            // Exclude dynamic stubs and secondary endpoints (like .php redirects)
            if (strpos($route, '.php') !== false) {
                continue;
            }

            $this->assertContains(
                $route,
                $sitemapPaths,
                "Public route '$route' is defined in routes.php but is missing from sitemap.xml."
            );
        }
    }

    /**
     * Test: Ensure robots.txt points correctly to sitemap.xml.
     */
    public function testRobotsTxtSitemapIsCorrect(): void
    {
        $robotsPath = __DIR__ . '/../../robots.txt';
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);
        $this->assertMatchesRegularExpression(
            '/Sitemap:\s*https:\/\/sipswpcalculator\.com\/sitemap\.xml/i',
            $content,
            'robots.txt does not link to the correct sitemap location'
        );
    }

    /**
     * Test: Validate BlogRepository mapping matches actual blog post markdown files.
     */
    public function testBlogRepositoryMatchesMarkdownFiles(): void
    {
        $allPosts = \Core\BlogRepository::getAllPosts();
        $repoSlugs = array_map(function ($post) {
            return basename($post['href']);
        }, $allPosts);

        $contentDir = __DIR__ . '/../../content/blog';
        $categories = ['growth', 'retirement', 'comparison'];

        foreach ($categories as $cat) {
            $dir = $contentDir . '/' . $cat;
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.md');
            if (!$files) {
                continue;
            }

            foreach ($files as $file) {
                $slug = basename($file, '.md');
                $this->assertContains(
                    $slug,
                    $repoSlugs,
                    "Blog post markdown file '$slug.md' in '$cat' is missing from BlogRepositoryConfigs or has parsing issues."
                );

                $postUrl = "/resource/{$cat}/{$slug}";
                $this->assertTrue(
                    $this->resolvesToGetRoute($postUrl),
                    "Blog post URL '$postUrl' is not resolvable by the Router."
                );
            }
        }
    }
}
