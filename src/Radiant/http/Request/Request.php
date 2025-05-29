<?php

declare(strict_types=1);

namespace Radiant\Http\Request;

use Radiant\Collection\SmartCollection;

class Request
{
	public string $method;
	public string $uri;
	public array $headers;
	public mixed $body;
	public array $validator_errors = [];
	public array $router_params = [];

	public function __construct()
	{
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$this->uri = $_SERVER['REQUEST_URI'] ?? '/';

		$headers = function_exists('getallheaders') ? getallheaders() : $this->getAllHeadersFallback();
		$this->headers = array_change_key_case($headers, CASE_LOWER);

		$this->body = file_get_contents('php://input');
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getResolvedUri(): array
	{
		$path = parse_url($this->uri, PHP_URL_PATH);
		return explode('/', trim($path, '/'));
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getHeader(string $key, mixed $default = null): mixed
	{
		$key = strtolower($key);
		return $this->headers[$key] ?? $default;
	}

	public function setBody(string|array $body): void
	{
		$this->body = is_array($body) ? json_encode($body) : $body;
	}

	public function mergeBody(array $newData): void
	{
		$existingData = is_string($this->body) ? json_decode($this->body, true) : $this->body;

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Invalid JSON format in body during merge.");
		}

		$merged = array_merge($existingData ?? [], $newData);
		$this->body = json_encode($merged);
	}

	public function getBody(?string $key = null): mixed
	{
		$data = is_string($this->body) ? json_decode($this->body, true) : $this->body;

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Invalid JSON format in request body!");
		}

		return $key !== null ? ($data[$key] ?? null) : new SmartCollection($data);
	}

	public function getJsonBody(?string $key = null): mixed
	{
		$data = is_array($this->body) ? $this->body : json_decode($this->body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Invalid JSON format!");
		}

		return $key !== null ? ($data[$key] ?? null) : $data;
	}

	public function get(string $key = null, mixed $default = null): mixed
	{
		return $key !== null ? ($_GET[$key] ?? $default) : $_GET;
	}

	public function isContentType(string $type): bool
	{
		return $this->getHeader('Content-Type') === $type;
	}

	public function isJSON(): bool
	{
		return $this->isContentType('application/json');
	}

	public function isFormData(): bool
	{
		return $this->isContentType('multipart/form-data');
	}

	public function setRouterParams(array $params): void
	{
		$this->router_params = $params;
	}

	public function rParam(string $param, mixed $default = null): mixed
	{
		return $this->router_params[$param] ?? $default;
	}

	public function rParams(): array
	{
		return $this->router_params;
	}

	private function getAllHeadersFallback(): array
	{
		$headers = [];

		foreach ($_SERVER as $name => $value) {
			if (str_starts_with($name, 'HTTP_')) {
				$header = str_replace('_', '-', substr($name, 5));
				$headers[$header] = $value;
			}
		}

		return $headers;
	}
}
