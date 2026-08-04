<?php

declare(strict_types=1);

namespace Core;

/**
 * Container
 * A lightweight Dependency Injection Container with auto-wiring reflection resolution.
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];
    private static ?Container $instance = null;

    /**
     * Get the globally registered container instance (Singleton behavior for the container itself).
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set the global container instance.
     */
    public static function setInstance(?Container $container): void
    {
        self::$instance = $container;
    }

    /**
     * Bind a key (interface/class name) to a resolver callback or value.
     */
    public function bind(string $key, callable|object|string $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    /**
     * Bind a key as a singleton instance.
     */
    public function singleton(string $key, callable|object|string $resolver): void
    {
        $this->bindings[$key] = function (self $container) use ($resolver, $key) {
            if (!isset($container->instances[$key])) {
                $container->instances[$key] = is_callable($resolver) ? $resolver($container) : $resolver;
            }
            return $container->instances[$key];
        };
    }

    /**
     * Retrieve and resolve a class instance.
     */
    public function get(string $key): mixed
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (isset($this->bindings[$key])) {
            $resolver = $this->bindings[$key];
            if (is_callable($resolver)) {
                $resolved = $resolver($this);
            } else {
                $resolved = $resolver;
            }
            return $resolved;
        }

        return $this->resolve($key);
    }

    /**
     * Automatically resolve dependencies via Reflection.
     */
    public function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new \Exception("Class {$class} does not exist.");
        }

        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$class} is not instantiable.");
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
                    throw new \Exception("Cannot resolve parameter '{$parameter->getName()}' in class {$class} (no type hint).");
                }
            } else {
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $dependencies[] = $this->get($type->getName());
                } else {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new \Exception("Cannot resolve primitive parameter '{$parameter->getName()}' in class {$class}.");
                    }
                }
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
