<?php

declare(strict_types=1);

use Radiant\Session\Session;

beforeEach(function () {
    Session::init();
    $_SESSION = []; // tisztítás minden teszt előtt
});

afterEach(function () {
    Session::destroy(); // biztos, ami biztos
});

test('it can start a session and retrieve the ID', function () {
    Session::init();
    expect(session_status())->toBe(PHP_SESSION_ACTIVE)
        ->and(Session::sessid())->not->toBeEmpty();
});

test('it can set and get a session variable', function () {
    Session::set('username', 'ricsi');
    expect(Session::get('username'))->toBe('ricsi');
});

test('it returns default value for nonexistent key', function () {
    expect(Session::get('missing', 'default'))->toBe('default');
});

test('it can store and retrieve expiring session values', function () {
    Session::withExpire('token', 'abc123', 2);
    expect(Session::get('token'))->toBe('abc123');

    sleep(3);

    expect(Session::get('token', 'expired'))->toBe('expired');
});

test('it throws exception for invalid expiration time', function () {
    expect(fn () => Session::withExpire('fail', 'nope', 0))
        ->toThrow(InvalidArgumentException::class);
});

test('it can delete a session variable', function () {
    Session::set('deleteMe', 'bye');
    Session::delete('deleteMe');

    expect(Session::get('deleteMe', null))->toBeNull();
});

test('it can delete multiple session variables', function () {
    Session::set('a', 1);
    Session::set('b', 2);
    Session::delete(['a', 'b']);

    expect(Session::count())->toBe(0);
});

test('it can retrieve all session values', function () {
    Session::set('x', 'one');
    Session::set('y', 'two');

    expect(Session::all())->toMatchArray([
        'x' => 'one',
        'y' => 'two',
    ]);
});

test('it can destroy the session', function () {
    Session::set('temp', 'gone');
    Session::destroy();

    expect(Session::sessid())->toBe('');
});

test('it can count session items', function () {
    Session::set('one', 1);
    Session::set('two', 2);

    expect(Session::count())->toBe(2);
});

test('it can flash and retrieve a flash message', function () {
    Session::flash('Saved!', 'success');
    expect(Session::flashed('success'))->toBe('Saved!');
});

test('flashed messages are deleted after retrieval', function () {
    Session::flash('Oops!', 'error');
    Session::flashed('error'); // retrieve once

    expect(Session::flashed('error', 'none'))->toBe('none');
});

test('it can retrieve and clear all flash messages', function () {
    Session::flash('done', 'notice');
    Session::flash('fail', 'error');

    expect(Session::flashed())->toMatchArray([
        'notice' => 'done',
        'error' => 'fail',
    ]);

    expect(Session::flashed('notice', 'missing'))->toBe('missing');
});
