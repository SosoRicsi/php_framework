<?php

namespace Tests\Mocks;

class MockPhpStream
{
    private $index = 0;
    private string $data;
    public $context;

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $this->data = '{"name": "Ricsi", "age": 30}';
        return true;
    }

    public function stream_read($count): string
    {
        $chunk = substr($this->data, $this->index, $count);
        $this->index += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->index >= strlen($this->data);
    }

    public function stream_stat(): array
    {
        return [];
    }
}
