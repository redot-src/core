# Lang Extractor

Lang Extractor powers an in-app translation workflow. It scans your source for translatable strings, stores them so you can edit them in a language editor, and publishes your edits back to Laravel's language files. The cycle is: extract new keys, edit their translations, publish them to disk — with revert and sync available as recovery tools.

## Quick start

Scan your app for new translation strings and add them as editable tokens:

```bash
php artisan lang:extract
```

Then edit the translations in the language editor, and write your edits back to disk:

```bash
php artisan lang:publish
```

## Commands

Each command takes an optional language code; omit it to run for every language:

- **`lang:extract`** — scan the source code for translatable strings and add any new ones as editable tokens. Existing tokens (and your edits) are never overwritten — only brand-new keys are added.
- **`lang:publish`** — write the current translations back to Laravel's language files. This also triggers a rebuild of the compiled front-end translation bundles so JavaScript translations stay in sync.
- **`lang:revert`** — discard edits, resetting changed translations back to the value they were extracted with.
- **`lang:sync`** — rebuild the token list from the language files on disk. Use this when the files are the source of truth and you want the editor to match them.

```bash
# Extract for Arabic only
php artisan lang:extract ar

# Publish every language
php artisan lang:publish
```

## What gets extracted

Extraction finds free-form translation strings passed to `__()`, `trans()`, `trans_choice()`, `Lang::get()`, `Lang::choice()`, `@lang` and `@choice`. The argument may be single or double quoted, a heredoc or nowdoc, or several literals concatenated together; escape sequences are resolved exactly as PHP resolves them, so the extracted key matches the one your code looks up at runtime.

Arguments that are not fully literal — `__($label)` or `__('Prefix ' . $suffix)` — are skipped, because there is no static key to store.

Extraction deliberately skips file-based keys such as `__('validation.required')`, since those live in PHP language files rather than the editable JSON set. A string only counts as file-based when it has no whitespace and starts with the name of a PHP language file in the fallback locale, so a sentence like `__('validation.rules are strict here')` is still extracted. File-based keys enter the editor only through `lang:sync`.

By default the scan covers `app`, `resources`, `routes`, `database/seeders`, and `public/assets`; directories that don't exist are ignored. Only `.php` and `.js` files are searched. Files ignored by Git are skipped.

## Limitations

Extraction is static pattern matching, not a full PHP or JavaScript parser:

- **Dynamic keys are invisible.** Variables, class constants, interpolated double-quoted strings or heredocs, and any mix of literals with variables — `__($label)`, `__('Prefix ' . $suffix)`, `__("Hello $name")` — are skipped, because there is no fixed key to store.
- **Only the listed helpers.** Calls through a translator instance (`$translator->get(...)`) or other APIs are not scanned. `Lang::get` / `Lang::choice` must appear with that `Lang::` prefix (an aliased `use` is fine; a renamed import is not).
- **File-based PHP language keys stay out of extract.** Keys like `__('validation.required')` are filtered out; bring them into the editor with `lang:sync` instead.
- **Empty strings are dropped.** `__('')` never becomes a token.
- **Scan scope is fixed for the Artisan command.** `lang:extract` only walks the directories and extensions above. Other paths (packages under `vendor`, custom folders, `.vue` files, and so on) are ignored unless you drive `LangExtractor` yourself.

## Notes

- **Sync is destructive.** `lang:sync` replaces all of a language's tokens with what's on disk, so any unpublished edits are lost.
- **Editing un-publishes.** Changing a translation marks it as needing to be published again, so you always re-run `lang:publish` to apply it.

## Related

- [JS Translations](/frontend/translations) — how `__()` resolves on the front end.
- [Asset & Init System](/frontend/asset-system) — the compiled bundles that publishing rebuilds.
