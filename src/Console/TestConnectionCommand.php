<?php

namespace Minhyung\LaravelOpenObserve\Console;

use Illuminate\Console\Command;
use Minhyung\OpenObserve\Client;
use Minhyung\OpenObserve\Exception\ApiException;
use Minhyung\OpenObserve\Exception\AuthenticationException;
use Minhyung\OpenObserve\Exception\TransportException;
use Throwable;

class TestConnectionCommand extends Command
{
    protected $signature = 'openobserve:test';

    protected $description = 'Test the connection to OpenObserve';

    public function handle(): int
    {
        $this->info('Testing connection to OpenObserve...');

        $config = [];

        foreach (['url', 'organization', 'stream', 'auth.email', 'auth.password'] as $key) {
            $value = config('openobserve.'.$key);

            if (! is_string($value) || trim($value) === '') {
                $this->error('Connection failed: Missing or invalid configuration: openobserve.'.$key);

                return self::FAILURE;
            }

            $config[$key] = $value;
        }

        $this->line('Configuration:');
        $this->line('  URL: '.$config['url']);
        $this->line('  Organization: '.$config['organization']);
        $this->line('  Stream: '.$config['stream']);
        $this->line('  Email: '.$config['auth.email']);
        $this->newLine();

        try {
            $response = app(Client::class)->logs()->json($config['stream'], [[
                'level' => 'info',
                'message' => 'OpenObserve connection test',
                '_timestamp' => (int) (microtime(true) * 1_000_000),
            ]]);
        } catch (Throwable $e) {
            $reason = match (true) {
                $e instanceof AuthenticationException => 'Authentication failed. Check the configured credentials.',
                $e instanceof TransportException => 'HTTP transport failed. Check connectivity, timeout and TLS settings.',
                $e instanceof ApiException => 'The API returned an error or invalid JSON.',
                default => 'Unable to initialize or use the OpenObserve client.',
            };
            $this->error('Connection failed: '.$reason);

            return self::FAILURE;
        }

        if (($error = $this->ingestionError($response, $config['stream'])) !== null) {
            $this->error('Connection failed: '.$error);

            return self::FAILURE;
        }

        $this->info('Connection successful!');
        $this->info('A test log has been ingested by OpenObserve.');

        return self::SUCCESS;
    }

    /**
     * @param  array<array-key, mixed>  $response
     */
    private function ingestionError(array $response, string $stream): ?string
    {
        $unconfirmed = 'The API response did not confirm log ingestion for the configured stream.';
        $statuses = $response['status'] ?? null;

        if (($response['code'] ?? null) !== 200 || ! is_array($statuses) || ! array_is_list($statuses)) {
            return $unconfirmed;
        }

        $matches = array_values(array_filter(
            $statuses,
            fn (mixed $status): bool => is_array($status) && ($status['name'] ?? null) === $stream,
        ));

        if (count($matches) !== 1) {
            return $unconfirmed;
        }

        $successful = $matches[0]['successful'] ?? null;
        $failed = $matches[0]['failed'] ?? null;

        if (! is_int($successful) || ! is_int($failed) || $successful < 0 || $failed < 0) {
            return $unconfirmed;
        }

        if ($successful < 1 || $failed > 0) {
            return "Log ingestion failed (successful: {$successful}, failed: {$failed}).";
        }

        return null;
    }
}
