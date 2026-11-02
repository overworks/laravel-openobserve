# Changelog

All notable changes to `laravel-openobserve` will be documented in this file.

## [Unreleased]

### Breaking changes for 0.2.0
- Require PHP 8.4.1+ and Laravel 13; drop PHP 8.3 and Laravel 11/12 support.
- Require a confirmed ingestion result for `openobserve:test` to succeed. Responses without valid per-stream counts now return exit code 1, even when HTTP succeeds.

### Added
- PHP 8.4/8.5 CI coverage and a PHP 8.4.1 lowest-dependency job.
- Pint and Larastan/PHPStan level 6 checks, with `lint`, `format`, `analyse`, and `check` Composer scripts.
- Regression tests for ingestion responses, missing configuration, authentication and transport errors, and Laravel logging requests.

### Changed
- Upgrade to Testbench 11 and Pest 5 and resolve stable dependencies by default.
- Declare the directly used `illuminate/console` dependency.
- Use fixed test configuration and mock HTTP clients independently of local OpenObserve environment variables.

### Fixed
- Discover PHPUnit configuration automatically so `composer test` works without copying an XML file.
- Validate connection settings before resolving the client or sending a test record.
- Detect rejected records in HTTP 200 responses and report errors without printing exception details or raw response bodies.

## [0.1.0]

- Initial release.
