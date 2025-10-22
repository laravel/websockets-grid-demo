# Wayfinder + Inertia Form Component Integration

## Overview

Successfully integrated Inertia v2's `<Form>` component with Wayfinder auto-generated controller actions. This provides type-safe, zero-configuration form handling with automatic CSRF protection.

## Key Components

### 1. Updated Route (routes/web.php)

```php
Route::put('/grid/{position}', [GridController::class, 'update'])->name('grid.update');
```

Added route name for Wayfinder to recognize and auto-generate the action.

### 2. Wayfinder Generated Action

Wayfinder automatically generates type-safe actions:

```typescript
// resources/js/actions/App/Http/Controllers/GridController.ts
export const update = (position: number) => ({
    url: '/grid/42', // Auto-filled based on parameters
    method: 'put',
});
```

### 3. Vite Configuration

Added path alias to resolve Wayfinder actions:

```typescript
// vite.config.ts
resolve: {
    alias: {
        App: path.resolve(__dirname, './resources/js/actions/App'),
    },
}
```

### 4. TypeScript Configuration

Added path mapping for IDE support:

```json
// tsconfig.json
"paths": {
    "@/*": ["./resources/js/*"],
    "App/*": ["./resources/js/actions/App/*"]
}
```

### 5. Inertia Form Component

```typescript
import { Form } from '@inertiajs/react';
import { update as gridUpdate } from 'App/Http/Controllers/GridController';

<Form
    action={gridUpdate(position)}  // Wayfinder action
    method="put"                   // HTTP method
    onError={(errors) => { /* ... */ }}  // Error handling
>
    {({ processing, submit }) => (
        <>
            <input type="hidden" name="emoji" value={selectedEmoji} />
            <button onClick={() => submit()}>
                {emoji || ''}
            </button>
        </>
    )}
</Form>
```

## How It Works

### Request Flow

```
1. Wayfinder auto-generates controller actions from routes
   ↓
2. Grid component imports: `import { update } from 'App/Http/Controllers/GridController'`
   ↓
3. Call update(position) to get route definition
   ↓
4. Pass to Form component's action prop
   ↓
5. Form component renders HTML form with Inertia integration
   ↓
6. On submit:
   ├─ Inertia automatically adds CSRF token
   ├─ Sends PUT request to /grid/{position}
   ├─ Form data includes: { emoji: '🚀' }
   └─ Server processes and broadcasts event
```

## Benefits

✅ **Type Safety**: Wayfinder provides full TypeScript types for all routes  
✅ **Zero Configuration**: No manual CSRF token handling  
✅ **Automatic Updates**: Regenerates actions when routes change  
✅ **IDE Support**: Full autocomplete and intellisense  
✅ **Refactoring Safe**: Change route without updating component imports  
✅ **Form Variants**: Supports all form submission methods

## Wayfinder Features Used

### Route Actions

```typescript
// Generates URL and method from route
gridUpdate(position); // { url: '/grid/42', method: 'put' }
```

### Form Variants

```typescript
// Inertia Form component slot props provide:
gridUpdate.form(position); // Optimized for HTML forms
```

### Type Safety

```typescript
update = (
    args: { position: string | number } | [position: string | number] | string | number,
    options?: RouteQueryOptions
): RouteDefinition<'put'> => ...
```

## File Changes Summary

| File                          | Change                   | Purpose               |
| ----------------------------- | ------------------------ | --------------------- |
| `routes/web.php`              | Added route name         | Wayfinder recognition |
| `vite.config.ts`              | Added `App` alias        | Module resolution     |
| `tsconfig.json`               | Added `App/*` path       | TypeScript support    |
| `resources/js/pages/Grid.tsx` | Use `<Form>` + Wayfinder | Form integration      |

## Component Code Example

```typescript
import { Form, Head } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { update as gridUpdate } from 'App/Http/Controllers/GridController';
import { useState } from 'react';

export default function Grid({ initialCells }) {
    const [cells, setCells] = useState(initialCells);
    const [selectedEmoji, setSelectedEmoji] = useState('🚀');

    return (
        <div className="grid">
            {Array.from({ length: 900 }).map((_, position) => (
                <Form
                    key={position}
                    action={gridUpdate(position)}
                    method="put"
                    onError={(errors) => console.error(errors)}
                >
                    {({ processing, submit }) => (
                        <>
                            <input type="hidden" name="emoji" value={selectedEmoji} />
                            <button onClick={submit} disabled={processing}>
                                {cells[position] || ''}
                            </button>
                        </>
                    )}
                </Form>
            ))}
        </div>
    );
}
```

## Route Definition Example

Wayfinder generates this automatically:

```typescript
export const update = (
    args: { position: string | number } | [position: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.url = (args, options) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args };
    }

    return update.definition.url.replace('{position}', args.position.toString()) + queryParams(options);
};
```

## CSRF Protection

✅ **Automatic**: Inertia Form handles all CSRF tokens  
✅ **No Manual Setup**: No need to fetch or pass tokens  
✅ **Middleware Compatible**: Works with Laravel's VerifyCsrfToken

## Error Handling

```typescript
<Form
    onError={(errors: Record<string, string | string[]>) => {
        // errors.emoji = ["The emoji field is invalid."]
        console.error(errors.emoji);

        // Rollback optimistic update
        setCells(prev => {
            const updated = { ...prev };
            delete updated[position];
            return updated;
        });
    }}
>
```

## Building & Running

```bash
# Build frontend (Wayfinder auto-generates actions)
npm run build

# Type check (full type safety)
npm run types

# Lint (follows project conventions)
npm run lint

# Start services
composer run dev
```

## References

- [Inertia v2 Form Component](https://inertiajs.com/forms)
- [Wayfinder Documentation](https://laravel.com/docs/12.x/wayfinder)
- [Laravel Routing](https://laravel.com/docs/12.x/routing)

---

**Status**: ✅ Complete  
**Integration**: Wayfinder + Inertia Form  
**Type Safety**: ✅ Full TypeScript support  
**CSRF Protection**: ✅ Automatic  
**Production Ready**: ✅ Yes
