# Alert

`<x-alert>` renders a contextual alert box with an icon, optional title and
description, and an optional dismiss button. Pick a semantic `type` and it
chooses a matching colour and default icon for you.

## Usage

```blade
<x-alert type="info">
    {{ __('This token has been modified.') }}
</x-alert>
```

The body can be a slot (as above) or the `title` / `description` attributes.

## Options

- **`type`** — semantic variant: `success` (default), `error`, `warning`, or
  `info`. Sets the colour and the default icon.
- **`title`** — heading text. On its own it's the alert body; with `description`
  it becomes a bold title above the description.
- **`description`** — secondary text shown below the title.
- **`icon`** — override the default icon. Pass `:icon="false"` to show no icon.
- **`dismissible`** — add a close button so the user can dismiss the alert.

## Examples

### Alert wrapping a list, with no icon

```blade
<x-alert type="info" :icon="false">
    <ul class="m-0">
        <li>{{ __(':count token(s) need to be published.', ['count' => $count]) }}</li>
    </ul>
</x-alert>
```

### Titled alert with a description

```blade
<x-alert type="success" :title="__('Saved')" :description="__('Your changes have been stored.')" />
```

### Dismissible alert from a flash message

```blade
<x-alert :type="$status" :dismissible="true">
    {{ session($status) }}
</x-alert>
```

## Related

- [Status](/components/status) — renders dismissible alerts straight from flash data.
- [Icon](/components/icon) — used for the alert icon.
