<?php

declare(strict_types=1);

use Radiant\Http\Response;

test('it sets and gets status code', function () {
    $response = new Response();
    $response->setStatusCode(404);

    expect($response->getStatusCode())->toBe(404);
});

test('it adds and retrieves headers', function () {
    $response = new Response();
    $response->addHeader('Content-Type', 'application/json');

    $headers = $response->getHeaders();

    expect($headers)->toHaveKey('Content-Type');
    expect($headers['Content-Type'])->toBe('application/json');
});

test('it sets and gets body', function () {
    $response = new Response();
    $response->setBody(['success' => true]);

    expect($response->getBody())->toBe(['success' => true]);
});

test('it merges array body correctly', function () {
    $response = new Response();
    $response->setBody(['name' => 'Ricsi']);

    $reflection = new ReflectionClass($response);
    $method = $reflection->getMethod('mergeBody');
    $method->setAccessible(true);
    $method->invoke($response, ['age' => 30]);

    expect($response->getBody())->toBe(['name' => 'Ricsi', 'age' => 30]);
});

test('it converts array body to json in toOutput()', function () {
    $response = new Response();
    $response->setBody(['key' => 'value']);

    expect($response->toOutput())->toBe(json_encode(['key' => 'value']));
});

test('it converts string body to string in toOutput()', function () {
    $response = new Response();
    $response->setBody('Hello, world!');

    expect($response->toOutput())->toBe('Hello, world!');
});
