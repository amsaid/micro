<?php

namespace SdFramework\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use SdFramework\Exception\ContainerException;
use SdFramework\Exception\NotFoundException;

class Container implements ContainerInterface
{
    private array $instances = [];
    private array $definitions = [];
    private array $bindings = [];

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("No entry was found for {$id} identifier");
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];
            if (is_callable($definition)) {
                return $this->instances[$id] = $definition($this);
            }
            return $this->instances[$id] = $definition;
        }

        if (class_exists($id)) {
            return $this->instances[$id] = $this->autowire($id);
        }

        throw new ContainerException("Could not resolve {$id}");
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->definitions[$id]) || class_exists($id) || isset($this->bindings[$id]);
    }

    public function set(string $id, $concrete): void
    {
        $this->definitions[$id] = $concrete;
    }

    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        // Return existing instance if it exists
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        // If the concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }

        // Store the instance if it's a singleton
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function build(string $concrete, array $parameters = []): object
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $resolvedDependencies = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($resolvedDependencies);
    }

    private function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $resolvedDependencies = [];

        /** @var ReflectionParameter $dependency */
        foreach ($dependencies as $dependency) {
            // If the parameter was passed in, use it
            if (array_key_exists($dependency->getName(), $parameters)) {
                $resolvedDependencies[] = $parameters[$dependency->getName()];
                continue;
            }

            // If the parameter has a type hint, try to resolve it
            $type = $dependency->getType();
            if ($type && !$type->isBuiltin()) {
                $resolvedDependencies[] = $this->make($type->getName());
                continue;
            }

            // If the parameter has a default value, use it
            if ($dependency->isDefaultValueAvailable()) {
                $resolvedDependencies[] = $dependency->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(
                "Unable to resolve dependency: {$dependency->getName()}"
            );
        }

        return $resolvedDependencies;
    }

    private function autowire(string $class)
    {
        $reflector = new \ReflectionClass($class);
        
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class {$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if (!$type) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new ContainerException("Cannot resolve parameter {$parameter->getName()}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
