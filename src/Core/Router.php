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
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        if (array_key_exists($uri, $this->redirects) || array_key_exists($normalizedUri, $this->redirects)) {
            $target = $this->redirects[$uri] ?? $this->redirects[$normalizedUri];
            return Response::redirect($target, 301);
        }

        if ($normalizedUri === '/' && isset($this->routes[$method]['/'])) {
            return $this->callAction($this->routes[$method]['/'], [], $request);
        }

        if (isset($this->routes[$method][$normalizedUri])) {
            return $this->callAction($this->routes[$method][$normalizedUri], [], $request);
        }

        if (isset($this->routes[$method]) && is_array($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $action) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\.-]+)', $route);
                if (preg_match('#^' . $pattern . '$#', $normalizedUri, $matches)) {
                    $rawParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $params = array_map('urldecode', $rawParams);
                    return $this->callAction($action, $params, $request);
                }
            }
        }

        throw new \Core\Exceptions\RouteNotFoundException("No route found for URI: {$uri}");
    }

    private function callAction(array $controllerAction, array $params = [], ?Request $request = null): Response
    {
        $controllerName = $controllerAction[0];
        $action = $controllerAction[1] ?? '__invoke';

        if (!str_starts_with($controllerName, '\\')) {
            $controllerName = '\\' . $controllerName;
        }

        if (class_exists($controllerName)) {
            $controller = $this->container->get($controllerName);
            if (method_exists($controller, $action)) {
                $request = $request ?? Request::createFromGlobals();

                $reflection = new \ReflectionMethod($controller, $action);
                $args = [];
                foreach ($reflection->getParameters() as $param) {
                    $name = $param->getName();
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && $type->getName() === Request::class) {
                        $args[] = $request;
                    } elseif (array_key_exists($name, $params)) {
                        $args[] = $params[$name];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        throw new \Core\Exceptions\ContainerException("Cannot resolve parameter '{$name}' for controller action {$controllerName}@{$action}");
                    }
                }

                $response = call_user_func_array([$controller, $action], $args);
                if ($response instanceof Response) {
                    return $response;
                }
                return new Response((string) $response, 200);
            }
        }

        throw new \Core\Exceptions\RouteNotFoundException("Controller or Method not found ({$controllerName}@{$action})");
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
