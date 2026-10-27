# Asset & Init System

The dashboard frontend is shipped as static assets — there is no Vite/Webpack build for the platform layer. The shared runtime (`functions.js`, `themer.js`, the `redot-*` plugins, and `redot.css`) ships with `redot/core`, together with the third-party libraries it relies on, and `php artisan redot:publish` copies all of it to `public/vendor/redot`. Your application's own assets live under `public/assets`. A small artisan step compiles per-feature "init" scripts and translations, and a runtime `init()` bootstrap attaches them to any element that carries an `init="..."` attribute. This page is the canonical reference for how JavaScript widgets get wired up.

## Enabling a widget with `init`

You rarely call JavaScript directly. Instead you mark an element with an `init` attribute naming the initializer to run, and the bootstrap does the rest:

```blade
<input type="text" name="color" init="coloris" />
```

- The `init` value is **space-separated**, so one element can run several: `init="tomselect sortable"`.
- Pass options to an initializer through attributes prefixed with its name, e.g. `sortable-animation="300"` for the `sortable` init. Each init's page lists the options it reads.
- Initialization runs automatically on page load **and** whenever new markup is added to the page (Livewire/AJAX content included) — you never call it manually.

Most of the time you don't even write the `init` attribute yourself: the JS-backed [components](/components/overview) (date picker, select, rich editor, uploader, …) add it for you.

## Available initializers

Each has its own page with the options it accepts:

- [Apex Chart](/frontend/inits/apex-chart) — charts
- [Coloris](/frontend/inits/coloris) — colour picker
- [HugeRTE](/frontend/inits/hugerte) — rich text editor
- [Icon Picker](/frontend/inits/icon-picker)
- [Overlay Scrollbars](/frontend/inits/overlay-scrollbars) — slim overlay scrollbars
- [Query Builder](/frontend/inits/query-builder)
- [Repeater](/frontend/inits/repeater)
- [Sortable](/frontend/inits/sortable) — drag-to-reorder
- [Tempus Dominus](/frontend/inits/tempus-dominus) — date/time picker
- [Tom Select](/frontend/inits/tomselect) — enhanced selects
- [Turnstile](/frontend/inits/turnstile) — Cloudflare captcha
- [Uploader](/frontend/inits/uploader)

## Loading the runtime

A layout loads the whole shared runtime with two components, so the list of libraries and their order stays with `redot/core` and updates with it:

```blade
<head>
    ...
    <x-redot-styles :direction="$direction" />
    <link rel="stylesheet" href="{{ hashed_asset('/assets/css/app.css') }}" />
</head>

<body>
    {{ $slot }}

    <x-redot-scripts />
    <script src="{{ hashed_asset('/assets/js/app.js') }}"></script>

    @stack('plugins-scripts')
    @stack('scripts')
</body>
```

- **`<x-redot-styles>`** — Tabler (in the given `direction`, defaulting to the current language's), Tabler Icons, Glare, OverlayScrollbars, the `plugins-styles` stack, and `redot.css`. Load your application CSS right after it so your rules win.
- **`<x-redot-scripts>`** — jQuery, Lodash, Tabler, `functions.js`, the bundled plugins, the translations and initializer bundles, the runtime (`runtime.js`: running initializers as markup appears, client-side form validation, the CSRF header and session-expiry dialog for AJAX, lightbox links, and Bootbox defaults), toasts, and the restoring of submitted input and validation errors. Load your application script right after it; `app.js` is yours to fill.

The scaffold layout already uses both. To change what they load, [override](/components/overview) `resources/components/redot-styles.blade.php` or `redot-scripts.blade.php`.

## Loading a widget's vendor files

Components load the third-party scripts and styles their widget needs. If you put an `init` attribute on your own markup instead, include the matching `<x-assets.*>` component so those files are on the page:

```blade
<x-assets.tom-select />

<select name="status" init="tomselect">
    <option value="draft">{{ __('Draft') }}</option>
    <option value="published">{{ __('Published') }}</option>
</select>
```

Each asset component adds its files only once per page, no matter how many times it's rendered. Available asset components:

- **`<x-assets.apexcharts />`** — ApexCharts.
- **`<x-assets.cloudflare-turnstile />`** — Cloudflare Turnstile and its site-key meta tag.
- **`<x-assets.coloris />`** — Coloris colour picker.
- **`<x-assets.hugerte />`** — HugeRTE rich-text editor.
- **`<x-assets.query-builder />`** — the query builder, plus Tom Select and Tempus Dominus.
- **`<x-assets.redot-icon-picker />`** — the icon picker plugin and its modal template.
- **`<x-assets.redot-repeater />`** — the repeater plugin.
- **`<x-assets.redot-toasts />`** — the toasts plugin.
- **`<x-assets.redot-uploader />`** — the uploader plugin.
- **`<x-assets.tabler-flags />`** — Tabler flag styles.
- **`<x-assets.tabler-socials />`** — Tabler social icon styles.
- **`<x-assets.tempus-dominus />`** — Tempus Dominus date picker.
- **`<x-assets.tom-select />`** — Tom Select.

## Cache-busting assets

Reference any file under `public/` with the [`hashed_asset()`](/foundation/helpers) helper instead of `asset()`. It appends a version query string that changes whenever the file changes, so browsers always pick up edits:

```blade
<link rel="stylesheet" href="{{ hashed_asset('assets/css/app.css') }}">
<script src="{{ hashed_asset('assets/js/app.js') }}"></script>
```

## Automatic rebuilds

The compiled output in `public/assets/dist` is **generated, never edited by hand**. In development you don't run a build step: a middleware checks whether any source init script or translation file has changed and regenerates `dist/` on the next request. To force a rebuild manually, run:

```bash
php artisan dependencies:build
```

## Adding a new initializer

1. Create `public/assets/inits/my-widget.js` whose last statement returns the initializer function:

   ```js
   return (selector, options = {}) => {
       const instance = new MyWidget($(selector).get(0), options);
       $(selector).data('myWidget', instance);
   };
   ```

2. Use it from Blade: `<div init="my-widget" mywidget-animation="300"></div>`.
3. The next request rebuilds `dist/` and your initializer is live as `init="my-widget"`.

The built-in initializers ship with `redot/core`. To change one, add a file with the same name to `public/assets/inits` (for example `public/assets/inits/tomselect.js`); it replaces the package version in the build.

## Gotchas

- **Never edit `public/assets/dist/*` by hand** — it is regenerated. Edit the source `public/assets/inits/*.js` and `lang/*` files instead.
- **Never edit `public/vendor/redot/*`** — `redot:publish` replaces it on every `composer update`. Override a component or initializer in your application instead.
- An element is initialized only once. If you re-render it and need it re-initialized, render fresh markup rather than reusing an already-initialized element.

## Related

- [Components overview](/components/overview) — components that use `init` for you.
- [JS Translations](/frontend/translations) — the `__()` helper for localized JS strings.
- [Helpers](/foundation/helpers) — `hashed_asset()` and related helpers.
