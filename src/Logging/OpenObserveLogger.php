<?php

namespace Minhyung\LaravelOpenObserve\Logging;

use Minhyung\OpenObserve\Client;
use Minhyung\OpenObserve\Monolog\Handler as OpenObserveMonologHandler;
use Monolog\Handler\NullHandler;
use Monolog\Level;
use Monolog\Logger;

class OpenObserveLogger
{
    public function __invoke(array $config): Logger
    {
        $openObserveConfig = config('openobserve');
        $logger = new Logger($config['name'] ?? 'openobserve');

        if (!($openObserveConfig['enabled'] ?? false)) {
            $logger->pushHandler(new NullHandler());

            return $logger;
        }

        $logger->pushHandler(new OpenObserveMonologHandler(
            client: app(Client::class),
            stream: $openObserveConfig['stream'] ?? 'default',
            level: $config['level'] ?? Level::Debug,
        ));

        return $logger;
    }
}
