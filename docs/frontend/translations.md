# JS Translations

The Redot Dashboard ships a client-side translation system so JavaScript can localize strings the same way Blade does with Laravel's `__()` helper. A per-locale bundle exposes the active translations on `window`, and a global `__()` function in `functions.js` looks them up at runtime with parameter substitution.

## What it is

There are two moving parts:

1. **Generated translation bundles** — one JS file per locale at `public/assets/dist/translations/<locale>.js` (e.g. `en.js`, `ar.js`). Each bundle assigns two globals:

   ```js
   window.__locale = 'en';
   window.__translations = { /* key => translation map */ };
   ```

   These files are generated from the application's PHP language files (and extracted source tokens) by the core Lang Extractor — see [Lang Extractor](/packages/lang-extractor) and [Localization](/foundation/localization). They are committed build artifacts; you do not edit them by hand.

2. **The `__()` runtime helper** — a global function defined in `public/assets/js/functions.js` that resolves a key against `window.__translations` and interpolates `:param` placeholders.

## Loading the bundle

The scaffold layout loads only the bundle for the current application locale, before the app scripts that depend on it:

```blade
{{-- resources/layouts/scaffold.blade.php --}}

{{-- Language Script --}}
<script src="{{ hashed_asset('/assets/dist/translations/' . app()->getLocale() . '.js') }}"></script>

{{-- Custom Scripts --}}
<script src="{{ hashed_asset('/assets/dist/init.js') }}"></script>
<script src="{{ hashed_asset('/assets/js/app.js') }}"></script>
```

Because the file is chosen by `app()->getLocale()`, the browser only ever downloads the active locale's strings. Switching the application language swaps which bundle is served. The bundle must load **before** `app.js`/`functions.js` run, otherwise `__()` falls back to returning keys verbatim (see below).

## The `__()` helper

```js
/**
 * Translate the given key with the given parameters.
 *
 * @param {string} key
 * @param {object} params
 * @returns {string}
 */
function __(key, params = {}) {
    if (typeof window.__translations === 'undefined') {
        return key;
    }

    let translation = window.__translations[key] || key;

    for (const [param, value] of Object.entries(params)) {
        translation = translation.replaceAll(`:${param}`, value);
    }

    return translation;
}
```

### Signature

- `key` (string) — the translation key. As with Laravel's JSON translations, the key is usually the **English source string itself** (e.g. `'Are you sure?'`), not a dotted identifier. Dotted keys also exist for framework strings (e.g. `'validation.required'`, `'pagination.next'`, `'auth.failed'`).
- `params` (object, default `{}`) — placeholder replacements. Each entry `{ count: 3 }` replaces every occurrence of `:count` in the resolved string.

### Lookup behavior

- If `window.__translations` is undefined (bundle not loaded yet), the key is returned **unchanged** — strings degrade gracefully to their English source.
- If the key is not present in the map, the key itself is returned (`window.__translations[key] || key`).
- Placeholder substitution uses `replaceAll(':param', value)`, so `:param` tokens are replaced globally. Substitution runs against whatever the lookup produced (translated string or the fallback key).

There is no pluralization logic — `:count` is a plain string replacement, not a pipe-delimited plural selector.

## Examples

These are real call sites in the dashboard's committed JS.

Confirmation dialog labels (`functions.js`):

```js
function warnBeforeAction(action, options = {}) {
    const defaultOptions = {
        type: 'red',
        title: __('Are you sure?'),
        content: __('This action cannot be undone.'),
        buttons: {
            cancel: { text: __('No'), btnClass: 'btn btn-secondary' },
            confirm: { text: __('Yes'), btnClass: 'btn-primary', action: function () { /* ... */ } },
        },
    };

    $.confirm(_.merge(defaultOptions, options));
}
```

Parameter interpolation when collapsing multiple validation errors (`functions.js`):

```js
if (messages.length) {
    // If there are more than one error, add a counter for the remaining errors
    message += ' ' + __('(and :count more error)', { count: messages.length });
}
```

Form error toast (`functions.js`):

```js
toastify().error(__('You have errors in your form. Please correct them and try again.'));
```

Breadcrumb label (`dashboard.js`):

```js
const breadcrumb = [__('Dashboard')].concat(page.parent ? [page.parent] : []).join(' › ');
```

Session-expired prompt (`app.js`):

```js
$.confirm({
    title: __('Session Expired'),
    content: __('Your session has expired. Please refresh the page to continue.'),
    buttons: { /* ... text: __('Refresh') ... */ },
});
```

## Inside the bundle

A bundle is a flat key/value map. Keys mirror the Blade-side `__()` keys, so a string used in a view and in JS shares one entry. For `en.js`, values equal their keys (identity), while `ar.js` carries the actual translations:

```js
// en.js
window.__translations = {
    "Are you sure?": "Are you sure?",
    "(and :count more error)": "(and :count more error)",
    "validation.required": "The :attribute field is required.",
    // ...
};

// ar.js
window.__translations = {
    "Are you sure?": "هل أنت متأكد؟",
    "(and :count more error)": "(و :count خطأ آخر)",
    "validation.required": "حقل :attribute مطلوب.",
    // ...
};
```

The map includes UI strings extracted from source, plus framework namespaces such as `validation.*`, `passwords.*`, `auth.*`, and `pagination.*`. The `:attribute`, `:count`, `:value`, etc. placeholders inside these values are what the `params` argument of `__()` fills in.

## Gotchas

- **Bundle must precede consumers.** `__()` returns the raw key whenever `window.__translations` is undefined. The scaffold layout deliberately includes the locale bundle before `init.js`/`app.js`.
- **Keep keys identical to Blade.** Because keys are the English source strings, a typo or punctuation mismatch between a Blade `__('...')` and a JS `__('...')` produces two distinct keys and a missed translation. Match the source exactly.
- **No pluralization.** Only flat `:param` replacement is supported; there is no `trans_choice` equivalent.
- **`replaceAll`, not single replace.** Every occurrence of `:param` in the string is replaced, so repeated placeholders all resolve to the same value.
- **Regenerated, not hand-edited.** Bundles are produced by the build/extraction step. Add or change strings through the source and the Lang Extractor rather than editing `en.js`/`ar.js` directly.

## Related

- [Lang Extractor](/packages/lang-extractor) — extracts source tokens and generates the translation bundles.
- [Localization](/foundation/localization) — how locales are configured and how the active locale is resolved server-side.
