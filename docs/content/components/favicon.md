# Favicon

`<x-favicon>` renders the application icon as an image. It automatically swaps between the light- and dark-theme versions to match the active theme. Use it where the full [logo](/components/logo) is too wide, such as a compact sidebar mark or an avatar-sized brand badge.

## Usage

```blade
<x-favicon />
```

The images come from the `app_icon_dark` and `app_icon_light` settings — the same icons the layout uses for the browser tab. See [Settings](/foundation/settings).

## Options

- **`lazy`** — defer loading of the images (`true` by default). Set `:lazy="false"` where the icon must appear immediately.

Extra classes and attributes you pass apply to the icon images.

## Examples

### Small brand mark next to a title

```blade
<div class="d-flex align-items-center gap-2">
    <x-favicon class="avatar avatar-sm" :lazy="false" />
    <span class="fw-bold">{{ app_name() }}</span>
</div>
```

## Related

- [Logo](/components/logo) — the full application logo.
- [Settings](/foundation/settings) — where the icons are configured.
