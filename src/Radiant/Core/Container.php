<?php declare(strict_types=1);

namespace Radiant\Core;

class Container 
{
	protected array $bindings = [];
	protected array $instances = [];

	public function bind(string $key, callable $resolver, bool $singleton = false): void
	{
		$this->bindings[$key] = [
			'resolver' => $resolver,
			'singleton' => $singleton,
		];
	}

	public function singleton(string $key, callable $resolver): void
	{
		$this->bind($key, $resolver, true);
	}

	public function take(string $key): mixed
	{
		if (!array_key_exists($key, $this->bindings)) {
			throw new \Exception("No matching binding found for [{$key}]!");
		}

		$binding = $this->bindings[$key];

		if ($binding['singleton']) {
			if (!isset($this->instances[$key])) {
				$this->instances[$key] = call_user_func($binding['resolver']);
			}
			return $this->instances[$key];
		}

		return call_user_func($binding['resolver']);
	}
}
