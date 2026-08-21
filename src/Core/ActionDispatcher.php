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
     * @param array|string $controllerAction
     * @param array $params
     * @param Request|null $request
     * @return Response
     */
    public function dispatch(array|string $controllerAction, array $params = [], ?Request $request = null): Response
    {
        if (is_string($controllerAction)) {
            $controllerName = $controllerAction;
            $action = '__invoke';
        } else {
            $controllerName = $controllerAction[0] ?? '';
            $action = $controllerAction[1] ?? '__invoke';
        }

        if (class_exists($controllerName)) {
            $controller = $this->container->get($controllerName);
            if (method_exists($controller, $action)) {
                $request = $request ?? Request::createFromGlobals();

                $reflection = new \ReflectionMethod($controller, $action);
                $args = [];
                $paramValues = array_values($params);
                $paramIndex = 0;

                foreach ($reflection->getParameters() as $param) {
                    $name = $param->getName();
                    $type = $param->getType();

                    $isRequestType = false;
                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && is_a($type->getName(), Request::class, true)) {
                        $isRequestType = true;
                    } elseif ($type instanceof \ReflectionUnionType) {
                        foreach ($type->getTypes() as $subType) {
                            if ($subType instanceof \ReflectionNamedType && !$subType->isBuiltin() && is_a($subType->getName(), Request::class, true)) {
                                $isRequestType = true;
                                break;
                            }
                        }
                    }

                    if ($isRequestType) {
                        $args[] = $request;
                    } elseif (array_key_exists($name, $params)) {
                        $args[] = $this->coerceParameterValue($params[$name], $type);
                    } elseif (isset($paramValues[$paramIndex])) {
                        $args[] = $this->coerceParameterValue($paramValues[$paramIndex], $type);
                        $paramIndex++;
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
                if ($response === null || $response === false) {
                    throw new \Core\Exceptions\ContainerException("Controller action {$controllerName}@{$action} did not return a valid Core\\Http\\Response object.");
                }
                return new Response((string) $response, 200);
            }
        }

        throw new \Core\Exceptions\RouteNotFoundException("Controller or Method not found ({$controllerName}@{$action})");
    }

    /**
     * Coerce string route parameters to expected scalar method typehint.
     */
    private function coerceParameterValue(mixed $val, ?\ReflectionType $type): mixed
    {
        if ($val === null || $type === null) {
            return $val;
        }

        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            return match ($type->getName()) {
                'int' => is_numeric($val) ? (int) $val : $val,
                'float' => is_numeric($val) ? (float) $val : $val,
                'bool' => is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN) : (bool) $val,
                'string' => (string) $val,
                default => $val,
            };
        }

        return $val;
    }
}
