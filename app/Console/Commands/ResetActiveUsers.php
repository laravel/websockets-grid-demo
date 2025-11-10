<?php

namespace App\Console\Commands;

use App\Events\UserCountUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetActiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:reset-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the active users count (useful after deployments)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Cache::forget('active_users');

        broadcast(new UserCountUpdated(0));

        $this->info('Active users have been reset to 0.');

        return Command::SUCCESS;
    }
}
