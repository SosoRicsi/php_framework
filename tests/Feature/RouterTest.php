<?php

use Radiant\http\Router;

beforeEach(function () {
    $this->router = new Router();
});

test('can register GET route', function () {
    $this->router->get('/test', function () {
		print "ok";
	});
    ob_start();
    $this->router->run('/test', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('ok');
});

test('can register POST route', function () {
    $this->router->post('/submit', function () {
		print "submitted";
	});
    ob_start();
    $this->router->run('/submit', 'POST');
    $output = ob_get_clean();

    expect($output)->toBe('submitted');
});

test('group adds prefix to route', function () {
    $this->router->group('/admin', function ($router) {
        $router->get('/dashboard', function () {
			print "admin panel";
		});
    });

    ob_start();
    $this->router->run('/admin/dashboard', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('admin panel');
});

test('version adds versioned prefix', function () {
    $this->router->version(function ($router) {
        $router->get('/status', function () {
			print "v1";
		});
    }, prefix: '', version: '1');

    ob_start();
    $this->router->run('/api/v1/status', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('v1');
});

test('route parameters work', function () {
    $this->router->get('/user/{id}', function ($id) {
        echo "User: $id";
    });

    ob_start();
    $this->router->run('/user/42', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('User: 42');
});

test('404 fallback works', function () {
    ob_start();
    $this->router->run('/nonexistent', 'GET');
    $output = ob_get_clean();

    expect($output)->toContain('404');
});
