<?php

declare(strict_types=1);

namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Psr\Container\ContainerInterface;

class ActionDispatcher
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch request to controller action with parameter resolution.
     *
     * @param array $controllerAction
     * @param array $params
     * @param Request|null $request
     * @return Response
     */
    public function dispatch(array $controllerAction, array $params = [], ?Request $request = null): Response
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
}
