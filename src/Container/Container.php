<?php

namespace SdFramework\Container;

use ReflectionClass;
use ReflectionParameter;
use SdFramework\Exceptions\ContainerException;

class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $resolved = [];

    public function bind(string $abstract, mixed $concrete = null): self
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
        return $this;
    }

    public function singleton(string $abstract, mixed $concrete = null): self
    {
        $this->bind($abstract, $concrete);
        $this->resolved[$abstract] = true;
        return $this;
    }

    public function instance(string $abstract, object $instance): self
    {
        $this->instances[$abstract] = $instance;
        return $this;
    }

    public function make(string $abstract, array $parameters = []): object
    {
        // If we have an instance, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // If we have a singleton and it's resolved, return the instance
        if (isset($this->resolved[$abstract]) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Build the instance
        $instance = $this->build($concrete, $parameters);

        // If it's a singleton, store it
        if (isset($this->resolved[$abstract])) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    private function build(string $concrete, array $parameters = []): object
    {
        try {
            $reflector = new ReflectionClass($concrete);

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class {$concrete} is not instantiable");
            }

            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                return new $concrete;
            }

            $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

            return $reflector->newInstanceArgs($dependencies);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Error resolving {$concrete}: " . $e->getMessage());
        }
    }

    private function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $resolved = [];

        /** @var ReflectionParameter $dependency */
        foreach ($dependencies as $dependency) {
            $name = $dependency->getName();
            $type = $dependency->getType();

            // If we have a parameter override, use it
            if (isset($parameters[$name])) {
                $resolved[] = $parameters[$name];
                continue;
            }

            // If parameter is type-hinted with a class
            if ($type && !$type->isBuiltin()) {
                try {
                    $resolved[] = $this->make($type->getName());
                    continue;
                } catch (ContainerException $e) {
                    // If we can't resolve it and it's optional, use the default value
                    if ($dependency->isDefaultValueAvailable()) {
                        $resolved[] = $dependency->getDefaultValue();
                        continue;
                    }
                    throw $e;
                }
            }

            // If parameter has a default value
            if ($dependency->isDefaultValueAvailable()) {
                $resolved[] = $dependency->getDefaultValue();
                continue;
            }

            throw new ContainerException("Unable to resolve dependency: {$name}");
        }

        return $resolved;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) 
            || isset($this->instances[$abstract]) 
            || class_exists($abstract);
    }

    public function get(string $abstract): mixed
    {
        if (!$this->has($abstract)) {
            throw new ContainerException("No binding found for {$abstract}");
        }

        return $this->make($abstract);
    }
}
