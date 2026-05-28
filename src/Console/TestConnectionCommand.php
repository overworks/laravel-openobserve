<?php

namespace Minhyung\LaravelOpenObserve\Console;

use Illuminate\Console\Command;
use Minhyung\OpenObserve\Client;

class TestConnectionCommand extends Command
{
    protected $signature = 'openobserve:test';

    protected $description = 'Test the connection to OpenObserve';

    public function handle(Client $client): int
    {
        $this->info('Testing connection to OpenObserve...');

        $config = config('openobserve');

        $this->line('Configuration:');
        $this->line('  URL: '.$config['url']);
        $this->line('  Organization: '.$config['organization']);
        $this->line('  Stream: '.$config['stream']);
        $this->line('  Email: '.$config['auth']['email']);
        $this->newLine();

        try {
            $client->logs()->json($config['stream'], [[
                'level' => 'info',
                'message' => 'OpenObserve connection test',
                '_timestamp' => (int) (microtime(true) * 1_000_000),
            ]]);

            $this->info('Connection successful!');
            $this->info('A test log has been sent to OpenObserve.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Connection failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
