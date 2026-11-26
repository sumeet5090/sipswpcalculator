<?php

declare(strict_types=1);

namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Psr\Container\ContainerInterface;

class Router
{
    /** @var array<string, array<string, array<int, string>>> */
    private array $routes = [];
    /** @var array<string, array<string, array<int, string|\Core\Middleware\MiddlewareInterface>>> */
    private array $routeMiddlewares = [];
    /** @var array<string, array<string, string>> */
    private array $compiledPatterns = [];
    /** @var array<string, string> */
    private array $redirects = [];
    /** @var array<int, string|\Core\Middleware\MiddlewareInterface> */
    private array $middlewares = [];
    private ContainerInterface $container;
    private ActionDispatcher $actionDispatcher;

    public function __construct(ContainerInterface $container, ActionDispatcher $actionDispatcher)
    {
        $this->container = $container;
        $this->actionDispatcher = $actionDispatcher;
    }

    public function pipe(string|\Core\Middleware\MiddlewareInterface $middleware): void
    {
        if (is_string($middleware) && !is_subclass_of($middleware, \Core\Middleware\MiddlewareInterface::class)) {
            throw new \InvalidArgumentException("Middleware must implement \Core\Middleware\MiddlewareInterface");
        }
        $this->middlewares[] = $middleware;
    }

    /**
     * @param array<int, string> $controllerAction
     * @param array<int, string|\Core\Middleware\MiddlewareInterface> $middlewares
     */
    public function get(string $uri, array $controllerAction, array $middlewares = []): void
    {
        $this->routes['GET'][$uri] = $controllerAction;
        if (!empty($middlewares)) {
            $this->routeMiddlewares['GET'][$uri] = $middlewares;
        }
        if (str_contains($uri, '{')) {
            $this->compiledPatterns['GET'][$uri] = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $uri) . '$#';
        }
    }

    /**
     * @param array<int, string> $controllerAction
     * @param array<int, string|\Core\Middleware\MiddlewareInterface> $middlewares
     */
    public function post(string $uri, array $controllerAction, array $middlewares = []): void
    {
        $this->routes['POST'][$uri] = $controllerAction;
        if (!empty($middlewares)) {
            $this->routeMiddlewares['POST'][$uri] = $middlewares;
        }
        if (str_contains($uri, '{')) {
            $this->compiledPatterns['POST'][$uri] = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $uri) . '$#';
        }
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
        $lookupMethod = ($method === 'HEAD') ? 'GET' : $method;

        $coreHandler = function (Request $req) use ($lookupMethod, $uri): Response {
            if (array_key_exists($uri, $this->redirects)) {
                $target = $this->redirects[$uri];
                $queryString = (string) $req->server('QUERY_STRING', '');
                if ($queryString !== '') {
                    $target .= (str_contains($target, '?') ? '&' : '?') . $queryString;
                }
                return Response::redirect($target, 301);
            }

            if (preg_match('#^/\d+\.\d+\.\d+(/.*)?$#', $uri)) {
                return Response::redirect('/', 301);
            }

            if (isset($this->routes[$lookupMethod][$uri])) {
                $routeMiddlewares = $this->routeMiddlewares[$lookupMethod][$uri] ?? [];
                return $this->dispatchWithRouteMiddlewares(
                    $routeMiddlewares,
                    $this->routes[$lookupMethod][$uri],
                    [],
                    $req
                );
            }

            if (isset($this->compiledPatterns[$lookupMethod])) {
                foreach ($this->compiledPatterns[$lookupMethod] as $route => $pattern) {
                    if (preg_match($pattern, $uri, $matches)) {
                        $rawParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        $params = array_map('urldecode', $rawParams);
                        $action = $this->routes[$lookupMethod][$route];
                        $routeMiddlewares = $this->routeMiddlewares[$lookupMethod][$route] ?? [];
                        return $this->dispatchWithRouteMiddlewares(
                            $routeMiddlewares,
                            $action,
                            $params,
                            $req
                        );
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

    /**
     * @param array<int, string|\Core\Middleware\MiddlewareInterface> $routeMiddlewares
     * @param array<int, string> $controllerAction
     * @param array<string, mixed> $params
     */
    private function dispatchWithRouteMiddlewares(
        array $routeMiddlewares,
        array $controllerAction,
        array $params,
        Request $request
    ): Response {
        if (empty($routeMiddlewares)) {
            return $this->callAction($controllerAction, $params, $request);
        }

        $terminalAction = fn(Request $req): Response => $this->callAction($controllerAction, $params, $req);

        $routePipeline = array_reduce(
            array_reverse($routeMiddlewares),
            function (callable $next, mixed $middleware) {
                return function (Request $req) use ($next, $middleware): Response {
                    $instance = is_string($middleware)
                        ? $this->container->get($middleware)
                        : $middleware;

                    return $instance->process($req, $next);
                };
            },
            $terminalAction
        );

        return $routePipeline($request);
    }

    /**
     * @param array<int, string> $controllerAction
     * @param array<string, mixed> $params
     */
    private function callAction(array $controllerAction, array $params = [], ?Request $request = null): Response
    {
        return $this->actionDispatcher->dispatch($controllerAction, $params, $request);
    }

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return array<string, string>
     */
    public function getRedirects(): array
    {
        return $this->redirects;
    }
}
