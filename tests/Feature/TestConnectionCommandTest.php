<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\NetworkException;
use Minhyung\OpenObserve\Client;

test('command displays configuration', function () {
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response(200, [], '{"code":200,"status":[{"name":"default","successful":1,"failed":0}]}'));

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('http://localhost:5080')
        ->expectsOutputToContain('default')
        ->doesntExpectOutputToContain('test-password')
        ->assertSuccessful();
});

test('command verifies ingestion of a test record into the configured stream', function () {
    config(['openobserve.stream' => 'app-logs']);
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response(200, [], '{"code":200,"status":[{"name":"app-logs","successful":1,"failed":0}]}'));

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('Connection successful')
        ->assertSuccessful();

    expect($http->getRequests())->toHaveCount(1);
    $request = $http->getLastRequest();
    $records = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->getPath())->toBe('/api/default/app-logs/_json')
        ->and($records)->toHaveCount(1)
        ->and($records[0]['message'])->toBe('OpenObserve connection test')
        ->and($records[0]['level'])->toBe('info')
        ->and($records[0]['_timestamp'])->toBeInt()->toBeGreaterThan(0);
});

test('command rejects unsuccessful ingestion even when HTTP succeeds', function (int $successful, int $failed) {
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response(200, [], json_encode([
        'code' => 200,
        'status' => [['name' => 'default', 'successful' => $successful, 'failed' => $failed]],
    ], JSON_THROW_ON_ERROR)));

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('Log ingestion failed')
        ->doesntExpectOutputToContain('Connection successful')
        ->assertFailed();
})->with([
    'rejected record' => [0, 1],
    'partial failure' => [1, 1],
    'no ingested records' => [0, 0],
]);

test('command rejects responses that do not confirm ingestion', function (string $body) {
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response(200, [], $body));

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('did not confirm log ingestion')
        ->doesntExpectOutputToContain('Connection successful')
        ->assertFailed();
})->with([
    'empty object' => '{}',
    'legacy success string' => '{"code":200,"status":"ok"}',
    'missing response code' => '{"status":[{"name":"default","successful":1,"failed":0}]}',
    'error response code' => '{"code":500,"status":[{"name":"default","successful":1,"failed":0}]}',
    'empty status list' => '{"code":200,"status":[]}',
    'malformed status entry' => '{"code":200,"status":[null]}',
    'wrong stream' => '{"code":200,"status":[{"name":"another-stream","successful":1,"failed":0}]}',
    'missing counts' => '{"code":200,"status":[{"name":"default"}]}',
    'string counts' => '{"code":200,"status":[{"name":"default","successful":"1","failed":"0"}]}',
    'negative count' => '{"code":200,"status":[{"name":"default","successful":1,"failed":-1}]}',
    'ambiguous stream results' => '{"code":200,"status":[{"name":"default","successful":1,"failed":0},{"name":"default","successful":0,"failed":1}]}',
]);

test('command rejects missing configuration before resolving the client', function (string $key, mixed $value) {
    config(["openobserve.{$key}" => $value]);
    app()->singleton(Client::class, function () {
        $this->fail('The client must not be resolved with missing configuration.');
    });

    $this->artisan('openobserve:test')
        ->expectsOutputToContain("Missing or invalid configuration: openobserve.{$key}")
        ->assertFailed();
})->with(['url', 'organization', 'stream', 'auth.email', 'auth.password'])
    ->with([null, '', '   ']);

test('command reports API errors without exposing the response body', function (int $status, string $reason) {
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response($status, [], 'sensitive-response test-password'));

    $this->artisan('openobserve:test')
        ->expectsOutputToContain($reason)
        ->doesntExpectOutputToContain('sensitive-response')
        ->doesntExpectOutputToContain('test-password')
        ->assertFailed();
})->with([
    'unauthorized' => [401, 'Authentication failed'],
    'forbidden' => [403, 'Authentication failed'],
    'server failure' => [500, 'API returned an error or invalid JSON'],
    'invalid JSON' => [200, 'API returned an error or invalid JSON'],
]);

test('command reports transport errors without exposing exception details', function () {
    $http = $this->mockOpenObserveClient();
    $http->addException(new NetworkException('sensitive-response test-password', new Request('POST', 'http://localhost:5080')));

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('HTTP transport failed')
        ->doesntExpectOutputToContain('sensitive-response')
        ->doesntExpectOutputToContain('test-password')
        ->assertFailed();
});

test('command reports client initialization errors without exposing exception details', function () {
    app()->singleton(Client::class, function () {
        throw new RuntimeException('sensitive-response test-password');
    });

    $this->artisan('openobserve:test')
        ->expectsOutputToContain('Unable to initialize or use the OpenObserve client')
        ->doesntExpectOutputToContain('sensitive-response')
        ->doesntExpectOutputToContain('test-password')
        ->assertFailed();
});
