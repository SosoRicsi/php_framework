<?php

use Radiant\Http\Middleware\MiddlewareHandler;
use Radiant\Http\Middleware\MiddlewareInterface;
use Radiant\Http\Request\Request;
use Radiant\Http\Response\Response;

class LoggingMiddleware implements MiddlewareInterface
{
	public array $log;
	public function __construct(array &$log)
	{
		$this->log = &$log;
	}

	public function handle(Request $request, Response $response, callable $next): Response
	{
		$this->log[] = 'before';
		$response = $next($request, $response);
		$this->log[] = 'after';
		return $response;
	}
}

test('middleware handler calls middleware in correct order and executes final callback', function () {
	$log = [];

	$request = new Request();
	$response = new Response();

	$middleware1 = new LoggingMiddleware($log);
	$middleware2 = new LoggingMiddleware($log);

	$handler = new MiddlewareHandler();
	$handler->add($middleware1)->add($middleware2);

	$final = function (Request $req, Response $res) use (&$log) {
		$log[] = 'final';
		return $res;
	};

	$handler->handle($request, $response, $final);

	expect($log)->toEqual([
		'before', // middleware2
		'before', // middleware1
		'final',
		'after',  // middleware1
		'after',  // middleware2
	]);
});
