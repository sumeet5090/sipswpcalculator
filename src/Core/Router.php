<?php

declare(strict_types=1);

namespace Core;

use Core\Http\Request;
use Core\Http\Response;

class Router
{
    private array $routes = [];
    private array $redirects = [];

    public function get(string $uri, string|array $controllerAction): void
    {
        $this->routes['GET'][$uri] = $controllerAction;
    }

    public function post(string $uri, string|array $controllerAction): void
    {
        $this->routes['POST'][$uri] = $controllerAction;
    }

    public function redirect(string $uri, string $target): void
    {
        $this->redirects[$uri] = $target;
    }

    public function dispatch(?Request $request = null): void
    {
        $request = $request ?? Request::createFromGlobals();
        $uri = $request->getUri();
        $method = $request->getMethod();

        error_log("Dispatching: $method $uri");

        if (array_key_exists($uri, $this->redirects)) {
            header('Location: ' . $this->redirects[$uri], true, 301);
            exit;
        }

        if ($uri === '/' && isset($this->routes[$method]['/'])) {
            $this->callAction($this->routes[$method]['/'], [], $request);
            return;
        }

        $uri = rtrim($uri, '/');

        if (isset($this->routes[$method][$uri])) {
            $this->callAction($this->routes[$method][$uri], [], $request);
            return;
        }

        if (isset($this->routes[$method]) && is_array($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $action) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\.-]+)', $route);
                if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $this->callAction($action, $params, $request);
                    return;
                }
            }
        }

        \Controllers\ErrorController::handle404();
    }

    private function callAction(string|array $controllerAction, array $params = [], ?Request $request = null): void
    {
        if (is_array($controllerAction)) {
            $controllerName = $controllerAction[0];
            $action = $controllerAction[1] ?? '__invoke';
        } else {
            $parts = explode('@', $controllerAction);
            $controllerName = $parts[0];
            $action = $parts[1] ?? '__invoke';
        }

        if (!str_starts_with($controllerName, '\\')) {
            $controllerName = '\\' . ltrim($controllerName, '\\');
        }
        if (!str_starts_with($controllerName, '\\Controllers\\')) {
            $controllerName = '\\Controllers\\' . ltrim($controllerName, '\\');
        }

        if (class_exists($controllerName)) {
            try {
                $controller = Container::getInstance()->get($controllerName);
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
                            $args[] = null;
                        }
                    }

                    $response = call_user_func_array([$controller, $action], $args);
                    if ($response instanceof Response) {
                        $response->send();
                    }
                    return;
                }
            } catch (\Throwable $e) {
                \Controllers\ErrorController::handle500($e);
            }
        }

        \Controllers\ErrorController::handle500(new \Exception("Controller or Method not found ($controllerName@$action)"));
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
