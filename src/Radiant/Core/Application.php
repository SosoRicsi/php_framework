<?php

namespace Radiant\Core;

class Application
{
	protected array $providers = [];

	protected array $booted = [];

	public function registerProviders(array $providers): void
	{
		foreach ($providers as $providerClass) {
			$provider = new $providerClass($this);
			$provider->register();
			$this->providers[] = $provider;
		}
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
