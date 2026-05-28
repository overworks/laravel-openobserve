<?php

use Minhyung\LaravelOpenObserve\Logging\OpenObserveLogger;
use Minhyung\OpenObserve\Monolog\Handler as OpenObserveMonologHandler;
use Monolog\Logger;

test('logger factory creates monolog instance', function () {
    $factory = new OpenObserveLogger();
    $logger = $factory([]);

    expect($logger)->toBeInstanceOf(Logger::class)
        ->and($logger->getName())->toBe('openobserve');
});

test('logger factory uses custom channel name', function () {
    $factory = new OpenObserveLogger();
    $logger = $factory(['name' => 'custom-channel']);

    expect($logger->getName())->toBe('custom-channel');
});

test('logger factory pushes openobserve handler', function () {
    $factory = new OpenObserveLogger();
    $logger = $factory([]);

    expect($logger->getHandlers())->toHaveCount(1)
        ->and($logger->getHandlers()[0])->toBeInstanceOf(OpenObserveMonologHandler::class);
});
