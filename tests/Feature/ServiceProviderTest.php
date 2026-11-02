<?php

use Minhyung\LaravelOpenObserve\Facades\OpenObserve;
use Minhyung\LaravelOpenObserve\OpenObserveServiceProvider;
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

test('service provider merges defaults with application configuration', function () {
    config(['openobserve' => ['stream' => 'custom-stream']]);

    (new OpenObserveServiceProvider($this->app))->register();

    $config = config('openobserve');

    expect($config)->toBeArray()
        ->toHaveKeys(['url', 'organization', 'stream', 'auth', 'timeout', 'ssl_verify'])
        ->and($config['stream'])->toBe('custom-stream')
        ->and($config['auth'])->toHaveKeys(['email', 'password']);
});
