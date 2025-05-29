<?php

declare(strict_types=1);

namespace Radiant\Container;

use Closure;
use Radiant\Container\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionException;

final class Container
{
    private array $bindings = [];

    private array $instances = [];

    public function bind(string $key, Closure $resolver, bool $singleton = false): void
    {
        $this->bindings[$key] = [
            'resolver' => $resolver,
            'singleton' => $singleton,
        ];
    }

    public function singleton(string $key, Closure $resolver): void
    {
        $this->bind($key, $resolver, true);
    }

    public function take(string $key): mixed
    {
        // Visszaadja a singleton példányt, ha már létezik
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // Ha van bindolva
        if (isset($this->bindings[$key])) {
            $binding = $this->bindings[$key];
            $object = call_user_func($binding['resolver'], $this);

            if ($binding['singleton']) {
                $this->instances[$key] = $object;
            }

            return $object;
        }

        // Autowiring fallback
        if (! class_exists($key)) {
            throw new ContainerException("No binding and no class found for [{$key}].");
        }

        try {
            $reflection = new ReflectionClass($key);

            if (! $reflection->isInstantiable()) {
                throw new ContainerException("Class [{$key}] is not instantiable.");
            }

            $constructor = $reflection->getConstructor();

            if (is_null($constructor)) {
                return new $key;
            }

            $dependencies = [];

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (! $type || $type->isBuiltin()) {
                    throw new ContainerException("Cannot resolve parameter \${$parameter->getName()} of [{$key}].");
                }

                $dependencies[] = $this->take($type->getName());
            }

            return $reflection->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to reflect class [{$key}]: {$e->getMessage()}", 0, $e);
        }
    }
}
