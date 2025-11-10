<?php

namespace App\Services;

use App\Events\UserCountUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class UserPresenceService
{
    private const USER_PRESENCE_CACHE_KEY = 'active_users';

    /**
     * Register a user as active
     */
    public function registerUser(): void
    {
        $userId = $this->getUserId();
        $users = Cache::get(self::USER_PRESENCE_CACHE_KEY, []);

        // Add this user to active users
        $users[$userId] = now()->timestamp;
        Cache::forever(self::USER_PRESENCE_CACHE_KEY, $users);

        // Broadcast updated count
        $this->broadcastUserCount();
    }

    /**
     * Remove a user from active users
     */
    public function removeUser(): void
    {
        $userId = $this->getUserId();
        $users = Cache::get(self::USER_PRESENCE_CACHE_KEY, []);

        // Remove this user
        unset($users[$userId]);
        Cache::forever(self::USER_PRESENCE_CACHE_KEY, $users);

        // Broadcast updated count
        $this->broadcastUserCount();
    }

    /**
     * Get current active user count
     */
    public function getActiveUserCount(): int
    {
        $users = Cache::get(self::USER_PRESENCE_CACHE_KEY, []);

        return count($users);
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
        broadcast(new UserCountUpdated($count));
    }
}
