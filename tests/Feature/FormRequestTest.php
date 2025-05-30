<?php

use Tests\Stubs\TestRequest;

test('form request validates correctly with valid data', function () {
    $request = new TestRequest();

    $result = $request->validate(['name' => 'Ricsi']);

    expect($result)->toBeTrue();
    expect($request->errors())->toBeEmpty();
});

test('form request fails with invalid data', function () {
    $request = new TestRequest();

    $result = $request->validate(['name' => 123]);

    expect($result)->toBeFalse();
    expect($request->errors())->toHaveKey('name');
    expect($request->errors()['name'][0])->toBeString();
});

test('form request fails authorization', function () {
    $request = new class extends TestRequest {
        public function authorize(): bool {
            return false;
        }
    };

    $result = $request->validate(['name' => 'Ricsi']);

    expect($result)->toBeFalse();
    expect($request->errors())->toHaveKey('authorization');
});
