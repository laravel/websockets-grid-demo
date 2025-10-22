# Broadcasting Setup with ShouldBroadcastNow

## Overview

This application uses Laravel Reverb with `ShouldBroadcastNow` for immediate, real-time broadcasting of emoji grid updates.

## Architecture

### Event Broadcasting Flow

```
User clicks cell
    ↓
fetch() POST to /grid/{position}
    ↓
GridController::update()
    ├─ Validate emoji
    ├─ Save to Cache (persistence)
    ├─ Dispatch GridCellUpdated event
    └─ Return JSON response
    ↓
GridCellUpdated::dispatch() [ShouldBroadcastNow]
    ├─ Broadcasts immediately (no queue)
    ├─ Channel: 'grid'
    ├─ Event name: 'cell-updated'
    └─ Payload: { position, emoji }
    ↓
Laravel Reverb WebSocket server
    ├─ Receives broadcast
    ├─ Sends to all connected clients on 'grid' channel
    └─ Except the originating client (.toOthers())
    ↓
Client receives via Laravel Echo
    ├─ useEchoPublic hook detects 'cell-updated' event
    ├─ Calls callback with event data
    ├─ Updates local React state
    └─ UI re-renders with new emoji
```

## Key Components

### 1. Event: GridCellUpdated (`app/Events/GridCellUpdated.php`)

```php
class GridCellUpdated implements ShouldBroadcastNow
{
    // ShouldBroadcastNow = dispatch immediately without queue
    // ShouldBroadcast = queue for later processing

    public function broadcastOn(): array {
        return [new Channel('grid')];  // Public channel
    }

    public function broadcastAs(): string {
        return 'cell-updated';  // Event name listeners use
    }

    public function broadcastWith(): array {
        return $this->data;  // What gets sent to clients
    }
}
```

**Why ShouldBroadcastNow?**

- Executes synchronously in the current request
- No queue worker needed
- No race conditions with cache writes
- Perfect for real-time updates that need immediate broadcast

### 2. Controller: GridController (`app/Http/Controllers/GridController.php`)

```php
public function update(Request $request, int $position): JsonResponse
{
    // 1. Validate emoji
    $validated = $request->validate(['emoji' => 'required|in:🚀,❤️,🤯,🔥']);

    // 2. Update cache for persistence
    $cells = Cache::get(self::GRID_CACHE_KEY, []);
    $cells[$position] = $validated['emoji'];
    Cache::forever(self::GRID_CACHE_KEY, $cells);

    // 3. Dispatch event for real-time broadcast
    GridCellUpdated::dispatch([
        'position' => $position,
        'emoji' => $validated['emoji'],
    ])->toOthers();  // Don't send back to originating client

    // 4. Return response
    return response()->json(['position' => $position, 'emoji' => $validated['emoji']]);
}
```

### 3. React Component: Grid (`resources/js/pages/Grid.tsx`)

```typescript
useEchoPublic(
    'grid', // Channel to listen on
    'cell-updated', // Event name from broadcastAs()
    (data) => {
        // Callback with event data
        setCells((prev) => ({
            ...prev,
            [data.position]: data.emoji,
        }));
    },
);
```

**Key Hook:** `useEchoPublic`

- Connects to public channel (no auth required)
- Listens for specific event name
- Automatically cleans up when component unmounts
- Includes type safety for event data

## Configuration

### Environment (.env)

```bash
BROADCAST_CONNECTION=reverb    # Use Laravel Reverb
QUEUE_CONNECTION=database       # Default queue (not used with ShouldBroadcastNow)
REVERB_HOST=127.0.0.1          # Local development
REVERB_PORT=8080               # WebSocket port
REVERB_SCHEME=http             # http for local, https for production
```

### Broadcasting Config (`config/broadcasting.php`)

```php
'default' => env('BROADCAST_CONNECTION', 'null'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
        ],
    ],
]
```

### Echo Setup (`resources/js/app.tsx`)

```typescript
import { configureEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
});
```

## Running the Application

### Start Services (3 terminals)

**Terminal 1: Laravel Server**

```bash
php artisan serve
# Runs on http://localhost:8000
```

**Terminal 2: Vite Dev Server**

```bash
npm run dev
# Handles frontend HMR and asset bundling
```

**Terminal 3: Reverb WebSocket Server**

```bash
php artisan reverb:start
# WebSocket server on ws://localhost:8080
```

**All at once:**

```bash
composer run dev
```

## How It Works in Practice

1. **User 1** clicks cell 42, selects 🚀
2. **Request** goes to `PUT /grid/42` with `{ emoji: '🚀' }`
3. **Server** updates cache, dispatches GridCellUpdated event
4. **Reverb** broadcasts event to 'grid' channel
5. **User 1** doesn't receive event (`.toOthers()`)
6. **User 2** on same page receives event via WebSocket
7. **React** updates state, shows 🚀 at position 42
8. **All Users** now see the emoji, even after page refresh (cache)

## Data Persistence

Grid state is saved in **Cache** with key `emoji_grid`:

```php
Cache::forever('emoji_grid', [
    0 => '🚀',
    5 => '❤️',
    42 => '🤯',
    100 => '🔥',
]);
```

To clear the grid:

```bash
php artisan cache:clear
```

Or in code:

```php
Cache::forget('emoji_grid');
```

## Debugging

### Check WebSocket Connection

```javascript
// In browser console
window.Echo.connector.socket.id; // Should have a socket ID
```

### Log Events

```php
// In GridCellUpdated event
public function broadcastWith(): array {
    ray($this->data);  // If Ray is installed
    return $this->data;
}
```

### Inspect Cache

```bash
php artisan tinker
Cache::get('emoji_grid')
```

## Key Differences: ShouldBroadcastNow vs ShouldBroadcast

| Feature      | ShouldBroadcastNow  | ShouldBroadcast  |
| ------------ | ------------------- | ---------------- |
| Execution    | Synchronous         | Queued (async)   |
| Queue Worker | Not needed          | Required         |
| Latency      | Immediate           | Depends on queue |
| Use Case     | Real-time updates   | Heavy operations |
| Persistence  | Within same request | May miss data    |

## Resources

- [Laravel Broadcasting Docs](https://laravel.com/docs/12.x/broadcasting)
- [Laravel Reverb Docs](https://laravel.com/docs/12.x/reverb)
- [Laravel Echo React](https://laravel.com/docs/12.x/broadcasting#using-react-or-vue)
- [ShouldBroadcastNow Interface](https://laravel.com/docs/12.x/broadcasting#broadcast-queue)
