<?php

declare(strict_types=1);

use Radiant\Collection\SmartCollection;
use Radiant\Session\Session;

if (! function_exists('wrap')) {
    function wrap(array $items)
    {
        return new SmartCollection($items);
    }
}

if (! function_exists('sessid')) {
    function sessid()
    {
        return Session::sessid();
    }
}

if (! function_exists('session_save')) {
    function session_save(string $key, mixed $item)
    {
        Session::set($key, $item);
    }
}

if (! function_exists('session')) {
    function session(string $key, mixed $default = null)
    {
        return Session::get($key) ?: $default;
    }
}
