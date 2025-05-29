<?php

declare(strict_types=1);

use Radiant\Container\Container;
use Radiant\Container\Exceptions\ContainerException;

beforeEach(function () {
    $this->container = new Container;
});

test('it can bind and resolve a non-singleton', function () {
    $this->container->bind('foo', fn () => new stdClass);

    $first = $this->container->take('foo');
    $second = $this->container->take('foo');

    expect($first)->not()->toBe($second); // külön példányok
});

test('it can bind and resolve a singleton', function () {
    $this->container->singleton('bar', fn () => new stdClass);

    $first = $this->container->take('bar');
    $second = $this->container->take('bar');

    expect($first)->toBe($second); // ugyanaz a példány
});

test('it throws exception if class does not exist', function () {
    expect(fn () => $this->container->take('NonExistentClass'))
        ->toThrow(ContainerException::class);
});

test('it throws exception if class is not instantiable', function () {
    abstract class AbstractExample {}

    expect(fn () => $this->container->take(AbstractExample::class))
        ->toThrow(ContainerException::class);
});

test('it can autowire classes with no dependencies', function () {
    final class NoDeps {}

    $instance = $this->container->take(NoDeps::class);

    expect($instance)->toBeInstanceOf(NoDeps::class);
});

test('it can autowire dependencies recursively', function () {
    final class Foo {}
    final class Bar
    {
        public function __construct(public Foo $foo) {}
    }
    final class Baz
    {
        public function __construct(public Bar $bar) {}
    }

    $baz = $this->container->take(Baz::class);

    expect($baz)->toBeInstanceOf(Baz::class)
        ->and($baz->bar)->toBeInstanceOf(Bar::class)
        ->and($baz->bar->foo)->toBeInstanceOf(Foo::class);
});

test('it throws if constructor has unresolvable builtin dependency', function () {
    final class NeedsScalar
    {
        public function __construct(string $name) {}
    }

    expect(fn () => $this->container->take(NeedsScalar::class))
        ->toThrow(ContainerException::class);
});
