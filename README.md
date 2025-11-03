# 🎨 Laravel Reverb Grid - Real-Time Collaborative Demo

A modern, real-time collaborative emoji grid showcasing **Laravel 12**, **React 19**, **Inertia.js**, and **Laravel Reverb** broadcasting capabilities. This demo demonstrates best practices for building real-time collaborative applications with Laravel.

## ☁️ Laravel Cloud Deployment

This application is designed to be deployed on **Laravel Cloud**. You'll need to provision:

- **🗄️ Database** - Required for sessions, cache table, and avoiding log errors (even though grid data uses cache)
- **💾 Cache** - Required for grid state, user presence tracking, and session storage
- **⚡ Queue (or Queue Cluster)** - Required for background job processing and scheduled tasks
- **🌐 WebSockets (Laravel Reverb)** - Required for real-time broadcasting functionality

> **Note**: While the grid itself uses cache storage, a database is still required to prevent errors in Laravel's session and cache drivers. The database cache driver writes to a `cache` table created by migrations.

## ✨ Features

- 🎯 **10×10 Interactive Grid** - Click cells to level up emojis through 5 stages
- 👥 **Live User Count** - See how many artisans are online in real-time
- ⚡ **Real-Time Sync** - Instant updates via WebSockets with optimistic UI
- 🎭 **Progressive Emoji Levels** - ❤️ → 🚀 → 🤯 → 🔥 → Secret Emoji
- 🌧️ **Emoji Rain** - Fill the entire grid with one emoji to trigger a celebration
- 💾 **Persistent State** - Grid state and click counts survive page refreshes
- 🔓 **Public Access** - No authentication required for demo purposes
- 📡 **Optimized Broadcasting** - Uses `toOthers()` to prevent duplicate updates
- 🎨 **Dark Mode Support** - Automatic theme switching

## 🏗️ Architecture

```
Browser → React Component → Axios → Laravel Controller →
Cache Update → Event Dispatch → Reverb WebSocket →
All Other Browsers (Echo) → UI Update
```

**Key Broadcasting Patterns:**

- **Optimistic UI Updates** - Immediate local state changes before server confirmation
- **`toOthers()` Pattern** - Broadcasts exclude the originating user
- **Axios + Socket ID** - Automatic `X-Socket-ID` header for proper `toOthers()` behavior
- **Presence Tracking** - Session-based user tracking with automatic cleanup

## 📋 Requirements

**Local Development:**

- PHP 8.4+
- Node.js 18+
- Composer & npm
- Laravel 12
- SQLite (or MySQL/PostgreSQL)

**Production (Laravel Cloud):**

- Database (MySQL/PostgreSQL recommended)
- Cache service
- Queue or Queue Cluster
- WebSockets (Laravel Reverb)

## 🚀 Quick Start

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Start Development Servers

**Option A: One Command (Recommended)**

```bash
composer run dev
```

This starts:

- Laravel server (http://localhost:8000)
- Vite dev server (http://localhost:5173)
- Reverb WebSocket (ws://localhost:8080)

**Option B: Manual Start (3 terminals)**

```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite
npm run dev

# Terminal 3: Reverb
php artisan reverb:start
```

### 5. Open Application

Visit http://localhost:8000 (or the port shown in your terminal)

## 🎮 How to Use

1. **Click any cell** to place an emoji (starts at ❤️ level 1)
2. **Keep clicking** the same cell to level it up:
    - 1 click: ❤️
    - 10 clicks: 🚀
    - 50 clicks: 🤯
    - 100 clicks: 🔥
    - 500 clicks: Special Emoij 👀
3. **Watch the counter** in the center 2×2 area to see live user count
4. **Open multiple tabs** to see real-time collaboration
5. **Fill the entire grid** with one emoji type to trigger emoji rain! 🌧️

## 📁 Project Structure

```
app/
├── Broadcasting/
│   └── GridPresence.php           # Presence channel authorization
├── Events/
│   ├── GridCellClicked.php        # Shake animation event
│   ├── GridCellUpdated.php        # Cell emoji update event
│   ├── GridRainStarted.php        # Celebration rain event
│   └── UserCountUpdated.php       # Active user count event
├── Http/
│   ├── Controllers/
│   │   └── GridController.php     # Main grid logic
│   └── Middleware/
│       └── TrackActiveUsers.php   # Session-based user tracking
└── Services/
    └── UserPresenceService.php    # User presence management

resources/
├── js/
│   ├── app.tsx                    # Axios + Echo configuration
│   └── pages/
│       └── Grid.tsx               # React grid component
└── css/
    └── app.css                    # Tailwind styling

routes/
├── web.php                        # Web routes
└── channels.php                   # Broadcasting channel definitions
```

## 🔑 Key Implementation Details

### 1. Axios Configuration with Echo (`resources/js/app.tsx`)

```typescript
// Automatically attach Socket ID to Axios requests
axios.interceptors.request.use((config) => {
    if (window.Echo && typeof window.Echo.socketId === 'function') {
        config.headers['X-Socket-ID'] = window.Echo.socketId();
    }
    return config;
});
```

### 2. Broadcasting with `toOthers()` (`app/Http/Controllers/GridController.php`)

```php
// Broadcast to everyone except the person who clicked
broadcast(new GridCellUpdated([...]))->toOthers();
broadcast(new GridCellClicked(...))->toOthers();
```

### 3. Optimistic UI Updates (`resources/js/pages/Grid.tsx`)

```typescript
// Update local state immediately for instant feedback
setCells((prev) => ({ ...prev, [position]: newEmoji }));

// Then send to server
await axios.put(`/grid/${position}`, { click: true });
```

### 4. User Presence Tracking

- **Session-based**: Each user gets a unique UUID stored in session
- **Cache storage**: Active users stored in cache with timestamps
- **Automatic cleanup**: Scheduled task removes inactive users every minute
- **Real-time updates**: Broadcasts user count changes to all clients

## 📊 Event Flow Diagram

```
User Clicks Cell
    ↓
1. Optimistic UI Update (instant)
    ↓
2. Axios PUT with X-Socket-ID header
    ↓
3. Laravel updates cache + click count
    ↓
4. Broadcast GridCellUpdated →toOthers()
    ↓
5. Broadcast GridCellClicked →toOthers()
    ↓
6. Check if grid is uniform (rain trigger)
    ↓
7. Other browsers receive via WebSocket
    ↓
8. React updates with animations
```

## 🔧 Configuration

### Environment Variables (.env)

```bash
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Broadcasting Configuration (`config/broadcasting.php`)

Already configured for Reverb! No changes needed.

## 🧪 Testing Real-Time Features

### Test 1: Multi-Client Sync

```bash
# Open app in 2 browser tabs
# Click a cell in tab 1
# Should appear instantly in tab 2 with animations
```

### Test 2: User Count Updates

```bash
# Open app in multiple tabs/browsers
# Watch center display update with "X artisans online"
# Close tabs and see count decrease after ~5 minutes
```

### Test 3: Emoji Rain

```bash
# Click all 96 clickable cells (excluding center 4) to the same emoji
# Watch the emoji rain celebration trigger!
```

### Test 4: Persistence

```bash
# Click various cells to add emojis
# Refresh the page
# All emojis and click counts should persist
```

### Clear Grid Data

```bash
php artisan cache:clear
# or
php artisan tinker
>>> Cache::forget('emoji_grid')
>>> Cache::forget('emoji_grid_click_counts')
>>> Cache::forget('emoji_grid_timestamps')
>>> Cache::forget('active_users')
```

## 🐛 Troubleshooting

| Problem                    | Solution                                              |
| -------------------------- | ----------------------------------------------------- |
| Emojis not persisting      | Run `php artisan migrate` for cache table             |
| Real-time not working      | Verify `php artisan reverb:start` is running          |
| User count not updating    | Check middleware is registered in `bootstrap/app.php` |
| Animations feel delayed    | Ensure `toOthers()` is used in broadcasts             |
| WebSocket connection fails | Check `BROADCAST_CONNECTION=reverb` in `.env`         |
| Axios errors               | Verify CSRF token meta tag in `app.blade.php`         |
| Log errors (no database)   | Database is required even with cache - run migrations |

**Why Database is Required:**

Even though the grid uses cache for storage, Laravel requires a database for:

- Session management (database driver)
- Cache table (database cache driver uses this)
- Queue table (for job tracking)
- Migrations table (Laravel's migration tracking)

Without a database, you may see errors in logs related to sessions and cache operations, even if the application appears to work correctly.

## 🎯 Best Practices Demonstrated

### 1. **Inertia.js Props**

- ✅ Pass initial data via props (no separate API call on load)
- ✅ Keep routes in `web.php` (not `api.php`)

### 2. **Broadcasting**

- ✅ Use `toOthers()` to prevent duplicate updates
- ✅ Axios with automatic `X-Socket-ID` header
- ✅ Optimistic UI updates for instant feedback

### 3. **User Presence**

- ✅ Session-based tracking (no database needed)
- ✅ Cache storage for performance
- ✅ Scheduled cleanup of inactive users

### 4. **Code Quality**

- ✅ Laravel Pint for consistent formatting
- ✅ TypeScript for type safety
- ✅ Service classes for business logic

## 📈 Performance

- **Grid Load**: ~50ms (cache read)
- **Update Latency**: ~20-30ms end-to-end
- **Memory**: ~50MB (React + Echo)
- **WebSocket**: ~1KB per message
- **Clickable Cells**: 96 (10×10 grid minus center 4)

## 🔐 Security

- ✅ CSRF protection on all mutations
- ✅ Axios automatic CSRF token handling
- ✅ Click count validation
- ✅ Position validation (0-99)
- ✅ No authentication (public demo)
- ✅ Cache-based storage (no SQL injection risk)

## 📚 Laravel Versions

- **Laravel Framework**: 12.x
- **Laravel Reverb**: 1.x
- **Inertia.js**: 2.x
- **Laravel Echo**: 2.x
- **React**: 19.x
- **Tailwind CSS**: 4.x

## 🎓 Learning Resources

This demo is perfect for learning:

- **Real-time Laravel features** with Reverb
- **Broadcasting patterns** with `toOthers()`
- **Optimistic UI updates** in React
- **Inertia.js best practices** for SPAs
- **WebSocket implementation** with Echo
- **User presence tracking** without authentication
- **Axios integration** with Laravel Echo

## 📖 Further Reading

- [Laravel Broadcasting Docs](https://laravel.com/docs/12.x/broadcasting)
- [Laravel Reverb Docs](https://laravel.com/docs/12.x/reverb)
- [Inertia.js Docs](https://inertiajs.com)
- [Laravel Echo Docs](https://laravel.com/docs/12.x/broadcasting#client-side-installation)

## 📄 License

MIT

## 👤 Author

Built as a comprehensive demo for Laravel Reverb WebSocket broadcasting with Inertia.js and React.

---

**Status**: ✅ Production Ready  
**Last Updated**: November 2, 2025  
**Framework Versions**: Laravel 12, React 19, Inertia 2, Reverb 1

Happy coding! 🚀
