# Social Icon

`<x-social-icon>` renders a branded social-platform glyph (Google, GitHub, X,
…). It's presentation-only and pulls in its own stylesheet on first use.

## Usage

```blade
<x-social-icon social="github" size="lg" />
```

## Options

- **`social`** — platform key (`google` by default). Supported keys: `apple`,
  `discord`, `dribbble`, `facebook`, `figma`, `github`, `google`, `instagram`,
  `linkedin`, `medium`, `meta`, `metamask`, `pinterest`, `reddit`, `signal`,
  `skype`, `snapchat`, `spotify`, `telegram`, `tiktok`, `tumblr`, `twitch`,
  `vk`, `x`, `youtube`. An unknown key renders an empty glyph.
- **`size`** — icon size: `xs`, `sm`, `md` (default), `lg`, `xl`.

Extra classes and attributes are merged onto the element.

## Examples

### Icon paired with a button label

```blade
<a href="{{ $url }}" class="btn">
    <x-social-icon social="x" size="sm" class="me-2" />
    {{ __('Continue with X') }}
</a>
```

## Related

- [Icon](/components/icon) — generic icon-font / SVG rendering.
