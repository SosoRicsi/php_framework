<?php

declare(strict_types=1);

use Radiant\Collection\SmartCollection;

test('it can add and retrieve items using add()', function () {
    $collection = new SmartCollection;
    $collection->add('value1', 'key1');
    $collection->add('value2');

    expect($collection->all())->toMatchArray([
        'key1' => 'value1',
        0 => 'value2',
    ]);
});

test('it can count the values', function () {
    $collection = new SmartCollection([1, 2, 3]);

    expect($collection->count())->toBe(3);
});

test('it throws exception on missing __get key', function () {
    $collection = new SmartCollection;

    expect(fn () => $collection->missing)->toThrow(InvalidArgumentException::class);
});

it('supports array access', function () {
    $collection = new SmartCollection;
    $collection['foo'] = 'bar';

    expect($collection['foo'])->toBe('bar')
        ->and(isset($collection['foo']))->toBeTrue();

    unset($collection['foo']);
    expect(isset($collection['foo']))->toBeFalse();
});

test('it can filter values', function () {
    $collection = new SmartCollection(['a' => 1, 'b' => 2, 'c' => 3]);
    $filtered = $collection->filter(fn ($value) => $value > 1);

    expect($filtered->all())->toMatchArray(['b' => 2, 'c' => 3]);
});

test('it can map values', function () {
    $collection = new SmartCollection([1, 2, 3]);
    $mapped = $collection->map(fn ($value) => $value * 2);

    expect($mapped->all())->toMatchArray([2, 4, 6]);
});

test('it can merge arrays', function () {
    $collection = new SmartCollection(['a' => 1]);
    $collection->merge(['b' => 2]);

    expect($collection->all())->toMatchArray(['a' => 1, 'b' => 2]);
});

test('it can collapse nested arrays', function () {
    $collection = new SmartCollection([
        'a' => ['x' => 1],
        'b' => ['y' => ['z' => 2]],
    ]);

    $collection->collapse();

    expect($collection->all())->toHaveKey('.a.x')
        ->and($collection->all())->toHaveKey('.b.y.z');
});

test('it can convert to json', function () {
    $collection = new SmartCollection(['a' => 1]);
    expect($collection->toJson())->toBe('{"a":1}');
});

test('it can return first and last items', function () {
    $collection = new SmartCollection(['first', 'middle', 'last']);

    expect($collection->first())->toBe('first')
        ->and($collection->last())->toBe('last');
});

test('it can chunk the collection', function () {
    $collection = new SmartCollection([1, 2, 3, 4]);
    $chunks = $collection->chunk(2);

    expect(count($chunks))->toBe(2)
        ->and($chunks[0]->all())->toBe([1, 2])
        ->and($chunks[1]->all())->toBe([3, 4]);
});

test('it can use the wrap() helper', function () {
    $collection = wrap([1, 2, 3]);

    expect($collection)->toMatchArray([1, 2, 3]);
});
