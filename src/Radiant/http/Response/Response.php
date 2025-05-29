<?php

declare(strict_types=1);

namespace Radiant\http\Response;

final class Response
{
    private int $status_code;

    private array $headers = [];

    private mixed $body;

    public function __construct(int $status_code = 200, mixed $body = null)
    {
        $this->status_code = $status_code;
        $this->body = $body;
    }

    public function setStatusCode(int $code): self
    {
        $this->status_code = $code;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setBody(mixed $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function mergeBody(array $body): self
    {
        $this->body = is_array($this->body) ? array_merge($this->body, $body) : $body;

        return $this;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function send(bool $reset = false): void
    {
        http_response_code($this->status_code);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        if (! empty($this->body)) {
            echo $this->toOutput();
        }

        if ($reset) {
            $this->reset();
        }
    }

    public function toOutput(): string
    {
        if (is_array($this->body)) {
            return json_encode($this->body);
        }

        return (string) $this->body;
    }

    private function reset(): void
    {
        $this->status_code = 200;
        $this->headers = [];
        $this->body = null;
    }
}
