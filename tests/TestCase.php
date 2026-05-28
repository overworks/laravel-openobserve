<?php

namespace Minhyung\LaravelOpenObserve\Tests;

use Minhyung\LaravelOpenObserve\Facades\OpenObserve;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Minhyung\LaravelOpenObserve\OpenObserveServiceProvider;

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

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('openobserve.url', env('OPENOBSERVE_URL', 'http://localhost:5080'));
        $app['config']->set('openobserve.organization', env('OPENOBSERVE_ORGANIZATION', 'default'));
        $app['config']->set('openobserve.stream', env('OPENOBSERVE_STREAM', 'default'));
        $app['config']->set('openobserve.auth.email', env('OPENOBSERVE_EMAIL', 'test@example.com'));
        $app['config']->set('openobserve.auth.password', env('OPENOBSERVE_PASSWORD', 'password'));
    }
}
