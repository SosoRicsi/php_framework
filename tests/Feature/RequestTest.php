<?php

declare(strict_types=1);

use Radiant\Collection\SmartCollection;
use Radiant\Http\Request\Request;
use Tests\Mocks\MockPhpStream;

beforeEach(function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/user/42';
    $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
    $_POST['name'] = 'Ricsi';
    $_POST['age'] = 30;
});

beforeAll(function () {
    // Regisztráld a mock stream wrappert
    stream_wrapper_unregister('php');
    stream_wrapper_register('php', MockPhpStream::class);
});

afterAll(function () {
    // Állítsd vissza
    stream_wrapper_restore('php');
});

test('it checks the method', function () {
    $request = new Request;

    expect($request->getMethod())->toBe('POST');
});

test('it resolves the uri', function () {
    $request = new Request;

    expect($request->getResolvedUri())->toMatchArray(['api', 'user', '42']);
});

test('it ensure that the request is json', function () {
    $request = new Request;

    expect($request->isJSON())->toBe(true);
});

test('it checks the request uri', function () {
    $request = new Request;

    expect($request->getUri())->toBe('/api/user/42');
});

test('it checks body type', function () {
    $request = new Request;
    $body = $request->getBody();

    expect($body)->toBeInstanceOf(SmartCollection::class);
});

test('it checks if the body is readable', function () {
    $request = new Request;
    $body = $request->getBody();

    expect($body['name'])->toBe('Ricsi');
    expect($body['age'])->toBe(30);
});
