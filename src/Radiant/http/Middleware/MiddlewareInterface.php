<?php

namespace Radiant\Http\Middleware;

use Radiant\Http\Request\Request;
use Radiant\Http\Response\Response;

interface MiddlewareInterface
{
	public function handle(Request $request, Response $response, callable $next): Response;
}