<?php

declare(strict_types=1);

namespace Radiant\Core;

final class Config
{
    private array $items;

    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function get(string $key, mixed $default = null)
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (! isset($this->items[$file])) {
            $this->loadFile($file);
        }

        $value = $this->items[$file];

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function all()
    {
        return $this->items;
    }

    private function loadFile(string $file): void
    {
        $path = $this->configPath.'/'.$file.'.php';

        if (file_exists($path)) {
            $this->items[$file] = require $path;
        } else {
            $this->items[$file] = [];
        }
    }
}
