# Status

`<x-status>` renders flash messages as a dismissible [Alert](/components/alert).
It reads the session for you, so you drop it into a layout and just flash a
session key from your controllers.

## Usage

```blade
<x-status />
```

It checks the session for `success`, `error`, `warning`, and `info` (in that
order) and renders the first one it finds. If none is present it renders
nothing, so it's safe to place unconditionally in a shared layout.

To trigger a message, flash one of those keys before redirecting:

```php
return redirect()
    ->route('posts.index')
    ->with('success', __('The post has been saved.'));
```

## Options

`<x-status>` takes no attributes in normal use. Any HTML attribute you do pass is
forwarded to the underlying alert.

## Examples

### Flashing a list of messages

Flash an array and each entry is rendered as a bullet point — handy for
validation-style message bags:

```php
return back()->with('error', [
    __('The title is required.'),
    __('The body is too long.'),
]);
```

## Related

- [Alert](/components/alert) — the underlying alert, including type-to-color mapping.
