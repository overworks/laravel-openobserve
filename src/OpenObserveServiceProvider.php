<?php

namespace Minhyung\LaravelOpenObserve;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;
use Minhyung\LaravelOpenObserve\Console\TestConnectionCommand;
use Minhyung\OpenObserve\Client;

class OpenObserveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/openobserve.php' => config_path('openobserve.php'),
        ], 'openobserve-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/openobserve.php',
            'openobserve'
        );

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['openobserve'];

            $guzzle = new GuzzleClient([
                'timeout' => $config['timeout'] ?? 5,
                'verify' => $config['ssl_verify'] ?? true,
            ]);

            return new Client(
                baseUrl: $config['url'] ?? 'http://localhost:5080',
                email: $config['auth']['email'] ?? '',
                password: $config['auth']['password'] ?? '',
                organization: $config['organization'] ?? 'default',
                httpClient: $guzzle,
            );
        });

        $this->app->alias(Client::class, 'openobserve');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestConnectionCommand::class,
            ]);
        }
    }
}
