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

- PHP 8.4.1 이상
- Laravel 13.x

### 0.1에서 업그레이드

예정된 0.2 릴리스부터 PHP 8.3과 Laravel 11/12 지원을 종료합니다. 패키지를 업데이트하기 전에 PHP와 Laravel을 업그레이드하세요. 기존 설정, Facade 사용법, 컨테이너 바인딩, 로그 채널 설정 방식은 유지됩니다.

`openobserve:test` 명령은 테스트 로그의 실제 수집 성공을 확인해야 통과합니다. HTTP 200 응답만으로는 성공 처리하지 않습니다. 자세한 기준은 [연결 테스트](#연결-테스트)를 참고하세요.

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
OPENOBSERVE_URL=http://localhost:5080
OPENOBSERVE_ORGANIZATION=default
OPENOBSERVE_STREAM=laravel-logs
OPENOBSERVE_EMAIL=your-email@example.com
OPENOBSERVE_PASSWORD=your-password
```

### 설정 옵션

| 옵션 | 환경변수 | 기본값 |
|------|---------|--------|
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

설정된 stream에 테스트 로그 한 건을 전송합니다. 응답의 `code`가 200이고, 대상 stream의 결과가 정확히 하나이며 정수형 건수가 `successful >= 1`, `failed: 0`일 때만 종료 코드 `0`을 반환합니다. [OpenObserve JSON 수집 응답 명세](https://openobserve.ai/docs/reference/api/ingestion/logs/json/#response)를 기준으로 검사합니다.

필수 연결 설정 누락, 인증·전송 오류, 수집 실패, 결과 누락 또는 알 수 없는 응답 형식은 진단 메시지와 함께 종료 코드 `1`을 반환합니다. stream별 수집 건수가 없는 구버전·커스텀 응답도 수집 성공을 확인할 수 없으므로 실패 처리합니다. 예외 세부 정보와 원문 응답은 출력하지 않습니다.

## 테스트

개발 의존성을 설치한 뒤 테스트를 실행합니다. 표준 PHPUnit 설정을 자동으로 읽으므로 설정 파일을 복사할 필요가 없습니다. 고정된 테스트 설정과 모의 HTTP 클라이언트를 사용하며, 실제 OpenObserve 서버나 인증정보는 필요하지 않습니다.

```bash
composer install
composer test
```

코드 품질 검사 명령:

```bash
composer lint       # Pint 스타일 검사
composer format     # 스타일 자동 수정
composer analyse    # Larastan / PHPStan level 6으로 src 분석
composer check      # Composer 검증, 스타일 검사, 정적 분석, 테스트
```

CI에서는 최신 PHP 8.4·8.5 패치와 Laravel 13 조합을 테스트하고, PHP 8.4.1에서는 설치 가능한 최소 안정 버전 의존성 조합도 검증합니다.

## 보안 취약점

보안 취약점을 발견한 경우 urlinee@gmail.com으로 이메일을 보내주세요.

## 라이선스

MIT 라이선스. 자세한 내용은 [License File](LICENSE)을 참조하세요.

## 크레딧

- [Minhyung Park](https://github.com/overworks)
- [All Contributors](../../contributors)
