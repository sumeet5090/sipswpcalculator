<?php
namespace Core;

class Router {
    private $routes = [];
    private $redirects = [];

    public function get($uri, $controllerAction) {
        $this->routes['GET'][$uri] = $controllerAction;
    }

    public function post($uri, $controllerAction) {
        $this->routes['POST'][$uri] = $controllerAction;
    }

    public function redirect($uri, $target) {
        $this->redirects[$uri] = $target;
    }

    public function dispatch($uri, $method) {
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

        foreach ($this->routes[$method] as $route => $action) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\.-]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callAction(explode('@', $action), $params);
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    private function callAction($controllerAction, $params = []) {
        $controllerName = "\\Controllers\\" . $controllerAction[0];
        $action = $controllerAction[1];

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $action)) {
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }
        
        http_response_code(500);
        echo "500 Internal Server Error: Controller or Method not found ($controllerName@$action)";
        exit;
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function getRedirects(): array {
        return $this->redirects;
    }
}

