<?php

namespace App\Http\Controllers;

use App\Events\GridCellUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GridController extends Controller
{
    private const GRID_CACHE_KEY = 'emoji_grid';

    private const GRID_TIMESTAMPS_KEY = 'emoji_grid_timestamps';

    private const GRID_CLICK_COUNTS_KEY = 'emoji_grid_click_counts';

    public function show(): \Inertia\Response
    {
        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        $clickCounts = Cache::get(self::GRID_CLICK_COUNTS_KEY, []);

        return Inertia::render('Grid', [
            'initialCells' => $cells,
            'cellTimestamps' => $timestamps,
            'cellClickCounts' => $clickCounts,
        ]);
    }

    public function update(Request $request, int $position): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'click' => 'required|boolean',
        ]);

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

        broadcast(new GridCellUpdated([
            'position' => $position,
            'emoji' => $emoji,
            'timestamp' => $timestamps[$position],
            'clickCount' => $clickCounts[$position],
        ]))->toOthers();

        return response()->json(['success' => true, 'clickCount' => $clickCounts[$position]]);
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
}
