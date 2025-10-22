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

    private const COOLDOWN_SECONDS = 5;

    public function show(): \Inertia\Response
    {
        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);

        return Inertia::render('Grid', [
            'initialCells' => $cells,
            'cellTimestamps' => $timestamps,
            'cooldownSeconds' => self::COOLDOWN_SECONDS,
        ]);
    }

    public function update(Request $request, int $position): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|in:🚀,❤️,🤯,🔥',
        ]);

        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        $lastUpdate = $timestamps[$position] ?? 0;
        $nowMs = round(microtime(true) * 1000);
        $timeSinceUpdateMs = $nowMs - $lastUpdate;
        $cooldownMs = self::COOLDOWN_SECONDS * 1000;

        if ($timeSinceUpdateMs < $cooldownMs && $lastUpdate > 0) {
            return response()->json(['error' => 'Cell is on cooldown'], 429);
        }

        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        $cells[$position] = $validated['emoji'];
        Cache::forever(self::GRID_CACHE_KEY, $cells);

        $timestamps[$position] = $nowMs;
        Cache::forever(self::GRID_TIMESTAMPS_KEY, $timestamps);

        broadcast(new GridCellUpdated([
            'position' => $position,
            'emoji' => $validated['emoji'],
            'timestamp' => $timestamps[$position],
        ]))->toOthers();

        return response()->json(['success' => true]);
    }

    public function clear(int $position): \Illuminate\Http\JsonResponse
    {
        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        unset($cells[$position]);
        Cache::forever(self::GRID_CACHE_KEY, $cells);

        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        unset($timestamps[$position]);
        Cache::forever(self::GRID_TIMESTAMPS_KEY, $timestamps);

        return response()->json(['success' => true]);
    }
}
