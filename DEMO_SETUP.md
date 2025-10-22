# Emoji Grid Demo - Setup & Running

## What Was Built

A real-time collaborative emoji grid demo using Laravel, Inertia, React, and Laravel Reverb. The app features:

- **50x50 Grid**: A 7x7 grid of clickable cells (easy to customize)
- **4 Emojis**: 🚀, ❤️, 🤯, 🔥 - selectable via dropdown
- **Public Broadcasting**: Anyone can add emojis, updates broadcast in real-time to all users
- **Zero Database Queries**: Uses public channels (no auth required)
- **Demo-Friendly Code**: Minimal, clean, easy to follow

## Project Structure

### Backend

- **Model**: `app/Models/GridCell.php` - Stores position and emoji
- **Controller**: `app/Http/Controllers/GridController.php` - Handles fetching grid and updating cells
- **Event**: `app/Events/GridCellUpdated.php` - Broadcasts updates on public `grid` channel
- **Database**: `grid_cells` table with `position` and `emoji` columns
- **Routes**:
    - `GET /` - Renders the grid page
    - `PUT /grid/{position}` - Updates a cell and broadcasts

### Frontend

- **Page**: `resources/js/pages/Grid.tsx` - React component with `useEchoPublic` hook
- **Styling**: Tailwind CSS with dark mode support

## How It Works

1. **Initial Load**: Grid fetches existing cell data from database
2. **User Updates**: Clicking a cell sends PUT request to `/grid/{position}` with selected emoji
3. **Broadcasting**: Server broadcasts event to `grid` channel with event name `cell-updated`
4. **Real-Time Sync**: `useEchoPublic` hook receives broadcast and updates local state
5. **All Devices**: Every connected user sees the update instantly

## Running the Demo

### Prerequisites

- PHP 8.4+
- Node.js 18+
- Laravel Reverb running (for WebSocket broadcasting)

### Start the Development Server

```bash
# Terminal 1: Start Laravel dev server
php artisan serve

# Terminal 2: Start Node/Vite dev server
npm run dev

# Terminal 3: Start Laravel Reverb (WebSocket server)
php artisan reverb:start

# Terminal 4 (Optional): Start queue worker
php artisan queue:work
```

**OR** use the concurrent command:

```bash
composer run dev
```

### Visit the App

Open http://localhost:8000 in multiple browser tabs/windows and start adding emojis!

## Key Code Files to Review

- **Grid Component** (`resources/js/pages/Grid.tsx`): Shows useEchoPublic usage
- **Event** (`app/Events/GridCellUpdated.php`): Broadcasting configuration
- **Controller** (`app/Http/Controllers/GridController.php`): Simple data fetching and persistence
- **Model** (`app/Models/GridCell.php`): Database model with fillable attributes

## Demo Points

✅ **Simplicity**: < 100 lines of frontend code  
✅ **Public Channels**: No authentication complexity  
✅ **Real-Time**: Instant updates across all users  
✅ **Scalable**: Database persistence + broadcasting  
✅ **Clean Code**: Easy to explain and modify for demos

## Customization Ideas

- **Change grid size**: Modify `GRID_SIZE` in Grid.tsx
- **Add more emojis**: Add to `EMOJIS` array
- **Persistence**: Grid data persists in database
- **Styling**: Fully styled with Tailwind, easy to customize
- **Users**: Can track per-user contributions by authenticating users
