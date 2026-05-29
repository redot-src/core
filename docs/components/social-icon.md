# Social Icon

`<x-social-icon>` renders a branded social-platform glyph (Google, GitHub, X, etc.) as a single `<span>`, styled by the Tabler "socials" stylesheet. It is an anonymous Blade component — there is no backing PHP class, so its props are declared with `@props`.

## What it is

The component outputs one element:

```blade
<span class="social social-{size} social-app-{social}"></span>
```

The visual icon comes entirely from CSS (background/mask rules in `tabler-socials.min.css`); the component itself emits no SVG or `<img>`. The stylesheet is registered once per request via `@pushOnce('plugins-styles', 'tabler-socials-styles')`, so it is injected only the first time any `<x-social-icon>` appears on the page no matter how many you render.

## Props

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `social` | string | `'google'` | Platform key. Becomes the `social-app-{social}` class. |
| `size` | string | `'md'` | Size token. Becomes the `social-{size}` class. |

Both props are interpolated directly into class names, so the value must match a class that exists in the vendor stylesheet.

### Supported `social` values

These `social-app-*` keys ship in `tabler-socials.min.css`:

`apple`, `discord`, `dribbble`, `facebook`, `figma`, `github`, `google`, `instagram`, `linkedin`, `medium`, `meta`, `metamask`, `pinterest`, `reddit`, `signal`, `skype`, `snapchat`, `spotify`, `telegram`, `tiktok`, `tumblr`, `twitch`, `vk`, `x`, `youtube`.

A value outside this list still renders the `social` base element but with no platform glyph.

### Supported `size` values

`xs`, `sm`, `md` (default), `lg`, `xl`.

## Attributes and slots

- The component has **no slot** — the `<span>` is self-closing in output and ignores any content you nest inside it.
- All extra HTML attributes are forwarded to the `<span>` via `$attributes->class([...])`. Any classes you pass are merged with the three generated classes (`social`, `social-{size}`, `social-app-{social}`). You can also pass `id`, `title`, `wire:*`, Alpine `x-*`, etc.; they land on the same `<span>`.
- There is no built-in `wire:model` or Livewire binding — it is purely presentational.

## Usage

Default (Google, medium size):

```blade
<x-social-icon />
```

Pick a platform and size:

```blade
<x-social-icon social="github" size="lg" />
```

Common pattern — pair the icon with a link or button label:

```blade
<a href="{{ $url }}" class="btn">
    <x-social-icon social="x" size="sm" class="me-2" />
    Continue with X
</a>
```

Render several at once; the stylesheet is still injected only once:

```blade
<x-social-icon social="facebook" />
<x-social-icon social="linkedin" />
<x-social-icon social="instagram" />
```

## Gotchas

- **Asset hashing**: the stylesheet href is resolved through `hashed_asset('/vendor/tabler/css/tabler-socials.min.css')`. Ensure that vendor CSS is published to `public/vendor/tabler/css/` or the icons will be unstyled.
- **Stack rendering**: the styles are pushed to the `plugins-styles` stack. The icons only appear if your layout actually renders `@stack('plugins-styles')` in the `<head>`.
- **No fallback glyph**: an unknown `social` value silently produces an empty styled span. Validate the key against the supported list above.
- **Size is unvalidated**: passing an unsupported `size` yields a `social-{size}` class with no matching rule, so the element falls back to whatever the base `social` class defines.
