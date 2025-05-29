<?php

declare(strict_types=1);

namespace Tests\Mocks;

final class MockPhpStream
{
    public $context;

    private $index = 0;

    private string $data;

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $this->data = '{"name": "Ricsi", "age": 30}';

        return true;
    }

    public function stream_read($count): string
    {
        $chunk = mb_substr($this->data, $this->index, $count);
        $this->index += mb_strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->index >= mb_strlen($this->data);
    }

    public function stream_stat(): array
    {
        return [];
    }
}
