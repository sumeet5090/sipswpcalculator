<?php

declare(strict_types=1);

namespace Tests\Integration;

use Controllers\AdminController;
use Controllers\BlogController;
use Controllers\GeneratePdfAction;
use Controllers\PageController;
use Controllers\RenderHomeAction;
use Core\Router;
use PHPUnit\Framework\TestCase;

class RouterIntegrityTest extends TestCase
{
    private Router $router;
    private \Core\Container $container;

    protected function setUp(): void
    {
        $app = new \Core\App();
        $app->boot();
        $this->container = $app->getContainer();
        $this->router = $app->getRouter();
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
            foreach ($mappings as $uri => $action) {
                if (is_array($action)) {
                    $controllerClass = $action[0];
                    $actionMethod = $action[1] ?? '__invoke';
                } else {
                    $parts = explode('@', $action);
                    $controllerClass = $parts[0];
                    $actionMethod = $parts[1] ?? '__invoke';
                }

                if (!str_starts_with($controllerClass, '\\')) {
                    $controllerClass = '\\' . ltrim($controllerClass, '\\');
                }
                if (!str_starts_with($controllerClass, '\\Controllers\\')) {
                    $controllerClass = '\\Controllers\\' . ltrim($controllerClass, '\\');
                }

                $this->assertTrue(
                    class_exists($controllerClass),
                    "Controller class '$controllerClass' does not exist for route '$uri' ($method)"
                );
                $this->assertTrue(
                    method_exists($controllerClass, $actionMethod),
                    "Action method '$actionMethod' does not exist in controller '$controllerClass' for route '$uri' ($method)"
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
        $this->assertNotEmpty($redirects, "Redirects list should not be empty.");
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
        ob_start();
        $controller = $this->container->get(\Controllers\SitemapController::class);
        $response = $controller->index();
        $response->send();
        $xmlContent = ob_get_clean();

        $xml = simplexml_load_string($xmlContent);
        $this->assertNotFalse($xml, 'Dynamic sitemap is not valid XML');

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

        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $routes = $this->router->getRoutes()['GET'] ?? [];
        $ignoredRoutes = [
            '/admin_insights',
            '/admin_insights/migrate',
            '/log_insight',
            '/sitemap.xml',
            '/resource', // Generic fallback redirect/canonical checks
            '/resource/{category}/{slug}', // General parameter matching
        ];

        foreach (array_keys($routes) as $route) {
            if (in_array($route, $ignoredRoutes, true) || strpos($route, '{') !== false) {
                continue;
            }

            // Exclude routes explicitly configured to be excluded from sitemap (e.g. noindex pages)
            if (isset($routesConfig['pages'][$route]['sitemap_exclude']) && $routesConfig['pages'][$route]['sitemap_exclude'] === true) {
                continue;
            }
            if (isset($routesConfig['calculators'][$route]['sitemap_exclude']) && $routesConfig['calculators'][$route]['sitemap_exclude'] === true) {
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
        /** @var \Core\BlogRepository $blogRepository */
        $blogRepository = $this->container->get(\Core\BlogRepository::class);
        $allPosts = $blogRepository->getAllPosts();
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
