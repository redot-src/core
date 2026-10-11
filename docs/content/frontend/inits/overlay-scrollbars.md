# Overlay Scrollbars Initializer

`overlay-scrollbars` replaces an element's native scrollbars with slim, auto-hiding overlay scrollbars (powered by [OverlayScrollbars](https://kingsora.github.io/OverlayScrollbars/)). The scaffold layout already applies it to the page body and the dashboard sidebar; use it on any other scrollable panel to match.

## Enable it

Mark the scrollable element with `init="overlay-scrollbars"` and `data-overlayscrollbars-initialize`:

```blade
<div class="card-body" style="max-height: 320px" init="overlay-scrollbars" data-overlayscrollbars-initialize>
    {{-- long content --}}
</div>
```

The `data-overlayscrollbars-initialize` attribute hides the native scrollbars before the script runs, so the page doesn't flash them on load. The OverlayScrollbars script and styles are already loaded by the scaffold layout.

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Set any [OverlayScrollbars option](https://kingsora.github.io/OverlayScrollbars/#options) as an `os-` attribute, using dots for nested keys (e.g. `os-overflow.x="hidden"`). A few extras are supported too:

- **`os-media`** — only use overlay scrollbars while this media query matches, e.g. `(min-width: 992px)`. Below it, the element falls back to native scrolling.
- **`os-viewport`** — selector for the element that actually scrolls, or `self` for the element itself.
- **`os-slot`** — selector for the element the scrollbars are placed in, or `self`.
- **`os-overflow.x`** / **`os-overflow.y`** — scroll behavior per axis. Vertical scrolling is on by default.

Scrollbars hide when the pointer leaves the element and follow the light/dark theme: dark containers get light scrollbars and vice versa. Reach the instance with `OverlayScrollbarsGlobal.OverlayScrollbars(element)`.

## Examples

### Desktop-only scrolling for a side panel

```blade
<aside init="overlay-scrollbars" os-media="(min-width: 992px)" os-overflow.x="hidden" data-overlayscrollbars-initialize>
    {{-- panel content --}}
</aside>
```

## Related

- [Theming](/frontend/theming) — the light/dark mode the scrollbars follow.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
