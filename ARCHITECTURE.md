# Architecture Diagram - Emoji Grid

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     USER BROWSERS                            │
│  ┌──────────────────┐              ┌──────────────────┐     │
│  │   Browser 1      │              │   Browser 2      │     │
│  │ ┌──────────────┐ │              │ ┌──────────────┐ │     │
│  │ │ React Grid   │ │              │ │ React Grid   │ │     │
│  │ │ Component    │ │              │ │ Component    │ │     │
│  │ └──────────────┘ │              │ └──────────────┘ │     │
│  │      ▲    │      │              │      ▲    │      │     │
│  │      │    │      │              │      │    │      │     │
│  │   HTTP   │      │              │   HTTP   │      │     │
│  │   WS     │      │              │   WS     │      │     │
│  │      │    ▼      │              │      │    ▼      │     │
│  │ ┌──────────────┐ │              │ ┌──────────────┐ │     │
│  │ │ useEchoPublic│ │              │ │ useEchoPublic│ │     │
│  │ │ Hook         │ │              │ │ Hook         │ │     │
│  │ └──────────────┘ │              │ └──────────────┘ │     │
│  └──────────────────┘              └──────────────────┘     │
└─────────────────────────────────────────────────────────────┘
            │ HTTP PUT                      │ WebSocket
            │                                │ (receives)
            ▼                                ▼
┌─────────────────────────────────────────────────────────┐
│           LARAVEL REVERB WEBSOCKET SERVER               │
│                   (Port 8080)                           │
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │ Broadcast Channel: 'grid'                      │    │
│  │ Events: cell-updated                           │    │
│  └────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
            │                              ▲
            │ Broadcast event              │ Subscribe
            │ { position, emoji }          │
            ▼                              │
┌─────────────────────────────────────────────────────────┐
│             LARAVEL APPLICATION SERVER                   │
│                  (Port 8000)                            │
│                                                          │
│  ┌─────────────────────────────────────────────────┐   │
│  │  PUT /grid/{position}                           │   │
│  │                                                  │   │
│  │  GridController::update()                       │   │
│  │  ├─ Validate emoji                              │   │
│  │  ├─ Cache::forever() → Save grid state          │   │
│  │  ├─ GridCellUpdated::dispatch()                 │   │
│  │  │  └─ ShouldBroadcastNow (immediate)          │   │
│  │  └─ return JSON response                        │   │
│  │                                                  │   │
│  │  [Event fires instantly via Reverb]             │   │
│  └─────────────────────────────────────────────────┘   │
│                                                          │
│  ┌─────────────────────────────────────────────────┐   │
│  │  GET / (page load)                              │   │
│  │  GridController::show()                         │   │
│  │  ├─ Load from Cache                             │   │
│  │  └─ Pass to React component                     │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
            ▼                              │
┌─────────────────────────────────────────────────────────┐
│             DATABASE / CACHE LAYER                       │
│                                                          │
│  ┌───────────────────────────────────────────────┐    │
│  │ Cache Key: 'emoji_grid'                      │    │
│  │                                               │    │
│  │ {                                             │    │
│  │   "0": "🚀",                                  │    │
│  │   "5": "❤️",                                  │    │
│  │   "42": "🤯",                                 │    │
│  │   "100": "🔥"                                 │    │
│  │ }                                             │    │
│  │                                               │    │
│  │ TTL: Forever (persistent)                    │    │
│  └───────────────────────────────────────────────┘    │
│                                                          │
│  ┌───────────────────────────────────────────────┐    │
│  │ Driver: Database (cache_table)                │    │
│  └───────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
```

## Request/Response Flow (Single User Action)

```
USER CLICKS CELL 42 WITH 🚀
│
├─→ React: setCells({ ...prev, [42]: '🚀' }) [Optimistic]
│
└─→ fetch('PUT /grid/42', { emoji: '🚀' })
    │
    ├─→ Laravel GridController::update()
    │   │
    │   ├─→ Validate: emoji in ['🚀', '❤️', '🤯', '🔥'] ✓
    │   │
    │   ├─→ Load cache: Cache::get('emoji_grid', [])
    │   │   └─→ Result: { 5: '❤️', 100: '🔥', ... }
    │   │
    │   ├─→ Add emoji: cells[42] = '🚀'
    │   │
    │   ├─→ Save cache: Cache::forever('emoji_grid', cells)
    │   │   └─→ Database updated ✓
    │   │
    │   ├─→ Dispatch event: GridCellUpdated::dispatch(...)
    │   │   └─→ ShouldBroadcastNow (IMMEDIATE)
    │   │
    │   └─→ return JSON: { position: 42, emoji: '🚀' }
    │
    ├─→ Reverb receives event
    │   │
    │   ├─→ Serialize: { position: 42, emoji: '🚀' }
    │   │
    │   ├─→ Broadcast to channel: 'grid'
    │   │
    │   ├─→ Send to all subscribers EXCEPT originating client
    │   │   (.toOthers() applied)
    │   │
    │   └─→ WebSocket: Send frame to Browser 2
    │
    └─→ React receives fetch response
        └─→ Confirm UI update (already done optimistically)

BROWSER 2 RECEIVES EVENT (1-2ms later)
│
└─→ WebSocket message received
    │
    ├─→ useEchoPublic hook detects 'cell-updated' event
    │
    ├─→ Call callback: (data) => setCells(...)
    │
    ├─→ React: setCells({ ...prev, [42]: '🚀' })
    │
    └─→ Component re-renders
        └─→ UI shows 🚀 at position 42 ✓

USER REFRESHES BROWSER 2
│
└─→ GET / request
    │
    ├─→ Laravel serves page
    │
    ├─→ GridController::show()
    │   │
    │   ├─→ Cache::get('emoji_grid')
    │   │   └─→ Result includes { 42: '🚀', ... }
    │   │
    │   └─→ Pass to React as props: initialCells
    │
    └─→ React component initializes
        │
        ├─→ useState(initialCells)
        │   └─→ State includes { 42: '🚀', ... }
        │
        └─→ Render grid
            └─→ Cell 42 shows 🚀 ✓ (persisted!)
```

## Data Flow Timeline

```
T+0ms      │ User clicks cell
           │
T+5ms      │ Optimistic UI update (local React state)
           │ Browser 1: Shows emoji
           │
T+10ms     │ fetch() reaches server
           │ GridController validates & caches
           │
T+15ms     │ GridCellUpdated::dispatch() fires
           │ (ShouldBroadcastNow = synchronous)
           │
T+18ms     │ Reverb receives event from Laravel
           │ Prepares WebSocket frames
           │
T+20ms     │ Browser 1: fetch() response received
           │ (confirms cache was updated)
           │
T+22ms     │ Browser 2: WebSocket message received
           │ useEchoPublic callback fires
           │
T+25ms     │ Browser 2: State updated
           │ Component re-renders
           │ Emoji appears (only ~15ms latency!)
```

## Component Interaction

```
┌──────────────────────┐
│   Grid Component     │
│  (React 19)          │
├──────────────────────┤
│                      │
│  State:              │
│  • cells: Record     │
│  • selectedEmoji     │
│                      │
│  Props:              │
│  • initialCells      │
│                      │
│  Hooks:              │
│  • useState()        │
│  • useEchoPublic()   │◄──── Listens to 'grid' channel
│                      │       Event: 'cell-updated'
│  Methods:            │
│  • handleCellClick() │────► PUT /grid/{position}
│                      │
└──────────────────────┘
         │
         │ Receives event data:
         │ { position, emoji }
         │
         └──► setState() ──► Re-render
```

## Event Class Structure

```
┌─────────────────────────────────────┐
│   GridCellUpdated Event             │
├─────────────────────────────────────┤
│                                     │
│  implements ShouldBroadcastNow      │
│  ├─ Executes synchronously          │
│  └─ No queue worker needed          │
│                                     │
│  __construct(array $data)           │
│  └─ Stores: { position, emoji }    │
│                                     │
│  broadcastOn()                      │
│  └─ Channel: 'grid' (public)        │
│                                     │
│  broadcastAs()                      │
│  └─ Event name: 'cell-updated'     │
│                                     │
│  broadcastWith()                    │
│  └─ Payload: { position, emoji }   │
│                                     │
└─────────────────────────────────────┘
```

## Communication Protocols

```
Browser 1 <──────HTTP──────> Laravel Server
                │
                ├─ Request:  PUT /grid/42
                │            Headers: X-CSRF-Token
                │            Body: { emoji: '🚀' }
                │
                └─ Response: 200 OK
                             { position: 42, emoji: '🚀' }

Browser 1 ◄─────WebSocket─────► Reverb Server
Browser 2 ◄─────WebSocket─────► Reverb Server

                │
                ├─ Browser connects to 'grid' channel
                ├─ Subscribes to 'cell-updated' event
                ├─ Receives: { position: 42, emoji: '🚀' }
                └─ Connection stays open (bi-directional)

Laravel ─────────HTTP────────► Reverb (broadcast endpoint)
         Frame: POST /broadcasting/auth
         Body: event data, channel, socket_id
```

## Error Handling Path

```
User Action
│
├─→ Validation Fails (invalid emoji)
│   │
│   ├─→ 422 Unprocessable Entity
│   │
│   └─→ Optimistic state rolled back
│
├─→ Network Error
│   │
│   ├─→ fetch() catch block
│   │
│   ├─→ Console error logged
│   │
│   └─→ UI keeps optimistic update (for UX)
│
├─→ Server Error
│   │
│   ├─→ 500 Internal Server Error
│   │
│   ├─→ Cache may be inconsistent
│   │
│   └─→ Reload page to sync state
│
└─→ WebSocket Disconnected
    │
    ├─→ useEchoPublic stops receiving events
    │
    ├─→ Auto-reconnect (Echo handles this)
    │
    └─→ Sync state on page reload
```

---

This architecture provides:

- **Low latency**: ShouldBroadcastNow eliminates queue delays
- **Real-time sync**: WebSockets for instant updates
- **Persistence**: Cache ensures data survives page reloads
- **Simplicity**: Clean separation of concerns
- **Scalability**: Ready for production with Redis cache
