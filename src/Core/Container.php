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
    private array $resolving = [];

    public function __construct()
    {
        $this->instances[ContainerInterface::class] = $this;
        $this->instances[self::class] = $this;
    }

    /**
     * Bind a key (interface/class name) to a transient resolver callback or value.
     * Transient bindings produce a new instance on each get() call.
     */
    public function bind(string $key, callable|object|string $resolver): void
    {
        $key = ltrim($key, '\\');
        if (is_string($resolver) && !class_exists($resolver) && !interface_exists($resolver)) {
            throw new ContainerException("Target class or interface '{$resolver}' does not exist for binding '{$key}'.");
        }
        $this->bindings[$key] = $resolver;
    }

    /**
     * Bind a key as a singleton instance.
     */
    public function singleton(string $key, callable|object|string $resolver): void
    {
        $key = ltrim($key, '\\');
        if (is_string($resolver) && !class_exists($resolver) && !interface_exists($resolver)) {
            throw new ContainerException("Target class or interface '{$resolver}' does not exist for singleton binding '{$key}'.");
        }
        $this->bindings[$key] = function (Container $container) use ($key, $resolver) {
            if (is_callable($resolver)) {
                $resolved = $resolver($container);
            } elseif (is_string($resolver) && class_exists($resolver)) {
                $resolved = $container->resolve($resolver);
            } elseif (is_object($resolver)) {
                $resolved = $resolver;
            } else {
                throw new ContainerException("Invalid singleton resolver provided for key: {$key}");
            }
            $container->instances[$key] = $resolved;
            return $resolved;
        };
    }

    /**
     * Bind an already existing instance as a singleton.
     */
    public function instance(string $key, object $instance): void
    {
        $key = ltrim($key, '\\');
        $this->instances[$key] = $instance;
    }

    /**
     * Remove a binding and cached singleton instance from the container.
     */
    public function forget(string $id): void
    {
        $id = ltrim($id, '\\');
        unset($this->instances[$id], $this->bindings[$id]);
    }

    /**
     * Flush all bindings and instances, re-registering the container itself.
     */
    public function flush(): void
    {
        $this->instances = [];
        $this->bindings = [];
        $this->resolving = [];
        $this->instances[ContainerInterface::class] = $this;
        $this->instances[self::class] = $this;
    }

    /**
     * Check if a binding or singleton instance exists.
     */
    public function has(string $id): bool
    {
        $id = ltrim($id, '\\');
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    /**
     * Retrieve an instance from the container by key.
     *
     * @throws NotFoundException if the key cannot be found/resolved
     * @throws ContainerException on generic dependency resolution failures
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
                return $resolved;
            }
            if (is_string($resolver) && class_exists($resolver)) {
                return $this->resolve($resolver);
            }
            return $resolver;
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
        if (interface_exists($class)) {
            throw new ContainerException("Cannot instantiate interface {$class}. Please bind an implementation in a ServiceProvider.");
        }
        if (!class_exists($class)) {
            throw new NotFoundException("Class {$class} does not exist.");
        }

        if (isset($this->resolving[$class])) {
            $chain = implode(' -> ', array_keys($this->resolving)) . ' -> ' . $class;
            throw new ContainerException("Circular dependency detected while resolving {$class} [{$chain}].");
        }

        $this->resolving[$class] = true;

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
                if ($parameter->isVariadic()) {
                    throw new ContainerException("Variadic parameter '{$parameter->getName()}' in class {$class} cannot be autowired by reflection.");
                }

                $type = $parameter->getType();
                if ($type === null) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new ContainerException("Cannot resolve parameter '{$parameter->getName()}' in class {$class} (no type hint).");
                    }
                } elseif ($type instanceof \ReflectionUnionType) {
                    $resolved = false;
                    foreach ($type->getTypes() as $subType) {
                        if ($subType instanceof \ReflectionNamedType && !$subType->isBuiltin()) {
                            $subName = $subType->getName();
                            if ($this->has($subName)) {
                                try {
                                    $dependencies[] = $this->get($subName);
                                    $resolved = true;
                                    break;
                                } catch (\Throwable) {
                                    // Try next union candidate
                                }
                            }
                        }
                    }
                    if (!$resolved) {
                        if ($parameter->isDefaultValueAvailable()) {
                            $dependencies[] = $parameter->getDefaultValue();
                        } else {
                            throw new ContainerException("Cannot resolve union type parameter '{$parameter->getName()}' in class {$class}.");
                        }
                    }
                } elseif ($type instanceof \ReflectionIntersectionType) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new ContainerException("Intersection types are not supported for autowiring parameter '{$parameter->getName()}' in class {$class}.");
                    }
                } else {
                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                        $typeName = $type->getName();
                        if (isset($this->instances[$typeName]) || isset($this->bindings[$typeName])) {
                            $dependencies[] = $this->get($typeName);
                        } else {
                            try {
                                $dependencies[] = $this->get($typeName);
                            } catch (\Throwable $e) {
                                if ($type->allowsNull()) {
                                    $dependencies[] = null;
                                } elseif ($parameter->isDefaultValueAvailable()) {
                                    $dependencies[] = $parameter->getDefaultValue();
                                } else {
                                    throw $e;
                                }
                            }
                        }
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
        } finally {
            unset($this->resolving[$class]);
        }
    }
}
