<?php

use Radiant\Core\Container;

beforeEach(function () {
    $this->container = new Container();
});

it('can bind and resolve non-singleton instances', function () {
    $this->container->bind('foo', fn () => new stdClass());

    $first = $this->container->take('foo');
    $second = $this->container->take('foo');

    expect($first)->not()->toBe($second);
});

it('can bind and resolve singleton instances', function () {
    $this->container->singleton('bar', fn () => new stdClass());

    $first = $this->container->take('bar');
    $second = $this->container->take('bar');

    expect($first)->toBe($second);
});

it('throws exception if binding is missing', function () {
    $this->container->take('missing');
})->throws(Exception::class, 'No matching binding found for [missing]!');
