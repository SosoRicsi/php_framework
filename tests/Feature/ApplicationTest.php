<?php

use Radiant\Core\Container;
use Radiant\Core\Application;
use Tests\Stubs\DummyProvider;
use Radiant\Http\Middleware\StartSession;

beforeEach(function () {
    $this->app = new Application(new Container());
});

it('registers and boots providers', function () {
	$this->app->registerProviders([DummyProvider::class]);

	$reflection = new ReflectionClass($this->app);
	$property = $reflection->getProperty('providers');
	$property->setAccessible(true);
	$providers = $property->getValue($this->app);

	expect($providers)->toHaveCount(1);
	expect($providers[0])->toBeInstanceOf(DummyProvider::class);
	expect($providers[0]->registered)->toBeTrue();

	$this->app->boot();

	expect($providers[0]->booted)->toBeTrue();
});


it('has a default web middleware group', function () {
    expect($this->app->getMiddlewareGroup('web'))
        ->toBe([StartSession::class]);
});

it('can append middleware to the web group', function () {
    $this->app->web(append: [
        'AnotherMiddleware',
        StartSession::class // ne ismétlődjön
    ]);

    expect($this->app->getMiddlewareGroup('web'))
        ->toBe([
            StartSession::class,
            'AnotherMiddleware'
        ]);
});

it('can fully overwrite the web group', function () {
    $this->app->web(set: ['CustomMiddleware']);

    expect($this->app->getMiddlewareGroup('web'))
        ->toBe(['CustomMiddleware']);
});