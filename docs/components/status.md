# Status

`<x-status>` is a self-contained flash-message component for the Redot Dashboard. It inspects the current session for any of the standard status keys and, if one is present, renders it as a dismissible [Alert](/components/alert). It takes no attributes in normal use — drop it into a layout and it does the rest.

## What it is

`App\View\Components\Status` is a class-based Blade component backed by the view `resources/components/status.blade.php`. On every render it scans the session for the keys `success`, `error`, `warning`, and `info` (in that order). The first key that exists becomes the alert's `type`, and the flash value is rendered inside an `<x-alert>`. If none of those keys is present, the component renders nothing (empty string), so it is safe to place unconditionally in shared layouts.

## Props / API

The component is class-based (`App\View\Components\Status`) and its constructor takes **no arguments** — there is nothing to pass when you use the tag.

| Property | Type | Default | Notes |
| --- | --- | --- | --- |
| `$status` | `?string` | `null` | Set internally during `render()` to the first matching session key. Becomes the alert `type`. |
| `$color` | `?string` | `null` | Declared on the class but not consumed by the view. |

Recognized status keys (the `STATUS` constant), checked in this order:

```php
const STATUS = ['success', 'error', 'warning', 'info'];
```

Any extra HTML attributes you pass on the tag are forwarded to the underlying `<x-alert>` via <span v-pre>`{{ $attributes }}`</span>.

### View behavior

```blade
<x-alert :type="$status" :dismissible="true" {{ $attributes }}>
    @if (is_array(session($status)))
        <ul class="mb-0">
            @foreach (session($status) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @else
        {{ session($status) }}
    @endif
</x-alert>
```

Key points:

- The wrapped alert is always **dismissible** (`:dismissible="true"`), so it renders the `btn-close` element with `data-bs-dismiss="alert"`.
- If the flashed value is an **array**, each entry is listed in a `<ul class="mb-0">` (handy for validation-style message bags). Otherwise the scalar value is printed directly.
- The alert `type` maps to a Tabler/Bootstrap alert color; note that `error` is rendered as `alert-danger` and gets the `fa-exclamation-circle` icon (see [Alert](/components/alert) for the full type/icon map). Only the four keys in `STATUS` are matched, so `error` is supported here even though `<x-alert>` itself would otherwise fall back to `success` for unknown types.

There are **no slots** — content comes entirely from the session, not from the tag body.

## Usage

The component is rendered once inside each dashboard layout and once at the top of the website auth/profile pages. You normally never pass anything to it; you just flash a session key from a controller or redirect.

Inside the dashboard layout (`resources/layouts/dashboard/base.blade.php`):

```blade
<div class="page-body">
    <div class="container-fluid">
        <x-status />

        {{ $slot }}
    </div>
</div>
```

In the auth layout (`resources/layouts/dashboard/auth.blade.php`) and on website pages such as `resources/views/website/auth/login.blade.php`:

```blade
<x-status />
```

To trigger a message, flash one of the recognized keys before redirecting:

```php
return redirect()
    ->route('login')
    ->with('success', 'Your password has been reset.');

// An array value renders as a bulleted list:
return back()->with('error', [
    'The email is invalid.',
    'The token has expired.',
]);
```

## Gotchas

- Only the keys `success`, `error`, `warning`, and `info` are detected. Flashing any other key (e.g. `status`, `message`) renders nothing.
- Order matters: if multiple recognized keys are flashed at once, only the **first** in `STATUS` order is shown.
- The `$color` property exists on the class but is unused by the view; do not rely on it.
- Because it returns an empty string when no key is set, it is safe to place in a shared layout without an `@if (session()->has(...))` guard.

## Related

- [Alert component](/components/alert) — the underlying presentation component, including the type-to-color/icon mapping and dismiss behavior.
