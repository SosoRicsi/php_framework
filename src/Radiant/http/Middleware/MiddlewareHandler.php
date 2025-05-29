<?php

namespace Radiant\Http\Middleware;

use Radiant\Http\Request\Request;
use Radiant\Http\Response\Response;

class MiddlewareHandler
{

	private array $middlewareStack = [];

	public function add(MiddlewareInterface $middleware): self
	{
		$this->middlewareStack[] = $middleware;
		return $this;
	}

	public function handle(Request $request, Response $response, callable $final): Response
	{
		$middleware = array_reverse($this->middlewareStack);

		$next = $final;

		foreach ($middleware as $layer) {
			$current = $layer;
			$next = function (Request $req, Response $res) use ($current, $next) {
				return $current->handle($req, $res, $next);
			};
		}

		return $next($request, $response);
	}

}