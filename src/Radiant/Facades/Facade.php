<?php

namespace Radiant\Facades;

abstract class Facade
{
	protected static array $instances = [];

	abstract protected static function getFacadeAccessor(): string;

	protected static function resolveInstance(): object
	{
		$key = static::getFacadeAccessor();

		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = static::registerAccessor();
		}

		return self::$instances[$key];
	}

	public static function getInstance(): object
	{
		return static::resolveInstance();
	}

	abstract protected static function registerAccessor(): object;

	public static function __callStatic($method, $args)
	{
		$instance = static::resolveInstance();

		if (!method_exists($instance, $method)) {
			throw new \BadMethodCallException("Method {$method} does not exist on class " . get_class($instance));
		}

		return $instance->$method(...$args);
	}
}
