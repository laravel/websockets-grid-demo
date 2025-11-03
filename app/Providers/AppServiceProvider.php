<?php

namespace App\Providers;

use App\Services\UserPresenceService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserPresenceService::class, function ($app) {
            return new UserPresenceService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Schedule cleanup of inactive users every minute
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->call(function () {
                $presenceService = app(UserPresenceService::class);
                $presenceService->cleanupInactiveUsers();
            })->everyMinute();
        });
    }
}
