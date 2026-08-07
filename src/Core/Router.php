<?php

declare(strict_types=1);

namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Psr\Container\ContainerInterface;

class Router
{
    private array $routes = [];
    private array $redirects = [];
    private array $middlewares = [];
    private ContainerInterface $container;
    private ActionDispatcher $actionDispatcher;

    public function __construct(ContainerInterface $container, ?ActionDispatcher $actionDispatcher = null)
    {
        $this->container = $container;
        $this->actionDispatcher = $actionDispatcher ?? new ActionDispatcher($container);
    }

    public function pipe(string|\Core\Middleware\MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function get(string $uri, array $controllerAction): void
    {
        $this->routes['GET'][$uri] = $controllerAction;
    }

    public function post(string $uri, array $controllerAction): void
    {
        $this->routes['POST'][$uri] = $controllerAction;
    }

    public function redirect(string $uri, string $target): void
    {
        $this->redirects[$uri] = $target;
    }

    public function dispatch(?Request $request = null): Response
    {
        $request = $request ?? Request::createFromGlobals();
        $uri = $request->getUri();
        $method = $request->getMethod();

        $normalizedUri = $uri === '/' ? '/' : rtrim($uri, '/');

        $coreHandler = function (Request $req) use ($normalizedUri, $method, $uri): Response {
            if (array_key_exists($uri, $this->redirects) || array_key_exists($normalizedUri, $this->redirects)) {
                $target = $this->redirects[$uri] ?? $this->redirects[$normalizedUri];
                return Response::redirect($target, 301);
            }

            if ($normalizedUri === '/' && isset($this->routes[$method]['/'])) {
                return $this->callAction($this->routes[$method]['/'], [], $req);
            }

            if (isset($this->routes[$method][$normalizedUri])) {
                return $this->callAction($this->routes[$method][$normalizedUri], [], $req);
            }

            if (isset($this->routes[$method]) && is_array($this->routes[$method])) {
                foreach ($this->routes[$method] as $route => $action) {
                    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\.-]+)', $route);
                    if (preg_match('#^' . $pattern . '$#', $normalizedUri, $matches)) {
                        $rawParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        $params = array_map('urldecode', $rawParams);
                        return $this->callAction($action, $params, $req);
                    }
                }
            }

            throw new \Core\Exceptions\RouteNotFoundException("No route found for URI: {$uri}");
        };

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function (callable $next, mixed $middleware) {
                return function (Request $req) use ($next, $middleware): Response {
                    $instance = is_string($middleware)
                        ? $this->container->get($middleware)
                        : $middleware;

                    return $instance->process($req, $next);
                };
            },
            $coreHandler
        );

        return $pipeline($request);
    }

    private function callAction(array $controllerAction, array $params = [], ?Request $request = null): Response
    {
        return $this->actionDispatcher->dispatch($controllerAction, $params, $request);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRedirects(): array
    {
        return $this->redirects;
    }
}
