<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple Dependency Injection Container.
 * 
 * Resolves class dependencies using PHP reflection.
 * Supports singleton bindings and manual factory bindings.
 */
class Container
{
    /** @var array<string, callable> Registered factory bindings */
    private array $bindings = [];

    /** @var array<string, object> Resolved singleton instances */
    private array $singletons = [];

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        // Register the container itself
        $this->singletons[self::class] = $this;
    }

    /**
     * Register a factory binding.
     * The callable will be invoked every time the class is requested.
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Register a singleton binding.
     * The callable is invoked once; the result is cached and reused.
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->singletons[$abstract])) {
                $this->singletons[$abstract] = $factory($this);
            }

            return $this->singletons[$abstract];
        };
    }

    /**
     * Resolve a class, injecting its dependencies.
     * 
     * Resolution order:
     * 1. If there's a cached singleton, return it.
     * 2. If there's a registered binding, invoke it.
     * 3. Otherwise, use reflection to auto-resolve constructor dependencies.
     */
    public function make(string $abstract): object
    {
        // Cached singleton?
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        // Registered binding?
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // Auto-resolve via reflection
        return $this->resolve($abstract);
    }

    /**
     * Get a config value using dot notation.
     * Example: $container->config('upload.max_size')
     */
    public function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Auto-resolve a class and its constructor dependencies.
     */
    private function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Class '{$class}' does not exist.");
        }

        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Class '{$class}' is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor — just instantiate
        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            // If the parameter is the config array
            if ($param->getName() === 'config' && ($type === null || $type->getName() === 'array')) {
                $dependencies[] = $this->config;
                continue;
            }

            // If it's a typed class/interface, resolve it recursively
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            // If it has a default value, use it
            if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(
                "Cannot resolve parameter '\${$param->getName()}' for class '{$class}'."
            );
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}