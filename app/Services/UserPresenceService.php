<?php

namespace App\Services;

use App\Events\UserCountUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class UserPresenceService
{
    private const USER_PRESENCE_CACHE_KEY = 'active_users';

    private const USER_PRESENCE_TIMEOUT = 300; // 5 minutes

    /**
     * Register a user as active
     */
    public function registerUser(): void
    {
        $userId = $this->getUserId();
        $users = Cache::get(self::USER_PRESENCE_CACHE_KEY, []);

        // Update timestamp for this user
        $users[$userId] = now()->timestamp;
        Cache::forever(self::USER_PRESENCE_CACHE_KEY, $users);

        // Broadcast updated count
        $this->broadcastUserCount();
    }

    /**
     * Clean up inactive users and broadcast count
     */
    public function cleanupInactiveUsers(): int
    {
        $users = Cache::get(self::USER_PRESENCE_CACHE_KEY, []);
        $now = now()->timestamp;
        $activeUsers = [];

        foreach ($users as $userId => $timestamp) {
            // Keep users who have been active within the timeout period
            if ($now - $timestamp < self::USER_PRESENCE_TIMEOUT) {
                $activeUsers[$userId] = $timestamp;
            }
        }

        Cache::forever(self::USER_PRESENCE_CACHE_KEY, $activeUsers);

        $count = count($activeUsers);
        broadcast(new UserCountUpdated($count));

        return $count;
    }

    /**
     * Get current active user count
     */
    public function getActiveUserCount(): int
    {
        $users = Cache::get(self::USER_PRESENCE_CACHE_KEY, []);
        $now = now()->timestamp;
        $count = 0;

        foreach ($users as $userId => $timestamp) {
            if ($now - $timestamp < self::USER_PRESENCE_TIMEOUT) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get or generate user ID from session
     */
    private function getUserId(): string
    {
        if (! Session::has('grid_user_id')) {
            Session::put('grid_user_id', (string) \Str::uuid());
        }

        return Session::get('grid_user_id');
    }

    /**
     * Broadcast current user count
     */
    private function broadcastUserCount(): void
    {
        $count = $this->getActiveUserCount();
        // Only broadcast if we're not in a console environment (like tests)
        if (app()->runningInConsole()) {
            return;
        }
        broadcast(new UserCountUpdated($count));
    }
}
