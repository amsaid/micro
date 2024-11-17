<?php

namespace SdFramework\Container;

use Psr\Container\ContainerInterface;
use SdFramework\Exception\ContainerException;
use SdFramework\Exception\NotFoundException;

class Container implements ContainerInterface
{
    private array $instances = [];
    private array $definitions = [];

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
        return isset($this->instances[$id]) || isset($this->definitions[$id]) || class_exists($id);
    }

    public function set(string $id, $concrete): void
    {
        $this->definitions[$id] = $concrete;
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
