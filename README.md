# Laravel OpenObserve

[![Tests](https://github.com/overworks/laravel-openobserve/actions/workflows/tests.yml/badge.svg?branch=0.x)](https://github.com/overworks/laravel-openobserve/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/minhyung/laravel-openobserve.svg?style=flat-square)](https://packagist.org/packages/minhyung/laravel-openobserve)
[![Total Downloads](https://img.shields.io/packagist/dt/minhyung/laravel-openobserve.svg?style=flat-square)](https://packagist.org/packages/minhyung/laravel-openobserve)

Laravel adapter for [minhyung/openobserve](https://github.com/overworks/php-openobserve), the PHP client for [OpenObserve](https://openobserve.ai).

This package provides:

- A configured `Minhyung\OpenObserve\Client` instance bound in the container
- A custom Laravel log channel driver that ships logs through OpenObserve's Monolog handler
- An `openobserve:test` Artisan command for connection checks

**[한국어 문서](README.ko.md)**

## Requirements

- PHP 8.4.1+
- Laravel 13.x

### Upgrading from 0.1

The upcoming 0.2 release drops support for PHP 8.3 and Laravel 11/12. Upgrade PHP and Laravel before updating this package. Existing configuration, Facade access, container bindings, and logging channel setup remain compatible.

The `openobserve:test` command now requires confirmation that its test record was ingested. An HTTP 200 response alone is no longer sufficient; see [Connection Test](#connection-test).

## Installation

```bash
composer require minhyung/laravel-openobserve
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=openobserve-config
```

## Configuration

Add OpenObserve connection details to your `.env` file:

```env
OPENOBSERVE_URL=http://localhost:5080
OPENOBSERVE_ORGANIZATION=default
OPENOBSERVE_STREAM=laravel-logs
OPENOBSERVE_EMAIL=your-email@example.com
OPENOBSERVE_PASSWORD=your-password
```

### Configuration Options

| Option | Env Variable | Default |
|--------|-------------|---------|
| `url` | `OPENOBSERVE_URL` | `http://localhost:5080` |
| `organization` | `OPENOBSERVE_ORGANIZATION` | `default` |
| `stream` | `OPENOBSERVE_STREAM` | `default` |
| `auth.email` | `OPENOBSERVE_EMAIL` | - |
| `auth.password` | `OPENOBSERVE_PASSWORD` | - |
| `timeout` | `OPENOBSERVE_TIMEOUT` | `5` |
| `ssl_verify` | `OPENOBSERVE_SSL_VERIFY` | `true` |

`timeout` and `ssl_verify` are applied to the Guzzle HTTP client that the service provider builds and injects into the OpenObserve client.

### Laravel Logging Channel Setup

Add the OpenObserve channel to your `config/logging.php`:

```php
'channels' => [
    // ... existing channels

    'openobserve' => [
        'driver' => 'custom',
        'via' => \Minhyung\LaravelOpenObserve\Logging\OpenObserveLogger::class,
        'level' => env('LOG_LEVEL', 'debug'),
        'name' => 'openobserve',
    ],

    // Optionally include openobserve in a stack channel
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'openobserve'],
        'ignore_exceptions' => false,
    ],
],
```

Set the default log channel in your `.env` file:

```env
LOG_CHANNEL=stack  # or 'openobserve'
```

## Usage

### Laravel Logging

Use it just like standard Laravel logging:

```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => 123]);
Log::error('An error occurred', ['error' => $exception->getMessage()]);
```

### Direct Client Access

The container resolves a configured `Minhyung\OpenObserve\Client`. Use the Facade or dependency injection to access it.

```php
use Minhyung\LaravelOpenObserve\Facades\OpenObserve;

OpenObserve::logs()->json('laravel-logs', [
    ['level' => 'info', 'message' => 'User action', 'user_id' => 123],
]);
```

```php
use Minhyung\OpenObserve\Client;

class SomeController extends Controller
{
    public function __construct(private Client $openObserve)
    {
    }

    public function index()
    {
        $this->openObserve->logs()->json('laravel-logs', [
            ['level' => 'info', 'message' => 'Controller executed'],
        ]);
    }
}
```

See [minhyung/openobserve](https://github.com/overworks/php-openobserve) for the full client API (search, streams, alerts, dashboards, OTLP, etc.).

### Connection Test

```bash
php artisan openobserve:test
```

This command sends one test log to the configured stream. It exits with code `0` only when the response has `code: 200` and exactly one result for that stream with `successful >= 1` and `failed: 0`, using integer counts. This follows the [OpenObserve JSON ingestion response](https://openobserve.ai/docs/reference/api/ingestion/logs/json/#response).

Missing connection settings, authentication or transport errors, rejected records, and missing or unrecognized ingestion results return exit code `1` with a diagnostic message. Legacy or custom response formats without per-stream counts cannot confirm ingestion and also fail. Exception details and raw response bodies are not printed.

## Testing

Install development dependencies, then run the tests. The standard PHPUnit configuration is discovered automatically; no configuration file needs to be copied. Tests use fixed configuration and mock HTTP clients, so no OpenObserve server or credentials are required.

```bash
composer install
composer test
```

Code quality commands:

```bash
composer lint       # Check formatting with Pint
composer format     # Apply formatting
composer analyse    # Analyse src with Larastan / PHPStan level 6
composer check      # Validate Composer metadata, lint, analyse, and test
```

CI tests Laravel 13 on the latest PHP 8.4 and 8.5 patches, plus PHP 8.4.1 with the lowest installable stable dependencies.

## Security Vulnerabilities

If you discover a security vulnerability, please email urlinee@gmail.com.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Minhyung Park](https://github.com/overworks)
- [All Contributors](../../contributors)
