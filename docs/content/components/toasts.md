# Toasts

`<x-toasts>` loads the toast notification scripts and shows any toasts flashed to the session. Render it once per page so toasts from [Toastify](/packages/toastify) appear.

## Usage

```blade
<x-toasts />
```

The scaffold layout already includes it, so dashboard and website pages show toasts out of the box. Add it yourself only in a custom layout, just before `</body>`.

## Options

This component has no options. Toast types and their styling come from the [Toastify configuration](/packages/toastify).

## Examples

### Custom layout

```blade
<body>
    {{ $slot }}

    <x-toasts />
</body>
```

Then flash a toast from a controller:

```php
toastify()->success(__('Saved successfully'));
```

## Related

- [Toastify](/packages/toastify) — flashing toasts from PHP, Livewire, and JavaScript, and configuring them.
- [Layouts](/layouts/overview) — the layouts that already include this component.
