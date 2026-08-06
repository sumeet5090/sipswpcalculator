<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\ContainerException;
use Core\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Container
 * A lightweight Dependency Injection Container with auto-wiring reflection resolution.
 * Implements PSR-11 ContainerInterface.
 */
class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * Bind a key (interface/class name) to a resolver callback or value.
     */
    public function bind(string $key, callable|object|string $resolver): void
    {
        $key = ltrim($key, '\\');
        $this->bindings[$key] = $resolver;
    }

    /**
     * Bind a key as a singleton instance.
     */
    public function singleton(string $key, callable|object|string $resolver): void
    {
        $key = ltrim($key, '\\');
        $this->bindings[$key] = function (self $container) use ($resolver, $key) {
            if (!isset($container->instances[$key])) {
                $container->instances[$key] = is_callable($resolver) ? $resolver($container) : $resolver;
            }
            return $container->instances[$key];
        };
    }

    /**
     * Check if the container has a binding or instance for the given identifier.
     */
    public function has(string $id): bool
    {
        $id = ltrim($id, '\\');
        if (isset($this->instances[$id]) || isset($this->bindings[$id])) {
            return true;
        }

        if (!class_exists($id)) {
            return false;
        }

        try {
            $reflector = new \ReflectionClass($id);
            return $reflector->isInstantiable();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Retrieve and resolve a class instance by its identifier.
     *
     * @throws NotFoundException if entry is not found
     * @throws ContainerException if error while retrieving the entry
     */
    public function get(string $id): mixed
    {
        $id = ltrim($id, '\\');
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $resolver = $this->bindings[$id];
            if (is_callable($resolver)) {
                $resolved = $resolver($this);
            } else {
                $resolved = $resolver;
            }
            return $resolved;
        }

        return $this->resolve($id);
    }

    /**
     * Automatically resolve dependencies via Reflection.
     *
     * @throws NotFoundException if class does not exist
     * @throws ContainerException if class cannot be instantiated or dependencies cannot be resolved
     */
    public function resolve(string $class): object
    {
        $class = ltrim($class, '\\');
        if (!class_exists($class)) {
            throw new NotFoundException("Class {$class} does not exist.");
        }

        try {
            $reflector = new \ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class {$class} is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if ($constructor === null) {
                return new $class();
            }

            $parameters = $constructor->getParameters();
            $dependencies = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if ($type === null) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new ContainerException("Cannot resolve parameter '{$parameter->getName()}' in class {$class} (no type hint).");
                    }
                } else {
                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                        $dependencies[] = $this->get($type->getName());
                    } else {
                        if ($parameter->isDefaultValueAvailable()) {
                            $dependencies[] = $parameter->getDefaultValue();
                        } else {
                            throw new ContainerException("Cannot resolve primitive parameter '{$parameter->getName()}' in class {$class}.");
                        }
                    }
                }
            }

            return $reflector->newInstanceArgs($dependencies);
        } catch (NotFoundException | ContainerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ContainerException("Failed to resolve class {$class}: " . $e->getMessage(), 0, $e);
        }
    }
}
