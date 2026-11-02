<?php

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Minhyung\LaravelOpenObserve\Logging\OpenObserveLogger;
use Minhyung\OpenObserve\Exception\ApiException;

beforeEach(function () {
    config([
        'openobserve.organization' => 'test-org',
        'openobserve.stream' => 'app-logs',
        'logging.channels.openobserve' => [
            'driver' => 'custom',
            'via' => OpenObserveLogger::class,
            'name' => 'application',
            'level' => 'info',
        ],
    ]);
});

test('Laravel logging sends the message and context to the configured destination', function () {
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response(200, [], '{"code":200,"status":[{"name":"app-logs","successful":1,"failed":0}]}'));

    Log::shareContext(['request_id' => 'request-123']);
    Log::channel('openobserve')->info('User logged in', ['user_id' => 123]);

    expect($http->getRequests())->toHaveCount(1);
    $request = $http->getLastRequest();
    $records = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('http://localhost:5080/api/test-org/app-logs/_json')
        ->and($request->getHeaderLine('Authorization'))->toBe('Basic '.base64_encode('test@example.com:test-password'))
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($records)->toHaveCount(1)
        ->and($records[0]['message'])->toBe('User logged in')
        ->and($records[0]['level'])->toBe('INFO')
        ->and($records[0]['channel'])->toBe('application')
        ->and($records[0]['context'])->toBe(['request_id' => 'request-123', 'user_id' => 123])
        ->and($records[0]['_timestamp'])->toBeInt()->toBeGreaterThan(0);
});

test('Laravel logging filters records below the channel level without sending a request', function () {
    $http = $this->mockOpenObserveClient();

    Log::channel('openobserve')->debug('Filtered message');

    expect($http->getRequests())->toBeEmpty();
});

test('Laravel logging continues to propagate ingestion API exceptions', function () {
    $http = $this->mockOpenObserveClient();
    $http->addResponse(new Response(503, [], 'unavailable'));

    expect(fn () => Log::channel('openobserve')->error('Test failure'))
        ->toThrow(ApiException::class);
});
