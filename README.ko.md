# Laravel OpenObserve

[![Tests](https://github.com/overworks/laravel-openobserve/actions/workflows/tests.yml/badge.svg?branch=0.x)](https://github.com/overworks/laravel-openobserve/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/minhyung/laravel-openobserve.svg?style=flat-square)](https://packagist.org/packages/minhyung/laravel-openobserve)
[![Total Downloads](https://img.shields.io/packagist/dt/minhyung/laravel-openobserve.svg?style=flat-square)](https://packagist.org/packages/minhyung/laravel-openobserve)

[OpenObserve](https://openobserve.ai)의 PHP 클라이언트인 [minhyung/openobserve](https://github.com/overworks/php-openobserve)를 Laravel에서 사용하기 위한 어댑터 패키지입니다.

이 패키지는 다음을 제공합니다.

- 컨테이너에 바인딩된 설정 완료 상태의 `Minhyung\OpenObserve\Client`
- OpenObserve Monolog 핸들러를 사용하는 커스텀 Laravel 로그 채널 드라이버
- 연결 확인용 `openobserve:test` Artisan 커맨드

## 요구사항

- PHP 8.3 이상
- Laravel 11.x 또는 12.x

## 설치

```bash
composer require minhyung/laravel-openobserve
```

설정 파일을 퍼블리시합니다:

```bash
php artisan vendor:publish --tag=openobserve-config
```

## 설정

`.env`에 OpenObserve 연결 정보를 추가합니다:

```env
OPENOBSERVE_ENABLED=true
OPENOBSERVE_URL=http://localhost:5080
OPENOBSERVE_ORGANIZATION=default
OPENOBSERVE_STREAM=laravel-logs
OPENOBSERVE_EMAIL=your-email@example.com
OPENOBSERVE_PASSWORD=your-password
```

### 설정 옵션

| 옵션 | 환경변수 | 기본값 |
|------|---------|--------|
| `enabled` | `OPENOBSERVE_ENABLED` | `false` |
| `url` | `OPENOBSERVE_URL` | `http://localhost:5080` |
| `organization` | `OPENOBSERVE_ORGANIZATION` | `default` |
| `stream` | `OPENOBSERVE_STREAM` | `default` |
| `auth.email` | `OPENOBSERVE_EMAIL` | - |
| `auth.password` | `OPENOBSERVE_PASSWORD` | - |
| `timeout` | `OPENOBSERVE_TIMEOUT` | `5` |
| `ssl_verify` | `OPENOBSERVE_SSL_VERIFY` | `true` |

`timeout`과 `ssl_verify`는 서비스 프로바이더가 빌드해서 OpenObserve 클라이언트에 주입하는 Guzzle HTTP 클라이언트에 적용됩니다.

### Laravel 로깅 채널 설정

`config/logging.php`에 채널을 추가합니다:

```php
'channels' => [
    // ... 기존 채널들

    'openobserve' => [
        'driver' => 'custom',
        'via' => \Minhyung\LaravelOpenObserve\Logging\OpenObserveLogger::class,
        'level' => env('LOG_LEVEL', 'debug'),
        'name' => 'openobserve',
    ],

    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'openobserve'],
        'ignore_exceptions' => false,
    ],
],
```

`OPENOBSERVE_ENABLED=false`일 때는 채널이 `NullHandler`로 동작하므로, 비프로덕션 환경에서도 안전하게 연결해 둘 수 있습니다.

`.env`에서 기본 로그 채널을 지정합니다:

```env
LOG_CHANNEL=stack  # 또는 'openobserve'
```

## 사용법

### Laravel 로깅

```php
use Illuminate\Support\Facades\Log;

Log::info('사용자 로그인', ['user_id' => 123]);
Log::error('오류 발생', ['error' => $exception->getMessage()]);
```

### 클라이언트 직접 사용

컨테이너는 설정이 적용된 `Minhyung\OpenObserve\Client`를 해석합니다. Facade나 의존성 주입을 통해 접근할 수 있습니다.

```php
use Minhyung\LaravelOpenObserve\Facades\OpenObserve;

OpenObserve::logs()->json('laravel-logs', [
    ['level' => 'info', 'message' => '사용자 액션', 'user_id' => 123],
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
            ['level' => 'info', 'message' => '컨트롤러 실행'],
        ]);
    }
}
```

전체 클라이언트 API(search, streams, alerts, dashboards, OTLP 등)는 [minhyung/openobserve](https://github.com/overworks/php-openobserve)를 참고하세요.

### 연결 테스트

```bash
php artisan openobserve:test
```

## 테스트

```bash
composer test
```

## 보안 취약점

보안 취약점을 발견한 경우 urlinee@gmail.com으로 이메일을 보내주세요.

## 라이선스

MIT 라이선스. 자세한 내용은 [License File](LICENSE)을 참조하세요.

## 크레딧

- [Minhyung Park](https://github.com/overworks)
- [All Contributors](../../contributors)
