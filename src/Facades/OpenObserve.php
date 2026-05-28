<?php

namespace Minhyung\LaravelOpenObserve\Facades;

use Illuminate\Support\Facades\Facade;
use Minhyung\OpenObserve\Client;

/**
 * @see \Minhyung\OpenObserve\Client
 */
class OpenObserve extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
