# Inertia Form Updates - Using router.put()

## What Changed

Updated the Grid component to use **Inertia's `router.put()` method** instead of raw fetch requests. This follows Inertia v2 best practices for handling form submissions.

## Key Changes

### Before (Raw Fetch)

```typescript
await fetch(`/grid/${position}`, {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify({ emoji: selectedEmoji }),
});
```

### After (Inertia Router)

```typescript
import { router } from '@inertiajs/react';

router.put(
    `/grid/${position}`,
    { emoji: selectedEmoji },
    {
        onError: (errors) => {
            console.error('Validation errors:', errors);
            // Handle errors
        },
        onFinish: () => {
            // Cleanup
        },
    },
);
```

## Benefits

✅ **CSRF Protection**: Automatically handled by Inertia  
✅ **Validation Errors**: Built-in error handling  
✅ **Loading State**: Can track processing status  
✅ **Type Safety**: Proper TypeScript support  
✅ **Consistent**: Follows Inertia conventions

## Updated Component Features

### Processing State

```typescript
const [processing, setProcessing] = useState(false);

// Disable UI during submission
<select disabled={processing}>
<button disabled={processing}>
```

### Error Handling

```typescript
onError: (errors) => {
    console.error('Validation errors:', errors);
    // Revert optimistic update
    setCells((prev) => {
        const updated = { ...prev };
        delete updated[position];
        return updated;
    });
};
```

### Submission Lifecycle

```typescript
router.put(
    '/grid/{position}',
    { emoji: selectedEmoji },
    {
        onStart: () => {
            /* Called when request starts */
        },
        onSuccess: () => {
            /* Called on success */
        },
        onError: (errors) => {
            /* Called on validation error */
        },
        onProgress: (progress) => {
            /* Track upload progress */
        },
        onFinish: () => {
            /* Always called at end */
        },
    },
);
```

## Router Methods Available

All HTTP methods are supported:

```typescript
router.get(url, options);
router.post(url, data, options);
router.put(url, data, options); // Our usage
router.patch(url, data, options);
router.delete(url, options);
```

## Type-Safe Error Handling

Validation errors come through structured and can be accessed:

```typescript
onError: (errors) => {
    // errors is: Record<string, string[] | string>
    console.log(errors.emoji); // Specific field errors
};
```

## For More Complex Forms

If you need more form functionality, use the `useForm()` hook:

```typescript
import { useForm } from '@inertiajs/react';

const form = useForm({
    emoji: '🚀',
});

form.post('/grid/{position}', {
    onSuccess: () => console.log('Saved!'),
});
```

Available form methods:

- `form.data` - Current form data
- `form.errors` - Validation errors
- `form.processing` - Whether request is in progress
- `form.isDirty` - Whether form has changes
- `form.wasSuccessful` - Whether last submission succeeded
- `form.reset()` - Reset form to initial state
- `form.submit()` / `form.post()` / `form.put()` - Submit form

## Documentation References

- [Inertia v2 Router](https://inertiajs.com/routing)
- [Inertia Form Helper](https://inertiajs.com/forms)
- [Inertia React Documentation](https://inertiajs.com/client-side-setup#choosing-your-framework)

## Testing the Changes

1. Start the app: `composer run dev`
2. Open http://localhost:8000
3. Select an emoji
4. Click a cell
5. Check browser console for validation errors if any
6. Emoji should appear in real-time via Reverb broadcast

## Common Issues Resolved

| Issue           | Resolution                                    |
| --------------- | --------------------------------------------- |
| 400 errors      | Inertia router handles CSRF automatically     |
| 345 errors      | Proper content-type headers managed by router |
| Form validation | Errors accessible via onError callback        |
| Loading states  | Use `processing` state during submission      |

---

**Status**: ✅ Updated  
**Follows**: Inertia v2 best practices  
**Framework**: Laravel 12 + Inertia React v2
