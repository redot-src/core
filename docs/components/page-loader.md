# Page Loader

`<x-page-loader>` renders a full-screen splash overlay shown while the page boots. It displays the app logo and a Bootstrap spinner, then fades itself out on the browser `load` event.

## What it is

The page loader is an **anonymous Blade component** backed solely by `resources/components/page-loader.blade.php`. There is no `app/View/Components/PageLoader.php` class, and the view declares no `@props` — so the component takes **no props or attributes**. Anything you pass would not be forwarded anywhere.

It paints a fixed, full-viewport overlay (`.page-loader`) on top of everything else (`z-index: 9999`) using the theme body background, centers the logo and spinner, and removes itself once all page assets have loaded.

## Markup

```blade
<div class="page page-loader">
    <div class="container container-slim text-center py-4 my-auto">
        <div><x-logo class="mb-4" :lazy="false" /></div>
        <div class="spinner-border"></div>
    </div>
</div>

@push('scripts')
    <script>
        $(window).on('load', () => $('.page-loader').fadeOut());
    </script>
@endpush
```

- **Logo** — renders [`<x-logo>`](/components/logo) with `:lazy="false"` so the splash logo is loaded immediately rather than lazily.
- **Spinner** — a Tabler/Bootstrap `.spinner-border`.
- **Fade-out script** — pushed to the `scripts` stack. It uses jQuery: on `window` `load` it calls `fadeOut()` on the `.page-loader` element. This depends on jQuery (`$`) being available globally.

## Styling

The overlay positioning comes from `public/assets/css/app.css`:

```css
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--tblr-body-bg);
    z-index: 9999;
}
```

## Behavior / lifecycle

1. The overlay is rendered server-side as part of the page.
2. Because it is `position: fixed` with a high `z-index` and an opaque background, it covers the page content while assets download.
3. When the browser fires `window` `load` (all images, scripts, and stylesheets finished), the inline script fades the overlay out.

There is no Livewire / `wire:model` binding involved — it is purely static markup plus a jQuery fade.

## Usage

The component is wired into the dashboard base layout and is gated behind a setting, so it only renders when the `page_loader_enabled` setting is on. From `resources/layouts/dashboard/base.blade.php`:

```blade
@if (setting('page_loader_enabled'))
    <x-page-loader />
@endif
```

To use it directly elsewhere, just drop the tag in — no attributes are needed:

```blade
<x-page-loader />
```

## Gotchas

- **No configurable props.** Logo, spinner, and styling are fixed in the view. To customize, edit the Blade view or the `.page-loader` CSS rather than passing attributes.
- **Requires jQuery.** The fade-out relies on the global `$`. Without jQuery loaded, the overlay will never disappear.
- **Gated by a setting.** In the shipped dashboard layout it only appears when `setting('page_loader_enabled')` is truthy.
- **Stays on `load`, not DOMContentLoaded.** It waits for full asset load, so slow third-party assets keep the splash visible longer.

## Related

- [Logo component](/components/logo) — rendered inside the loader.
