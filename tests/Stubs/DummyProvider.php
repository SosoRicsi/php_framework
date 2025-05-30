<?php

namespace Tests\Stubs;

use Radiant\Core\Application;

class DummyProvider
{
	public bool $registered = false;
	public bool $booted = false;

	public function __construct(protected Application $app) {}

	public function register(): void
	{
		$this->registered = true;
	}

	public function boot(): void
	{
		$this->booted = true;
	}
}
