<?php

namespace Minhyung\LaravelOpenObserve\Facades;

use Illuminate\Support\Facades\Facade;
use Minhyung\OpenObserve\Client;

/**
 * @see Client
 */
class OpenObserve extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
