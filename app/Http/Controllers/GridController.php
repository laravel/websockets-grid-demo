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
    private const COOLDOWN_SECONDS = 10;

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

    public function update(Request $request, int $position): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|in:🚀,❤️,🤯,🔥',
        ]);

        $timestamps = Cache::get(self::GRID_TIMESTAMPS_KEY, []);
        $lastUpdate = $timestamps[$position] ?? 0;
        $timeSinceUpdate = time() - $lastUpdate;

        if ($timeSinceUpdate < self::COOLDOWN_SECONDS && $lastUpdate > 0) {
            return redirect('/');
        }

        $cells = Cache::get(self::GRID_CACHE_KEY, []);
        $cells[$position] = $validated['emoji'];
        Cache::forever(self::GRID_CACHE_KEY, $cells);

        $timestamps[$position] = time();
        Cache::forever(self::GRID_TIMESTAMPS_KEY, $timestamps);

        broadcast(new GridCellUpdated([
            'position' => $position,
            'emoji' => $validated['emoji'],
            'timestamp' => $timestamps[$position],
        ]))->toOthers();

        return redirect('/');
    }


}
