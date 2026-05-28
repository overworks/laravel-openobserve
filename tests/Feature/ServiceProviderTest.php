<?php

use Minhyung\LaravelOpenObserve\Facades\OpenObserve;
use Minhyung\OpenObserve\Client;

test('service provider registers client as singleton', function () {
    $client = app(Client::class);

    expect($client)->toBeInstanceOf(Client::class)
        ->and(app(Client::class))->toBe($client);
});

test('client is accessible via alias', function () {
    expect(app('openobserve'))->toBeInstanceOf(Client::class);
});

test('facade resolves to client', function () {
    expect(OpenObserve::getFacadeRoot())->toBeInstanceOf(Client::class);
});

test('config has all required keys', function () {
    $config = config('openobserve');

    expect($config)->toBeArray()
        ->toHaveKeys(['url', 'organization', 'stream', 'auth', 'timeout', 'ssl_verify'])
        ->and($config['auth'])->toHaveKeys(['email', 'password']);
});
