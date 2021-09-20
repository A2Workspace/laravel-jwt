<?php

namespace A2Workspace\LaravelJwt\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-jwt:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '安裝設置 laravel-jwt';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('安裝 jwt-auth ...');

        $this->call('vendor:publish', [
            '--provider' => 'Tymon\JWTAuth\Providers\LaravelServiceProvider',
        ]);

        $this->call('jwt:secret');
    }
}
