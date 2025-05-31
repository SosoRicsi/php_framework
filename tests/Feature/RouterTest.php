<?php

use Radiant\Core\Application;
use Radiant\Core\Container;
use Radiant\Http\Response\Response;
use Radiant\Http\Request\Route\Router;
use Radiant\Http\Middleware\MiddlewareInterface;

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

    $middleware = new class implements MiddlewareInterface {
        public function handle($request, $response, $next): Response
        {
            print 'MW|';
            return $next($request, $response);
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
    $middleware = new class implements MiddlewareInterface {
        public function handle($request, $response, $next): Response
        {
            print '|AFTER';
            return $next($request, $response);
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
    $middleware = new class implements MiddlewareInterface {
        public function handle($request, $response, $next): Response
        {
            echo 'BLOCKED';
            return $response;
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

test('it uses custom Request subclass automatically', function () {
    require_once dirname(__DIR__) . '/stubs/CustomRequest.php';

    $this->router->get('/custom', function (\Tests\Stubs\CustomRequest $req) {
        echo 'Custom: ' . $req->customValue();
    });

    ob_start();
    $this->router->run('/custom', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('Custom: from subclass');
});

test('versioned group uses shared middleware correctly', function () {
    $this->router->version(function ($router) {
        $router->get('/ping', function () {
            echo 'pong';
        });
    }, middleware: [new class implements MiddlewareInterface {
        public function handle($req, $res, $next): Response
        {
            echo 'PRE|';
            return $next($req, $res);
        }
    }], afterMiddleware: [new class implements MiddlewareInterface {
        public function handle($req, $res, $next): Response
        {
            $next($req, $res);
            echo '|POST';
            return $res;
        }
    }], version: '2');

    ob_start();
    $this->router->run('/api/v2/ping', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('PRE|pong|POST');
});

test('route injects object dependency if not built-in', function () {
    $this->router->get('/inject', function (Response $res) {
        echo get_class($res);
    });

    ob_start();
    $this->router->run('/inject', 'GET');
    $output = ob_get_clean();

    expect($output)->toContain('Radiant\Http\Response\Response');
});

test('middleware group works via Application', function () {
    $app = new Application(new Container);
    $app->defineMiddlewareGroup('web', [
        new class implements MiddlewareInterface {
            public function handle($request, $response, $next): Response
            {
                echo 'MW1|';
                return $next($request, $response);
            }
        },
        new class implements MiddlewareInterface {
            public function handle($request, $response, $next): Response
            {
                echo 'MW2|';
                return $next($request, $response);
            }
        }
    ]);

    $router = new Router();

    // Ez volt a hiányzó lépés
    $router->setApplication($app);

    $router->get('/home', function () {
        echo 'handler';
    })->middleware(['@web']);

    ob_start();
    $router->run('/home', 'GET');
    $out = ob_get_clean();

    expect($out)->toBe('MW1|MW2|handler');
});

test('it supports fluent route chaining', function () {
    $this->router->get('/fluent', function () {
        echo 'chained';
    })->middleware([])->name('fluent.route')->afterMiddleware([]);

    ob_start();
    $this->router->run('/fluent', 'GET');
    $output = ob_get_clean();

    expect($output)->toBe('chained');
});