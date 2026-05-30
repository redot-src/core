# JS Translations

`__()` is the JavaScript twin of Laravel's `__()` helper. Reach for it whenever
your front-end code needs a user-facing string — a dialog title, a toast, a
button label — so it shows up in the visitor's language instead of being
hard-coded in English.

## Usage

Call `__()` with the key you want translated:

```js
toastify().error(__('You have errors in your form. Please correct them and try again.'));

$.confirm({
    title: __('Are you sure?'),
    content: __('This action cannot be undone.'),
});
```

As with Laravel's JSON translations, the key is normally the **English source
string itself** (`'Are you sure?'`), not a dotted identifier. Framework strings
do use dotted keys (`'validation.required'`, `'pagination.next'`, `'auth.failed'`).

## Substituting `:param` placeholders

Pass an object as the second argument. Each entry replaces every `:name` token
in the resolved string:

```js
__('(and :count more error)', { count: messages.length });
// → "(and 3 more error)"
```

Substitution is a plain `replaceAll`, so a token that appears more than once is
filled everywhere. There is no pluralization — `:count` is a string swap, not a
`trans_choice`-style plural selector.

## Where the strings come from

The string you pass must match the key exactly. Keys are extracted from your
source (Blade and JS alike share one entry per string) and the translated values
come from the application's language files — see
[Localization](/foundation/localization). The browser only loads the bundle for
the active locale, so the same `__('Save')` call resolves to the right language
automatically.

If a key is missing — or the bundle has not loaded yet — `__()` returns the key
unchanged, so strings degrade gracefully to their English source.

## Gotchas

- **Match the source exactly.** A punctuation or casing difference between a
  Blade `__('…')` and a JS `__('…')` produces two separate keys and a missed
  translation.
- **No pluralization.** Only flat `:param` replacement is supported.
- **Don't edit the generated bundles by hand.** Add or change strings through
  your language files and let the extractor regenerate them — see
  [Lang Extractor](/packages/lang-extractor).

## Related

- [Localization](/foundation/localization) — how locales are configured and resolved.
- [Lang Extractor](/packages/lang-extractor) — extracts source strings and builds the bundles.
- [Asset & Init System](/frontend/asset-system) — how front-end scripts are loaded.
