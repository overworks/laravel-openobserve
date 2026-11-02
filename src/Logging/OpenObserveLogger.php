<?php

namespace Minhyung\LaravelOpenObserve\Logging;

use Minhyung\OpenObserve\Client;
use Minhyung\OpenObserve\Monolog\Handler as OpenObserveMonologHandler;
use Monolog\Level;
use Monolog\Logger;

class OpenObserveLogger
{
    /**
     * @param  array{name?: string, level?: int|string|Level}  $config
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger($config['name'] ?? 'openobserve');

        $logger->pushHandler(new OpenObserveMonologHandler(
            client: app(Client::class),
            stream: config('openobserve.stream', 'default'),
            level: $config['level'] ?? Level::Debug,
        ));

        return $logger;
    }
}
