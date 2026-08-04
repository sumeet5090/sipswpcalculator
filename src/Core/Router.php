<?php

declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];
    private array $redirects = [];

    public function get(string $uri, string $controllerAction): void
    {
        $this->routes['GET'][$uri] = $controllerAction;
    }

    public function post(string $uri, string $controllerAction): void
    {
        $this->routes['POST'][$uri] = $controllerAction;
    }

    public function redirect(string $uri, string $target): void
    {
        $this->redirects[$uri] = $target;
    }

    public function dispatch(string $uri, string $method): void
    {
        error_log("Dispatching: $method $uri");
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        if (array_key_exists($uri, $this->redirects)) {
            header('Location: ' . $this->redirects[$uri], true, 301);
            exit;
        }

        if ($uri === '/' && isset($this->routes[$method]['/'])) {
            $this->callAction(explode('@', $this->routes[$method]['/']));
            return;
        }

        $uri = rtrim($uri, '/');

        if (isset($this->routes[$method][$uri])) {
            $this->callAction(explode('@', $this->routes[$method][$uri]));
            return;
        }

        if (isset($this->routes[$method]) && is_array($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $action) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\.-]+)', $route);
                if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $this->callAction(explode('@', $action), $params);
                    return;
                }
            }
        }

        \Controllers\ErrorController::handle404();
    }

    private function callAction(array $controllerAction, array $params = []): void
    {
        if (count($controllerAction) === 2) {
            $controllerName = "\\Controllers\\" . $controllerAction[0];
            $action = $controllerAction[1];
        } else {
            $controllerName = "\\Controllers\\" . $controllerAction[0];
            $action = '__invoke';
        }

        if (class_exists($controllerName)) {
            try {
                $controller = Container::getInstance()->get($controllerName);
                if (method_exists($controller, $action)) {
                    $request = \Core\Http\Request::createFromGlobals();

                    $reflection = new \ReflectionMethod($controller, $action);
                    $args = [];
                    foreach ($reflection->getParameters() as $param) {
                        $name = $param->getName();
                        $type = $param->getType();
                        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && $type->getName() === \Core\Http\Request::class) {
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
                    if ($response instanceof \Core\Http\Response) {
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
