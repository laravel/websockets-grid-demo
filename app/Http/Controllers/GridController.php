<?php

namespace App\Http\Controllers;

use App\Events\GridCellClicked;
use App\Events\GridCellUpdated;
use App\Events\GridRainStarted;
use App\Services\UserPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GridController extends Controller
{
    private const GRID_CACHE_KEY = 'emoji_grid';

    private const GRID_TIMESTAMPS_KEY = 'emoji_grid_timestamps';

    private const GRID_CLICK_COUNTS_KEY = 'emoji_grid_click_counts';

    private const RAIN_COOLDOWN_KEY = 'emoji_grid_rain_cooldown';

    private const RAIN_COOLDOWN_DURATION = 120;

    public function show(): \Inertia\Response
    {
        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        $clickCounts = Cache::get(self::GRID_CLICK_COUNTS_KEY, []);

        $presenceService = App::make(UserPresenceService::class);
        $activeUserCount = $presenceService->getActiveUserCount();

        return Inertia::render('Grid', [
            'initialCells' => $cells,
            'cellTimestamps' => $timestamps,
            'cellClickCounts' => $clickCounts,
            'initialActiveUserCount' => $activeUserCount,
        ]);
    }

    public function update(Request $request, int $position): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'click' => 'required|boolean',
        ]);

        // Track user activity (they clicked, so they're active)
        $presenceService = App::make(UserPresenceService::class);
        $presenceService->registerUser();

        // Get all data in a consistent way
        $clickCounts = Cache::get(self::GRID_CLICK_COUNTS_KEY, []);
        $clickCounts[$position] = ($clickCounts[$position] ?? 0) + 1;
        Cache::forever(self::GRID_CLICK_COUNTS_KEY, $clickCounts);

        $emoji = $this->getEmojiForClickCount($clickCounts[$position]);

        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        $cells[$position] = $emoji;
        Cache::forever(self::GRID_CACHE_KEY, $cells);

        $nowMs = round(microtime(true) * 1000);
        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        $timestamps[$position] = $nowMs;
        Cache::forever(self::GRID_TIMESTAMPS_KEY, $timestamps);

        // Broadcast all data together to ensure consistency
        broadcast(new GridCellUpdated([
            'position' => $position,
            'emoji' => $emoji,
            'timestamp' => $timestamps[$position],
            'clickCount' => $clickCounts[$position],
        ]))->toOthers();

        broadcast(new GridCellClicked($position, $clickCounts[$position]))->toOthers();

        $majorityEmoji = $this->getMajorityEmoji($cells);
        if ($majorityEmoji && $this->canTriggerRain($majorityEmoji)) {
            $this->setRainCooldown($majorityEmoji);
            $displayEmoji = $this->getDisplayEmoji($majorityEmoji);
            broadcast(new GridRainStarted($displayEmoji));
        }

        return response()->json([
            'success' => true,
            'clickCount' => $clickCounts[$position],
            'emoji' => $emoji,
            'timestamp' => $timestamps[$position],
        ]);
    }

    public function clear(int $position): \Illuminate\Http\JsonResponse
    {
        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        unset($cells[$position]);
        Cache::forever(self::GRID_CACHE_KEY, $cells);

        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        unset($timestamps[$position]);
        Cache::forever(self::GRID_TIMESTAMPS_KEY, $timestamps);

        $clickCounts = Cache::get(self::GRID_CLICK_COUNTS_KEY, []);
        unset($clickCounts[$position]);
        Cache::forever(self::GRID_CLICK_COUNTS_KEY, $clickCounts);

        // Broadcast the clear to all clients
        broadcast(new GridCellUpdated([
            'position' => $position,
            'emoji' => null,
            'timestamp' => null,
            'clickCount' => 0,
        ]));

        return response()->json(['success' => true]);
    }

    private function getEmojiForClickCount(int $clickCount): string
    {
        return match (true) {
            $clickCount >= 500 => 'taylor',
            $clickCount >= 100 => '🔥',
            $clickCount >= 50 => '🤯',
            $clickCount >= 10 => '🚀',
            default => '❤️',
        };
    }

    private function getMajorityEmoji(array $cells): ?string
    {
        // 100 total squares - 4 center squares reserved for user count = 96 clickable squares
        $clickableGridSize = 96;

        // Grid must be completely filled
        if (count($cells) !== $clickableGridSize) {
            return null;
        }

        // Count each emoji
        $emojiCounts = [];
        foreach ($cells as $emoji) {
            $emojiCounts[$emoji] = ($emojiCounts[$emoji] ?? 0) + 1;
        }

        // Find the emoji with the most occurrences
        $maxCount = 0;
        $majorityEmoji = null;
        foreach ($emojiCounts as $emoji => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $majorityEmoji = $emoji;
            }
        }

        return $majorityEmoji;
    }

    private function getDisplayEmoji(string $emoji): string
    {
        return $emoji === 'taylor' ? '🧑' : $emoji;
    }

    private function canTriggerRain(string $emoji): bool
    {
        $cooldown = Cache::get(self::RAIN_COOLDOWN_KEY);

        if ($cooldown === null) {
            return true;
        }

        return $cooldown !== $emoji;
    }

    private function setRainCooldown(string $emoji): void
    {
        Cache::put(self::RAIN_COOLDOWN_KEY, $emoji, self::RAIN_COOLDOWN_DURATION);
    }

    public function markActive(): \Illuminate\Http\JsonResponse
    {
        $presenceService = App::make(UserPresenceService::class);
        $presenceService->registerUser();

        return response()->json(['success' => true]);
    }
}
