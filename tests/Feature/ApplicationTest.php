<?php

use Radiant\Core\Application;
use Tests\Stubs\DummyProvider;

it('registers and boots providers', function () {
	$app = new Application();

	$app->registerProviders([DummyProvider::class]);

	$reflection = new ReflectionClass($app);
	$property = $reflection->getProperty('providers');
	$property->setAccessible(true);
	$providers = $property->getValue($app);

	expect($providers)->toHaveCount(1);
	expect($providers[0])->toBeInstanceOf(DummyProvider::class);
	expect($providers[0]->registered)->toBeTrue();

	$app->boot();

	expect($providers[0]->booted)->toBeTrue();
});
