# Asset & Init System

The Redot Dashboard ships its frontend as **committed, hand-authored static assets** under `public/assets` — no Vite/Webpack bundling for the platform layer. A small artisan build step compiles per-feature init scripts and translations into the `public/assets/dist` directory, and a runtime `init()` bootstrap binds those inits to DOM elements that carry an `init="..."` attribute. This page explains how the assets are loaded, how the `dist/` files are generated, and the global JS objects and helpers the platform exposes.

## How assets are loaded

Assets are referenced from the Blade layouts with the core [`hashed_asset()` helper](/foundation/helpers), which appends a cache-busting `?v=<md5(filemtime)>` query string so the browser refetches a file whenever its mtime changes:

```php
// vendor/redot/core/src/helpers.php
function hashed_asset(string $path, ?bool $secure = null): string
{
    $hash = null;
    $realPath = public_path($path);

    if (file_exists($realPath)) {
        $hash = md5(filemtime($realPath));
    }

    return asset($path, $secure) . ($hash ? '?v=' . $hash : '');
}
```

The companion [`dist_path()` helper](/foundation/helpers) resolves the build output directory: `dist_path()` → `public_path('assets/dist')`, and `dist_path('translations')` / `dist_path('lock.json')` for sub-paths.

### Load order in `resources/layouts/scaffold.blade.php`

The root scaffold layout wires everything together. Styles are loaded in `<head>`, scripts at the end of `<body>`:

```blade
{{-- Core --}}
<script src="{{ hashed_asset('/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/lodash/lodash.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/tabler/js/tabler.min.js') }}"></script>
<script src="{{ hashed_asset('/assets/js/functions.js') }}"></script>

{{-- Plugins --}}
<script src="{{ hashed_asset('/vendor/jquery-confirm/jquery-confirm.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/fancybox/fancybox.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/sortable/sortable.min.js') }}"></script>
<script src="{{ hashed_asset('/assets/plugins/redot-visibility.js') }}"></script>
<script src="{{ hashed_asset('/assets/plugins/redot-validator.js') }}"></script>
<script src="{{ hashed_asset('/assets/plugins/redot-validator-rules.js') }}"></script>

{{-- Language Script --}}
<script src="{{ hashed_asset('/assets/dist/translations/' . app()->getLocale() . '.js') }}"></script>

{{-- Custom Scripts --}}
<script src="{{ hashed_asset('/assets/dist/init.js') }}"></script>
<script src="{{ hashed_asset('/assets/js/app.js') }}"></script>
```

The ordering is load-bearing: jQuery and lodash (`_`) come first because every other script depends on the global `$` and `_`; `functions.js` defines the global helpers and the `init()` function; the `redot-*` plugins register their global classes; the locale script defines `window.__translations`; `dist/init.js` populates `window.__inits`; and finally `app.js` starts the MutationObserver and runs the first `init()` pass.

After the core scripts the layout also exposes Laravel state to JS:

```blade
<script>
    window.OldBag = _.toPlainObject(@json(old()));
    window.ErrorsBag = _.toPlainObject(@json(isset($errors) ? $errors->getMessages() : []));
</script>
```

The dashboard layout (`resources/layouts/dashboard/base.blade.php`) extends the scaffold and pushes its own dashboard-specific bundle:

```blade
<x-layouts::scaffold :title="$title" :inline="$inline" ...>
    @pushOnce('styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/dashboard.css') }}" />
    @endPushOnce

    {{-- ... page body ... --}}

    @pushOnce('scripts')
        <script src="{{ hashed_asset('assets/js/dashboard.js') }}"></script>
    @endPushOnce
</x-layouts::scaffold>
```

Stylesheets follow the same `hashed_asset()` pattern: vendor CSS (Tabler, FontAwesome, jQuery-Confirm, Fancybox), then `assets/css/app.css`, `themer.css`, and `overrides.css`, with `@stack('plugins-styles')` and `@stack('styles')` for per-page additions.

## The `dist/` build step

The files in `public/assets/dist/` are **generated**, not edited by hand — the directory's `.gitignore` ignores everything except itself. They are produced by the core artisan command `dependencies:build` (`Redot\Commands\BuildDependenciesCommand`):

- **`dist/init.js`** — concatenates every file in `public/assets/inits/*.js`, wrapping each one in an IIFE assigned to `window.__inits["<basename>"]`:

  ```php
  $javascriptFile = 'window.__inits = {};' . "\n";
  foreach ($inits as $file) {
      $javascriptFile .= sprintf(
          'window.__inits["%s"] = (() => { %s })();',
          basename($file, '.js'),
          File::get($file)
      );
  }
  ```

  So `public/assets/inits/tomselect.js` becomes `window.__inits["tomselect"]`, `coloris.js` becomes `window.__inits["coloris"]`, and so on. To add a new initiator you drop a `*.js` file into `public/assets/inits/` whose last statement `return`s a `(selector, options) => {}` function.

- **`dist/translations/<locale>.js`** — built from `lang/<locale>.json` plus the PHP files in `lang/<locale>/*.php`, flattened with `Arr::dot`. Each file sets `window.__locale` and `window.__translations`, which power the `__()` JS helper (below).

- **`dist/lock.json`** — records the `filemtime` of every source file and directory the build depends on (the lang dir, lang files, the `assets/inits` dir, and each init file).

### Automatic rebuilds

The `Redot\Http\Middleware\EnsureDependenciesBuilt` middleware reads `dist/lock.json` on each request. If the lock file is missing, or any tracked file/directory's `filemtime` no longer matches the recorded value, it calls `Artisan::call('dependencies:build')` to regenerate `dist/`. In practice this means editing an init script or a translation file transparently rebuilds `init.js` / the translation bundles on the next request — no manual build needed in development.

## `window.__inits` and the `init()` bootstrap

`window.__inits` is the registry of initiator functions, keyed by init name. Each entry is a function `(selector, options = {}) => { ... }` that wraps a vendor library and stores its instance on the element via jQuery `.data()`.

The `init()` function (in `functions.js`) is the dispatcher. It scans for elements with an `init` attribute that have not yet been initialized and runs each named initiator:

```js
function init(selector = 'body') {
    const $selector = $(selector).attr('init') ? $(selector).parent() : $(selector);

    $selector.find('[init]:not([initialized])').each(function () {
        let inits = $(this).attr('init').split(' ');

        $selector.trigger('init.before');

        for (const init of inits) {
            if (!window.__inits[init]) {
                console.error(`Initiator "${init}" is not defined.`);
                continue;
            }

            $selector.trigger(`init.before.${init}`);

            const options = this.hasAttribute(init) ? stringToPrimitive($(this).attr(init)) : {};
            window.__inits[init](this, options);

            $selector.trigger(`init.after.${init}`);
        }

        $selector.trigger('init.after');

        $(this).attr('initialized', true);
    });

    if (RedotVisibility.instance) RedotVisibility.instance.watch();
}
```

Key behaviours:

- The `init` attribute value is **space-separated** — an element can run multiple initiators (`init="tomselect sortable"`).
- Per-init options come from an attribute **named after the init** (e.g. `init="sortable" sortable='{"animation":300}'`), parsed by `stringToPrimitive()`.
- Once run, the element gets `initialized` so it is never re-initialized.
- Events fire around each pass: `init.before`, `init.before.<name>`, `init.after.<name>`, `init.after`.

### Auto-running on DOM changes

`app.js` runs the first `init()` on `$(document).ready` and installs a `MutationObserver` so dynamically inserted markup (e.g. Livewire/AJAX-loaded content) is initialized automatically:

```js
$(document).ready(() => {
    RedotVisibility.init();

    const observer = new MutationObserver(() => {
        window.init();
    });

    observer.observe(document.body, { childList: true, subtree: true });

    window.init();
});
```

### Per-initiator option attributes

Each initiator reads element attributes via `getOptionsFromSelector(selector, prefix)`, which collects all attributes starting with a prefix, strips it, camel-cases the remainder, and merges them over the defaults. The prefix differs per init:

| Init name (`window.__inits[...]`) | Wraps | Option attribute prefix | Notable defaults |
| --- | --- | --- | --- |
| `coloris` | Coloris | `coloris-` | `theme: 'polaroid'`, `formatToggle: true`, `rtl` from `<html dir>` |
| `icon-picker` | `RedotIconPicker` | `iconpicker-` | `endpoint: 'https://api.fontawesome.com'`, `version: '6.4.2'`, `maxResults: 100` |
| `query-builder` | jQuery QueryBuilder | `query-` | `allow_groups: 2`, `allow_empty: true`; also defines `$.fn.tomselect` / `$.fn.datepicker` |
| `repeater` | `RedotRepeater` | `repeater-` | `sortable: true`, `scrollable: true`, `confirmable: true` |
| `sortable` | SortableJS | `sortable-` | `animation: 150` |
| `tempus-dominus` | Tempus Dominus | `date-` | format `yyyy-MM-dd`; honors `datetime` / `only-time` attrs; reacts to `theme:changed` |
| `tinymce` | TinyMCE | (Object.assign, not prefix-based) | `height: 300`, `menubar: false`, uploads to `/tinymce/upload` |
| `tomselect` | Tom Select | `select-` (+ `bind-`) | remote loading when `query` is set, `select-preload-values`, `same-template`, `removable`, `tags` |
| `turnstile` | Cloudflare Turnstile | `captcha-` | `size: 'flexible'`, sitekey from `<meta cloudflare-turnstile-site-key>` |
| `uploader` | `RedotUploader` | `uploader-` | `multiple: true`, `autoUpload: true`, `maxSize: 10485760` |

Each initiator stores its instance on the element under a `.data()` key (e.g. `.data('tomselect')`, `.data('picker')`, `.data('uploader')`, `.data('repeater')`) which the field serialization helpers below rely on. See the per-component pages for full prop documentation, e.g. [Uploader component](/components/uploader).

## Global helpers (`functions.js`)

`functions.js` defines a set of free functions on the global scope, plus a couple of jQuery extensions. None are namespaced — they are plain globals.

### jQuery extensions

- **`$.fn.hasAttr(name)`** — returns whether the first matched element has the given attribute.
- **`$.query(selector, context = document)`** — flexible lookup helper. Given a bare string it tries, in order: the raw selector, `#sel`, `.sel`, `[name="sel"]`, `[name^="sel"]`, `[sel]`, returning the first non-empty match. Passing a jQuery object returns it unchanged.

### Translation & JSON

- **`__(key, params = {})`** — translates `key` against `window.__translations` (loaded from `dist/translations/<locale>.js`), substituting `:param` placeholders. Falls back to the key itself if no translation table or no match. See [translations build](#the-dist-build-step).
- **`isJson(value)`** — `true` if the string parses as JSON.
- **`stringToPrimitive(string)`** — coerces an attribute string to a JS value: the literals `true`/`false`/`null`/`undefined`, `window.x.y` lookups, `return ...` evaluated expressions, numeric strings, JSON, or the raw string. This is how `init`/option attributes become real values.

### Confirmation dialogs (wrap jQuery-Confirm)

- **`warnBeforeAction(action, options = {})`** — red "Are you sure?" confirm; runs `action` on confirm.
- **`awaitConfirmation(options = {})`** — Promise-based blue confirm that resolves/rejects on the user's choice.

### Form & field serialization

- **`validateForm($form, verbose = false)`** — runs `RedotValidator.errors($form)`; when `verbose`, appends errors and scrolls to the first one.
- **`appendErrorsToForm($form, errors)`** / **`scrollToFirstError($form)`** — render Bootstrap `.invalid-feedback`, mark tabs with `has-invalid-feedback`, and (unless `dont-toast`) raise a toast. `scrollToFirstError` respects the `dont-scroll-to-error` attribute.
- **`serializeFields($fields, key = 'name')`** — builds a nested object from all `[name]` (or other `key`) fields using `_.set`.
- **`getFieldValue($field, form, key)`** / **`setFieldValue($field, value, form, key)`** — type-aware read/write that understands checkboxes/switches, radios, numbers, `select` (including Tom Select via `.data('tomselect')`), TinyMCE, repeaters, uploaders, and date pickers (`.data('picker')`).
- **`deserializeFields($fields, data, key)`** — applies a data object back onto fields, triggering `change`.
- **`checkOtherField(field, other, otherValue)`** — compares another field's value (repeater-aware via `[initial-name]`).

### UI / misc

- **`makeDraggable(selector, minDiff = 5)`** — drag-to-scroll on an element.
- **`formRequest(url, data = {}, method = 'POST')`** — builds and submits a hidden form with CSRF token and `_method` spoofing.
- **`copyToClipboard(text)`** — `navigator.clipboard.writeText`.
- **`toggleFullscreen(selector = 'html', className = 'fullscreen')`** — toggle the Fullscreen API.
- **`slugify(string, options = {})`** — defaults `{ replacement: '-', lower: true, strict: false, trim: true }`.
- **`applyAvatarPreview(source, target)`** — preview a selected image file as a CSS background on the target.
- **`getOptionsFromSelector(selector, prefix, camelCase = true)`** — the prefixed-attribute → options collector used by every initiator.

## Global objects & namespaces

The platform registers these globals (defined in `functions.js`, the `redot-*` plugin files, the locale script, and vendor scripts):

- **`window.__inits`** — registry of initiator functions (from `dist/init.js`).
- **`window.__translations`** / **`window.__locale`** — translation table and active locale (from `dist/translations/<locale>.js`).
- **`window.OldBag`** / **`window.ErrorsBag`** — Laravel `old()` input and validation errors, injected by the scaffold.
- **`window.init`** — the bootstrap dispatcher described above.
- **`window.themerKey`** / **`window.themeConfig`** — theming keys used by `themer.js`; the theme is read from `localStorage`, applied as `data-bs-theme*` attributes on `<html>`, and a `theme:changed` event is dispatched on theme toggle (the `tempus-dominus` and `tinymce` inits listen for it).
- Plugin classes referenced by inits and helpers: **`RedotValidator`**, **`RedotVisibility`**, **`RedotIconPicker`**, **`RedotRepeater`**, **`RedotUploader`** (loaded from `assets/plugins/*.js`).

`app.js` also configures global jQuery behaviour: jQuery-Confirm defaults, Fancybox defaults, an `$.ajaxSetup` that sends `X-CSRF-TOKEN` and shows a "Session Expired" dialog on HTTP 419, native form validation disabled (`novalidate`), and a global `submit` handler that runs `validateForm()` before submitting (skipped on `[disable-validation]` forms).

## Usage

### Triggering an init from a Blade component

Add the `init` attribute and the matching option-prefixed attributes. From `resources/components/color-picker.blade.php`:

```blade
@pushOnce('plugins-styles', 'coloris-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/coloris/coloris.min.css') }}">
@endPushOnce

<input type="text" id="{{ $id }}"
    {{ $attributes->class(['form-control'])->merge(['init' => 'coloris']) }} />

@pushOnce('plugins-scripts', 'coloris-scripts')
    <script src="{{ hashed_asset('/vendor/coloris/coloris.min.js') }}"></script>
@endPushOnce
```

Note the pattern: the vendor library's CSS/JS is pushed via `@pushOnce` onto the `plugins-styles` / `plugins-scripts` stacks (with a dedupe key) so it loads only once per page, while the actual initialization is driven entirely by the `init="coloris"` attribute and `dist/init.js`.

The rich-editor component (`resources/components/rich-editor.blade.php`) follows the same convention with TinyMCE:

```blade
<x-textarea :value="$value" :id="$id" :autosize="false" init="tinymce"
    {{ $attributes->only(['name', 'validation']) }} />

@pushOnce('plugins-scripts', 'tinymce-scripts')
    <script src="{{ hashed_asset('/vendor/tinymce/tinymce.min.js') }}"></script>
@endPushOnce
```

### Reaching an initialized instance from JS

```js
// the Tom Select instance is stored on the element
const ts = $('#country').data('tomselect');
ts.setValue('eg');

// type-aware read/write that understands all init'd field types
const value = getFieldValue($('#published_at')); // reads the date picker, etc.
setFieldValue($('#tags'), ['a', 'b']);           // writes through Tom Select
```

### Adding a new initiator

1. Create `public/assets/inits/my-widget.js` whose last statement returns the initiator:

   ```js
   return (selector, options = {}) => {
       const selectorOptions = getOptionsFromSelector(selector, 'mywidget-');
       options = _.merge({ animation: 150 }, selectorOptions, options);

       const instance = new MyWidget($(selector).get(0), options);
       $(selector).data('myWidget', instance);
   };
   ```

2. Use it from Blade: `<div init="my-widget" mywidget-animation="300"></div>`.
3. The `EnsureDependenciesBuilt` middleware detects the new file (via `lock.json` mtime tracking) and rebuilds `dist/init.js` on the next request, registering `window.__inits["my-widget"]`.

## Gotchas

- **Never edit `public/assets/dist/*` by hand** — it is git-ignored and regenerated by `dependencies:build`. Edit the source `public/assets/inits/*.js` and `lang/*` files instead.
- The platform layer is **not** bundled by Vite; load order in `scaffold.blade.php` matters because globals (`$`, `_`, `__inits`, `__translations`, `Redot*`) must exist before dependents run.
- `getOptionsFromSelector` only picks up attributes with the **exact prefix** for that init, and camel-cases hyphenated names; `tinymce` is the exception and does not use a prefix.
- `init()` only processes `[init]:not([initialized])`; if you re-render an element keep in mind it must not already carry `initialized`.

## Related pages

- [Helpers](/foundation/helpers) — `hashed_asset()`, `dist_path()`, and other core helpers.
- [Uploader component](/components/uploader) — a component driven by the `uploader` init.
