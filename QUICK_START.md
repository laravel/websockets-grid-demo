# Quick Start - Emoji Grid Demo

## Start the App (One Command)

```bash
composer run dev
```

This starts all three services:

- Laravel server (localhost:8000)
- Vite dev server (localhost:5173)
- Reverb WebSocket (localhost:8080)

## Or Start Manually (3 terminals)

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3
php artisan reverb:start
```

## Test It

1. Open http://localhost:8000 in browser
2. Select an emoji from the dropdown
3. Click any cell in the 30×30 grid
4. Emoji appears instantly
5. Open another browser tab → see emoji in real-time
6. Refresh the page → emoji persists (cached)

## Tech Stack

- **Backend**: Laravel 12 with Reverb
- **Frontend**: React 19 + TypeScript + Tailwind
- **Real-Time**: WebSockets via Laravel Reverb
- **Caching**: Database cache for persistence
- **Broadcasting**: `ShouldBroadcastNow` for immediate dispatch

## How It Works

```
Click cell with emoji
    ↓
React sends PUT /grid/{position}
    ↓
Controller saves to Cache + broadcasts event
    ↓
Reverb sends WebSocket message
    ↓
React hook receives event
    ↓
UI updates instantly across all users
```

## Key Files

- `app/Http/Controllers/GridController.php` - Handles updates
- `app/Events/GridCellUpdated.php` - Broadcasting event (ShouldBroadcastNow)
- `resources/js/pages/Grid.tsx` - React component with useEchoPublic
- `routes/web.php` - API routes

## Clear Grid Data

```bash
php artisan cache:clear
```

Or in tinker:

```bash
php artisan tinker
Cache::forget('emoji_grid')
```

## Troubleshooting

**Emojis not persisting after refresh?**

- Check cache driver: `config('cache.default')` should be 'database'
- Verify cache table exists: `php artisan migrate`

**Real-time updates not working?**

- Check Reverb is running: `php artisan reverb:start`
- Check WebSocket connection: Open DevTools → Network → check for WS connections
- Check `BROADCAST_CONNECTION=reverb` in `.env`

**Port conflicts?**

- Laravel: Change with `php artisan serve --port=3000`
- Vite: `npm run dev -- --port 3001`
- Reverb: `php artisan reverb:start --port 9000`

## Next Steps

- Add user authentication to track who placed each emoji
- Add emoji reactions/voting system
- Create multiple grids
- Add animation effects when emojis appear
- Deploy to production with proper SSL certificates

Enjoy! 🚀
