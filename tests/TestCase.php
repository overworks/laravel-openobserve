<?php

namespace Minhyung\LaravelOpenObserve\Tests;

use Http\Mock\Client as MockHttpClient;
use Minhyung\LaravelOpenObserve\Facades\OpenObserve;
use Minhyung\LaravelOpenObserve\OpenObserveServiceProvider;
use Minhyung\OpenObserve\Client;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            OpenObserveServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'OpenObserve' => OpenObserve::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('openobserve', [
            'url' => 'http://localhost:5080',
            'organization' => 'default',
            'stream' => 'default',
            'auth' => [
                'email' => 'test@example.com',
                'password' => 'test-password',
            ],
            'timeout' => 5,
            'ssl_verify' => true,
        ]);
    }

    protected function mockOpenObserveClient(): MockHttpClient
    {
        $http = new MockHttpClient;

        $this->app->singleton(Client::class, fn () => new Client(
            baseUrl: config('openobserve.url'),
            email: config('openobserve.auth.email'),
            password: config('openobserve.auth.password'),
            organization: config('openobserve.organization'),
            httpClient: $http,
        ));

        return $http;
    }
}
