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

	public function boot(): void
	{
		foreach ($this->providers as $provider) {
			if (method_exists($provider, 'boot')) {
				$provider->boot();
			}
		}
	}
}
