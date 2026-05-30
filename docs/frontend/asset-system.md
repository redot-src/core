# Asset & Init System

The dashboard frontend is shipped as static assets under `public/assets` — there
is no Vite/Webpack build for the platform layer. A small artisan step compiles
per-feature "init" scripts and translations, and a runtime `init()` bootstrap
attaches them to any element that carries an `init="..."` attribute. This page is
the canonical reference for how JavaScript widgets get wired up.

## Enabling a widget with `init`

You rarely call JavaScript directly. Instead you mark an element with an `init`
attribute naming the initializer to run, and the bootstrap does the rest:

```blade
<input type="text" name="color" init="coloris" />
```

- The `init` value is **space-separated**, so one element can run several:
  `init="tomselect sortable"`.
- Pass options to an initializer through attributes prefixed with its name, e.g.
  `sortable-animation="300"` for the `sortable` init. Each init's page lists the
  options it reads.
- Initialization runs automatically on page load **and** whenever new markup is
  added to the page (Livewire/AJAX content included) — you never call it manually.

Most of the time you don't even write the `init` attribute yourself: the
JS-backed [components](/components/overview) (date picker, select, rich editor,
uploader, …) add it for you.

## Available initializers

Each has its own page with the options it accepts:

- [Coloris](/frontend/inits/coloris) — colour picker
- [Icon Picker](/frontend/inits/icon-picker)
- [Query Builder](/frontend/inits/query-builder)
- [Repeater](/frontend/inits/repeater)
- [Sortable](/frontend/inits/sortable) — drag-to-reorder
- [Tempus Dominus](/frontend/inits/tempus-dominus) — date/time picker
- [TinyMCE](/frontend/inits/tinymce) — rich text editor
- [Tom Select](/frontend/inits/tomselect) — enhanced selects
- [Turnstile](/frontend/inits/turnstile) — Cloudflare captcha
- [Uploader](/frontend/inits/uploader)

## Cache-busting assets

Reference any file under `public/` with the [`hashed_asset()`](/foundation/helpers)
helper instead of `asset()`. It appends a version query string that changes
whenever the file changes, so browsers always pick up edits:

```blade
<link rel="stylesheet" href="{{ hashed_asset('assets/css/app.css') }}">
<script src="{{ hashed_asset('assets/js/app.js') }}"></script>
```

## Automatic rebuilds

The compiled output in `public/assets/dist` is **generated, never edited by
hand**. In development you don't run a build step: a middleware checks whether any
source init script or translation file has changed and regenerates `dist/` on the
next request. To force a rebuild manually, run:

```bash
php artisan dependencies:build
```

## Adding a new initializer

1. Create `public/assets/inits/my-widget.js` whose last statement returns the
   initializer function:

   ```js
   return (selector, options = {}) => {
       const instance = new MyWidget($(selector).get(0), options);
       $(selector).data('myWidget', instance);
   };
   ```

2. Use it from Blade: `<div init="my-widget" mywidget-animation="300"></div>`.
3. The next request rebuilds `dist/` and your initializer is live as
   `init="my-widget"`.

## Gotchas

- **Never edit `public/assets/dist/*` by hand** — it is regenerated. Edit the
  source `public/assets/inits/*.js` and `lang/*` files instead.
- An element is initialized only once. If you re-render it and need it
  re-initialized, render fresh markup rather than reusing an already-initialized
  element.

## Related

- [Components overview](/components/overview) — components that use `init` for you.
- [JS Translations](/frontend/translations) — the `__()` helper for localized JS strings.
- [Helpers](/foundation/helpers) — `hashed_asset()` and related helpers.
