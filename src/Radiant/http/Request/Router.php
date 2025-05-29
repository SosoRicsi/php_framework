<?php

declare(strict_types=1);

namespace Radiant\http\Request;

use Radiant\http\Response\Response;

class Router
{
	private array $routes = [];
	private array $errors = [];

	private Response $response;
	private Request $request;

	private string $currentGroupPrefix = '';
	private array $currentGroupMiddleware = [];
	private array $currentGroupAfterMiddleware = [];
	private string $version = '';

	protected array $sharedInstances = [];

	private const SUPPORTED_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	public function __construct()
	{
		$this->request = new Request();
		$this->response = new Response();

		foreach (self::SUPPORTED_METHODS as $method) {
			$this->routes[$method] = [];
		}
	}

	public function setVersion(?string $version = ""): void
	{
		$this->version = $version ?? '';
	}

	public function get(string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$this->addRoute('GET', $path, $handler, $middleware, $afterMiddleware);
	}

	public function post(string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$this->addRoute('POST', $path, $handler, $middleware, $afterMiddleware);
	}

	public function put(string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$this->addRoute('PUT', $path, $handler, $middleware, $afterMiddleware);
	}

	public function patch(string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$this->addRoute('PATCH', $path, $handler, $middleware, $afterMiddleware);
	}

	public function delete(string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$this->addRoute('DELETE', $path, $handler, $middleware, $afterMiddleware);
	}

	public function options(string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$this->addRoute('OPTIONS', $path, $handler, $middleware, $afterMiddleware);
	}

	public function redirect(string $path, string $redirectTo): void
	{
		$this->addRoute('GET', $path, fn() => header("Location: {$redirectTo}", true, 302));
	}

	public function group(string $prefix, \Closure $callback, array $middleware = [], array $afterMiddleware = []): void
	{
		$prevPrefix = $this->currentGroupPrefix;
		$prevMiddleware = $this->currentGroupMiddleware;
		$prevAfter = $this->currentGroupAfterMiddleware;

		$this->currentGroupPrefix .= $prefix;
		$this->currentGroupMiddleware = array_merge($prevMiddleware, $middleware);
		$this->currentGroupAfterMiddleware = array_merge($prevAfter, $afterMiddleware);

		$callback($this);

		$this->currentGroupPrefix = $prevPrefix;
		$this->currentGroupMiddleware = $prevMiddleware;
		$this->currentGroupAfterMiddleware = $prevAfter;
	}

	public function version(\Closure $callback, array $middleware = [], array $afterMiddleware = [], ?string $prefix = '', ?string $version = ''): void
	{
		$ver = $version ?? $this->version;
		$prefix = $prefix ?: "/api/v{$ver}";

		$this->group($prefix, $callback, $middleware, $afterMiddleware);
	}

	private function addRoute(string $method, string $path, mixed $handler, array $middleware = [], array $afterMiddleware = []): void
	{
		$fullPath = rtrim($this->currentGroupPrefix . $path, '/') ?: '/';

		if (is_string($handler) && class_exists($handler)) {
			$handler = [$handler, '__invoke'];
		}

		$this->routes[$method][] = [
			'path' => $fullPath,
			'handler' => $handler,
			'middleware' => array_merge($this->currentGroupMiddleware, $middleware),
			'afterMiddleware' => array_merge($this->currentGroupAfterMiddleware, $afterMiddleware),
		];
	}

	public function afterMiddleware(array $middleware): void
	{
		$this->currentGroupAfterMiddleware = array_merge($this->currentGroupAfterMiddleware, $middleware);
	}

	public function errors(array $errors): void
	{
		foreach ($errors as $error) {
			$this->errors[$error['error']] = [
				'handler' => $error['handler']
			];
		}
	}

	private function match(string $requestPath, string $path, array &$params): bool
	{
		$paramNames = [];

		$pattern = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($matches) use (&$paramNames) {
			$paramNames[] = $matches[1];
			$regex = $matches[2] ?? '[^/]+';
			return "($regex)";
		}, $path);

		$pattern = "#^" . rtrim($pattern, '/') . "/?$#";

		if (preg_match($pattern, $requestPath, $matches)) {
			array_shift($matches); // teljes match kiszedése
			$params = array_combine($paramNames, $matches);
			$this->request->setRouterParams($params);
			return true;
		}

		return false;
	}

	private function resolveDependencies(array $params, mixed $handler, array $sharedObjects = []): array
	{
		$dependencies = [];
		$reflection = is_array($handler)
			? new \ReflectionMethod($handler[0], $handler[1])
			: new \ReflectionFunction($handler);

		foreach ($reflection->getParameters() as $param) {
			$name = $param->getName();
			$type = $param->getType();

			if (array_key_exists($name, $params)) {
				$dependencies[] = $params[$name];
			} elseif ($type && !$type->isBuiltin()) {
				$class = $type->getName();
				$dependencies[] = $sharedObjects[$class] ?? $this->instantiateOrGetSharedInstance($class);
			} elseif ($param->isDefaultValueAvailable()) {
				$dependencies[] = $param->getDefaultValue();
			} else {
				$dependencies[] = null;
			}
		}

		return $dependencies;
	}

	private function instantiateOrGetSharedInstance(string $className): object
	{
		return $this->sharedInstances[$className]
			??= new $className();
	}

	public function run(?string $uri = null, ?string $method = null): void
	{
		$requestUri = parse_url($uri ?? $_SERVER['REQUEST_URI']);
		$requestPath = $requestUri['path'] ?? '/';
		$method = $method ?? $_SERVER['REQUEST_METHOD'];
		$params = [];

		foreach ($this->routes[$method] ?? [] as $route) {
			if ($this->match($requestPath, $route['path'], $params)) {
				$this->resolveRequestSubclass($route['handler']);

				foreach ($route['middleware'] as $middleware) {
					if (!$this->instantiateOrGetSharedInstance($middleware)->handle($this->request, $this->response)) {
						return;
					}
				}

				$deps = $this->resolveDependencies($params, $route['handler'], [
					Request::class => $this->request,
					Response::class => $this->response
				]);

				is_array($route['handler'])
					? call_user_func_array([new $route['handler'][0], $route['handler'][1]], $deps)
					: call_user_func_array($route['handler'], $deps);

				foreach ($route['afterMiddleware'] as $middleware) {
					if (!$this->instantiateOrGetSharedInstance($middleware)->handle($this->request, $this->response)) {
						break;
					}
				}

				return;
			}
		}

		http_response_code(404);
		isset($this->errors['404'])
			? call_user_func($this->errors['404']['handler'])
			: print "404 - Page Not Found!";
	}

	private function resolveRequestSubclass(mixed $handler): void
	{
		if (!is_array($handler)) return;

		[$controllerClass, $method] = $handler;
		if (!class_exists($controllerClass)) return;

		$reflection = new \ReflectionMethod($controllerClass, $method);
		foreach ($reflection->getParameters() as $param) {
			$type = $param->getType();
			if ($type && !$type->isBuiltin() && is_subclass_of($type->getName(), Request::class)) {
				$this->request = new ($type->getName())();
				return;
			}
		}
	}

	public function info(?bool $showRoutes = false, ?bool $showErrorHandlers = false): void
	{
		print "<pre>";
		print "Routes: " . array_sum(array_map('count', $this->routes)) . "\n";
		print "App version: " . ($this->version ?: 'N/A') . "\n";
		print "Has 404: " . (isset($this->errors['404']) ? 'true' : 'false') . "\n";

		if ($showRoutes) print_r($this->routes);
		if ($showErrorHandlers) print_r($this->errors);
		print "</pre>";
	}
}
