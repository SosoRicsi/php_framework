<?php

namespace Radiant\Core;

class Application
{
	protected array $providers = [];

	protected array $booted = [];

	protected array $middlewareGroups = [];

	protected Container $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->middlewareGroups['web'] = [
			\Radiant\Http\Middleware\StartSession::class,
		];
	}

	public function container(): Container
	{
		return $this->container;
	}

	public function registerProviders(array $providers): void
	{
		foreach ($providers as $providerClass) {
			$provider = new $providerClass($this);
			$provider->register();
			$this->providers[] = $provider;
		}
	}

	public function defineMiddlewareGroup(string $name, array $middlewareList): void
	{
		$this->middlewareGroups[$name] = $middlewareList;
	}

	public function getMiddlewareGroup(string $name): array
	{
		return $this->middlewareGroups[$name] ?? [];
	}

	public function web(?array $append = null, ?array $set = null): array
	{
		if (!empty($set)) {
			$this->middlewareGroups['web'] = $set;
			return $this->middlewareGroups['web'];
		}

		$group = $this->middlewareGroups['web'] ?? [];

		if (!empty($append)) {
			foreach ($append as $middleware) {
				if (!in_array($middleware, $group, true)) {
					$group[] = $middleware;
				}
			}
		}

		$this->middlewareGroups['web'] = array_values($group); // újraindexelés
		return $this->middlewareGroups['web'];
	}

	public function boot(): void
	{
		foreach ($this->providers as $provider) {
			if (method_exists($provider, 'boot')) {
				$provider->boot();
			}
		}
	}
}
