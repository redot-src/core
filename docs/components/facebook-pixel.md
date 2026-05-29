# Facebook Pixel

The Facebook Pixel partial injects Meta's Pixel base code (`fbevents.js`) into the website layout when a Pixel ID has been configured in settings. It tracks a `PageView` on every render and degrades gracefully for users with JavaScript disabled.

## What it is

This is **not** a tag-style Blade component (`<x-facebook-pixel>`) and it has **no PHP class** in `app/View/Components`. It is a plain Blade view rendered via `@include`:

- View: `/home/abdelrhman/projects/dashboard/resources/components/facebook-pixel.blade.php`

Because it is an include-only partial, it exposes **no props, attributes, slots, or `wire:model` bindings**. Its entire behavior is driven by a single application setting.

## Configuration

The partial is gated and configured by one setting, read through the `setting()` helper:

| Setting key | Used for | Behavior when empty |
| --- | --- | --- |
| `facebook_pixel_id` | The Meta Pixel ID passed to `fbq('init', ...)` and the `<noscript>` tracking image | The entire block (script + noscript) is **not rendered** |

The whole output is wrapped in `@pushIf(setting('facebook_pixel_id'), 'scripts')`, so:

- Nothing is emitted at all unless `facebook_pixel_id` has a truthy value.
- When rendered, the markup is pushed onto the `scripts` stack, which the scaffold layout outputs via `@stack('scripts')` (see `resources/layouts/scaffold.blade.php`).

## What it renders

When `facebook_pixel_id` is set, the partial pushes two pieces onto the `scripts` stack:

1. A `<script>` block that:
   - Loads `https://connect.facebook.net/en_US/fbevents.js` and defines the global `fbq` / `_fbq` functions.
   - Calls <span v-pre>`fbq('init', '{{ setting('facebook_pixel_id') }}')`</span>.
   - Calls `fbq('track', 'PageView')`.
2. A `<noscript>` fallback with a 1x1 tracking pixel image:
   - <span v-pre>`https://www.facebook.com/tr?id={{ setting('facebook_pixel_id') }}&ev=PageView&noscript=1`</span>

This is Meta's standard Pixel base code; the only dynamic value is the Pixel ID.

## Usage

Include the partial inside a layout. It is wired into the website base layout alongside the Google Analytics partial:

```blade
{{-- resources/layouts/website/base.blade.php --}}
@include('components.google-analytics')
@include('components.facebook-pixel')

@pushOnce('scripts')
    <script src="{{ hashed_asset('assets/js/website.js') }}"></script>

    {!! setting('body_code') !!}
@endPushOnce
```

To enable tracking, set `facebook_pixel_id` in the application settings; no code change is required to toggle it on or off.

## Gotchas

- **No `<x-facebook-pixel>` tag.** Use `@include('components.facebook-pixel')`, not a component tag. There is no backing component class.
- **Pushes to the `scripts` stack.** The host layout must render `@stack('scripts')` (the scaffold layout does) or nothing will appear.
- **Renders nothing when unset.** With an empty `facebook_pixel_id`, the `@pushIf` guard skips the entire block, including the `<noscript>` pixel.
- **Always fires `PageView`.** Only the standard `PageView` event is tracked; custom events would need to be added separately.
