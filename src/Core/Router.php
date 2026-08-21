<?php

declare(strict_types=1);

namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Psr\Container\ContainerInterface;

class Router
{
    private array $routes = [];
    private array $compiledPatterns = [];
    private array $redirects = [];
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

    public function get(string $uri, array $controllerAction): void
    {
        $this->routes['GET'][$uri] = $controllerAction;
        if (str_contains($uri, '{')) {
            $this->compiledPatterns['GET'][$uri] = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $uri) . '$#';
        }
    }

    public function post(string $uri, array $controllerAction): void
    {
        $this->routes['POST'][$uri] = $controllerAction;
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

            if (isset($this->routes[$lookupMethod][$uri])) {
                return $this->callAction($this->routes[$lookupMethod][$uri], [], $req);
            }

            if (isset($this->compiledPatterns[$lookupMethod]) && is_array($this->compiledPatterns[$lookupMethod])) {
                foreach ($this->compiledPatterns[$lookupMethod] as $route => $pattern) {
                    if (preg_match($pattern, $uri, $matches)) {
                        $rawParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        $params = array_map('urldecode', $rawParams);
                        $action = $this->routes[$lookupMethod][$route];
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
