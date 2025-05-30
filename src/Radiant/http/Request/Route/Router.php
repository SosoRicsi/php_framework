<?php

declare(strict_types=1);

namespace Radiant\Http\Request\Route;

use Radiant\Core\Application;
use Radiant\Http\Request\Request;
use Radiant\http\Response\Response;
use Radiant\Http\Middleware\MiddlewareHandler;
use Radiant\Http\Middleware\MiddlewareInterface;

class Router
{
	private array $routes = [];
	private array $errors = [];

	private Response $response;
	private ?Request $request = null;

	private string $currentGroupPrefix = '';
	private array $currentGroupMiddleware = [];
	private array $currentGroupAfterMiddleware = [];
	private string $version = '';

	protected array $sharedInstances = [];

	private const SUPPORTED_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	public function __construct()
	{
		$this->response = new Response();

		foreach (self::SUPPORTED_METHODS as $method) {
			$this->routes[$method] = [];
		}
	}

	public function setApplication(Application $app): void
	{
		$this->sharedInstances[Application::class] = $app;
	}


	public function setVersion(?string $version = ""): void
	{
		$this->version = $version ?? '';
	}

	public function get(string $path, mixed $handler): RouteDefinition
	{
		return new RouteDefinition($this, 'GET', $path, $handler);
	}

	public function post(string $path, mixed $handler): RouteDefinition
	{
		return new RouteDefinition($this, 'POST', $path, $handler);
	}

	public function put(string $path, mixed $handler): RouteDefinition
	{
		return new RouteDefinition($this, 'PUT', $path, $handler);
	}

	public function patch(string $path, mixed $handler): RouteDefinition
	{
		return new RouteDefinition($this, 'PATCH', $path, $handler);
	}

	public function delete(string $path, mixed $handler): RouteDefinition
	{
		return new RouteDefinition($this, 'DELETE', $path, $handler);
	}

	public function options(string $path, mixed $handler): RouteDefinition
	{
		return new RouteDefinition($this, 'OPTIONS', $path, $handler);
	}

	public function redirect(string $path, string $redirectTo): RouteDefinition
	{
		return new RouteDefinition($this, 'POST', $path, function () use ($redirectTo) {
			header("Location: " . $redirectTo, true, 302);
		});
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

	public function registerRoute(string $method, string $path, mixed $handler, array $middleware = [], array $afterMiddleware = [], string $name = ""): void
	{
		$this->addRoute($method, $path, $handler, $middleware, $afterMiddleware, $name);
	}

	private function addRoute(string $method, string $path, mixed $handler, array $middleware = [], array $afterMiddleware = [], string $name = ""): void
	{
		$fullPath = rtrim($this->currentGroupPrefix . $path, '/') ?: '/';

		if (is_string($handler) && class_exists($handler)) {
			$handler = [$handler, '__invoke'];
		}

		$resolvedMiddleware = [];
		foreach ($middleware as $item) {
			if (is_string($item) && str_starts_with($item, '@')) {
				$group = ltrim($item, '@');

				$app = $this->sharedInstances[Application::class] ?? null;

				if ($app) {
					$resolvedMiddleware = array_merge($resolvedMiddleware, $app->getMiddlewareGroup($group));
				}
			} else {
				$resolvedMiddleware[] = $item;
			}
		}

		$this->routes[$method][] = [
			'path' => $fullPath,
			'handler' => $handler,
			'middleware' => array_merge($this->currentGroupMiddleware, $resolvedMiddleware),
			'afterMiddleware' => array_merge($this->currentGroupAfterMiddleware, $afterMiddleware),
			'name' => $name
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

	private function instantiateOrGetSharedInstance(string|object $classOrInstance): object
	{
		if (is_object($classOrInstance)) {
			return $classOrInstance;
		}

		return $this->sharedInstances[$classOrInstance]
			??= new $classOrInstance();
	}

	public function run(?string $uri = null, ?string $method = null): void
	{
		if (!$this->request instanceof Request) {
			$this->request = new Request();
		}

		$requestUri = parse_url($uri ?? $_SERVER['REQUEST_URI']);
		$requestPath = $requestUri['path'] ?? '/';
		$method = $method ?? $_SERVER['REQUEST_METHOD'];
		$params = [];

		foreach ($this->routes[$method] ?? [] as $route) {
			if ($this->match($requestPath, $route['path'], $params)) {
				$this->resolveRequestSubclass($route['handler']);

				$handler = function (Request $req, Response $res) use ($route, $params) {
					$deps = $this->resolveDependencies($params, $route['handler'], [
						Request::class => $req,
						Response::class => $res
					]);

					is_array($route['handler'])
						? call_user_func_array([new $route['handler'][0], $route['handler'][1]], $deps)
						: call_user_func_array($route['handler'], $deps);

					return $res;
				};

				$middlewareHandler = new MiddlewareHandler();

				// Add pre-handler middleware
				foreach ($route['middleware'] as $middlewareClass) {
					$instance = $this->instantiateOrGetSharedInstance($middlewareClass);
					if (!$instance instanceof MiddlewareInterface) {
						throw new \RuntimeException("$middlewareClass must implement MiddlewareInterface");
					}
					$middlewareHandler->add($instance);
				}

				$response = $middlewareHandler->handle($this->request, $this->response, $handler);

				// Add and run after-middleware (if needed, you can wrap these in another MiddlewareHandler too)
				foreach ($route['afterMiddleware'] as $afterMiddleware) {
					$instance = $this->instantiateOrGetSharedInstance($afterMiddleware);
					if ($instance instanceof MiddlewareInterface) {
						$response = $instance->handle($this->request, $response, fn($req, $res) => $res);
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
