<?php

namespace App\Console\Commands;

use App\Services\UserPresenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class CleanupInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-inactive-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up inactive users from the presence cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $presenceService = App::make(UserPresenceService::class);
        $count = $presenceService->cleanupInactiveUsers();

        $this->info("Cleaned up inactive users. Currently active users: {$count}");

        return Command::SUCCESS;
    }
}
