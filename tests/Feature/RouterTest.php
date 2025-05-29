<?php

use Radiant\Http\Request\Route\Router;

beforeEach(function () {
    $this->router = new Router();
});

test('it can register GET route', function () {
    $this->router->get('/test', function () {
		print "ok";
	});
    ob_start();
    $this->router->run('/test', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('ok');
});

test('it can register POST route', function () {
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

test('it calls middleware before handler', function () {
    $called = false;

    $middleware = new class {
        public function handle($request, $response) {
            print 'MW|';
            return true;
        }
    };

    $this->router->get('/protected', function () {
        echo 'handler';
    })->middleware([get_class($middleware)]);

    ob_start();
    $this->router->run('/protected', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('MW|handler');
});

test('it calls after middleware after handler', function () {
    $middleware = new class {
        public function handle($request, $response) {
            print '|AFTER';
            return true;
        }
    };

    $this->router->get('/done', function () {
        echo 'handler';
    })->afterMiddleware([get_class($middleware)]);

    ob_start();
    $this->router->run('/done', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('handler|AFTER');
});

test('middleware can stop execution', function () {
    $middleware = new class {
        public function handle($request, $response) {
            echo 'BLOCKED';
            return false;
        }
    };

    $this->router->get('/blocked', function () {
        echo 'handler';
    })->middleware([get_class($middleware)]);

    ob_start();
    $this->router->run('/blocked', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('BLOCKED');
});

test('it can register named route', function () {
    $this->router->get('/named', function () {
        echo 'named';
    })->name('custom.route');

    ob_start();
    $this->router->run('/named', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('named');
});

test('it handles route with regex param', function () {
    $this->router->get('/order/{id:\d+}', function ($id) {
        echo "Order: $id";
    });

    ob_start();
    $this->router->run('/order/123', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('Order: 123');
});