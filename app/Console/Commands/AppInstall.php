<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring the database up to date when the application is deployed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running database migrations...');

        $this->call('migrate', [
            '--force' => true,
            '--step' => true,
        ]);

        $this->info('Installation complete!');

        return self::SUCCESS;
    }
}
