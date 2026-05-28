<?php

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockHttpClient;
use Minhyung\OpenObserve\Client;

function bindMockClient(MockHttpClient $mock): void
{
    app()->singleton(Client::class, fn () => new Client(
        baseUrl: 'http://localhost:5080',
        email: 'test@example.com',
        password: 'password',
        organization: 'default',
        httpClient: $mock,
    ));
}

test('command displays configuration', function () {
    $mock = new MockHttpClient();
    $mock->addResponse(new Response(200, [], '{}'));
    bindMockClient($mock);

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('http://localhost:5080')
        ->expectsOutputToContain('default')
        ->assertSuccessful();
});

test('command returns success on successful connection', function () {
    $mock = new MockHttpClient();
    $mock->addResponse(new Response(200, [], '{}'));
    bindMockClient($mock);

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('Connection successful')
        ->assertSuccessful();
});

test('command returns failure on failed connection', function () {
    $mock = new MockHttpClient();
    $mock->addResponse(new Response(500, [], 'server error'));
    bindMockClient($mock);

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('Connection failed')
        ->assertFailed();
});
