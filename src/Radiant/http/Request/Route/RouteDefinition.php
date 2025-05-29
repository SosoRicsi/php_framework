<?php

namespace Radiant\Http\Request\Route;

class RouteDefinition
{
	private Router $router;

	private string $method;

	private string $path;

	private mixed $handler;

	private string $name = "";

	private array $middleware = [];

	private array $afterMiddleware = [];

	public function __construct(Router $router, string $method, string $path, mixed $handler) {
		$this->router = $router;
		$this->method = $method;
		$this->path = $path;
		$this->handler = $handler;
	}

	public function middleware(array $middleware): self
	{
		$this->middleware = $middleware;
		return $this;
	}

	public function afterMiddleware(array $after): self
	{
		$this->afterMiddleware = $after;
		return $this;
	}

	public function name(string $name): self
	{
		$this->name = $name;

		return $this;
	}


	public function register(): void
	{
		$this->router->registerRoute(
			$this->method,
			$this->path,
			$this->handler,
			$this->middleware,
			$this->afterMiddleware,
			$this->name,
		);
	}

	public function __destruct()
	{
		$this->register();
	}
}
