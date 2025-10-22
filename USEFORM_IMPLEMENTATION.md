# Inertia useForm Hook Implementation

## Updated to Use Inertia v2 `useForm` Hook

The Grid component now uses Inertia's `useForm` hook for proper form handling with CSRF protection, validation error handling, and loading states.

## What Changed

### Old Approach

```typescript
// Raw router.put()
router.put(`/grid/${position}`, { emoji: selectedEmoji });
```

### New Approach

```typescript
// Using useForm hook
const form = useForm({
    emoji: selectedEmoji,
});

form.put(`/grid/${position}`, {
    onError: (errors) => {
        /* handle errors */
    },
    onFinish: () => {
        /* cleanup */
    },
});
```

## Component Structure

### Main Grid Component

- Manages grid state (cells)
- Manages emoji selection
- Listens for Reverb broadcasts via `useEchoPublic`
- Handles cell click events

### CellForm Sub-Component

- Individual form for each cell
- Uses `useForm` hook for submission
- Manages form state (data, processing, errors)
- Submits via `form.put()`

## How It Works

### Each Cell Has Its Own Form

```typescript
function CellForm({ position, emoji, selectedEmoji, onCellClick, onError }: CellFormProps) {
    const form = useForm({
        emoji: selectedEmoji,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/grid/${position}`, {
            onError,
            onFinish: () => {
                form.reset();
            },
        });
    };

    return (
        <form id={`form-${position}`} onSubmit={handleSubmit} style={{ display: 'contents' }}>
            <input type="hidden" name="emoji" value={form.data.emoji} />
            <button type="button" onClick={onCellClick}>
                {emoji || ''}
            </button>
        </form>
    );
}
```

### useForm Features

**Data Management:**

```typescript
const form = useForm({
    emoji: selectedEmoji, // Initial data
});

form.data; // Current form data
form.setData(); // Update form data
form.reset(); // Reset to initial state
```

**State Tracking:**

```typescript
form.processing; // true when request in progress
form.isDirty; // true if data changed from initial
form.errors; // Validation errors object
form.hasErrors; // true if any errors
form.wasSuccessful; // true if last submit succeeded
```

**Submission Methods:**

```typescript
form.get(url, options);
form.post(url, options);
form.put(url, options); // Our usage
form.patch(url, options);
form.delete(url, options);
```

## CSRF Protection

✅ **Automatically handled** - `useForm` includes CSRF token in request headers  
✅ **No manual headers needed** - Inertia manages this internally  
✅ **Works with Laravel middleware** - Compatible with default VerifyCsrfToken

## Validation Error Handling

```typescript
form.put(`/grid/${position}`, {
    onError: (errors) => {
        // errors: Record<string, string[] | string>
        console.log(errors.emoji); // Field-specific errors
    },
});
```

## Advantages of useForm Hook

✅ **CSRF protection** - Automatic token handling  
✅ **Error management** - Built-in validation error tracking  
✅ **Loading states** - `form.processing` for UI feedback  
✅ **State tracking** - `isDirty`, `wasSuccessful`, etc.  
✅ **Form reset** - Easy state management  
✅ **Callbacks** - `onBefore`, `onStart`, `onSuccess`, `onError`, `onFinish`  
✅ **Type safety** - Full TypeScript support

## Example Flow

```
User clicks cell
    ↓
handleCellClick() called
    ↓
Optimistic UI update (setCells)
    ↓
form.submit() triggered
    ↓
form.put() sends PUT request with:
    ✓ CSRF token (automatic)
    ✓ form.data.emoji
    ✓ form.processing = true (disables button)
    ↓
Server validates and responds
    ↓
If valid:
    ✓ onSuccess callback
    ✓ form.wasSuccessful = true
    ✓ form.processing = false
    ✓ GridCellUpdated event broadcasts

If invalid:
    ✓ onError callback
    ✓ Rollback optimistic update
    ✓ Show validation errors
```

## Form Data Structure

The form submits with the field name `emoji`:

```php
// Laravel receives:
$validated = $request->validate([
    'emoji' => 'required|string|in:🚀,❤️,🤯,🔥',
]);
```

## Loading State UI

The button is disabled during submission:

```typescript
disabled={form.processing}
className="disabled:opacity-50"
```

This provides visual feedback to the user.

## Error Recovery

If validation fails:

```typescript
onError: (errors) => {
    // Revert optimistic UI update
    setCells((prev) => {
        const updated = { ...prev };
        delete updated[position];
        return updated;
    });
};
```

The cell reverts to its previous state if the submission fails.

## TypeScript Types

```typescript
// Form data type
interface CellFormProps {
    position: number;
    emoji: string | null | undefined;
    selectedEmoji: string;
    onCellClick: () => void;
    onError: (errors: Record<string, any>) => void;
}
```

## Benefits Over Raw Fetch

| Feature           | useForm            | Raw Fetch  |
| ----------------- | ------------------ | ---------- |
| CSRF token        | ✅ Automatic       | ❌ Manual  |
| Validation errors | ✅ Built-in        | ❌ Manual  |
| Loading state     | ✅ form.processing | ❌ Manual  |
| Error handling    | ✅ Structured      | ❌ Manual  |
| Type safety       | ✅ Full TS         | ⚠️ Partial |
| Code clarity      | ✅ Clear           | ❌ Verbose |

## Documentation References

- [Inertia useForm Hook](https://inertiajs.com/forms)
- [Inertia React Docs](https://inertiajs.com/client-side-setup#choosing-your-framework)
- [Form Submission Methods](https://inertiajs.com/forms#form-helper-methods)

---

**Status**: ✅ Using Inertia v2 useForm Hook  
**Best Practice**: ✅ Following Inertia conventions  
**CSRF Protected**: ✅ Automatic token handling
