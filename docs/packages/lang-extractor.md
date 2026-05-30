# Lang Extractor

Lang Extractor powers the dashboard's in-app translation workflow. It scans your source for translatable strings, stores them so you can edit them in the dashboard, and publishes your edits back to Laravel's language files. The cycle is: extract new keys, edit their translations, publish them to disk — with revert and sync available as recovery tools.

## Quick start

Scan your app for new translation strings and add them as editable tokens:

```bash
php artisan lang:extract
```

Then edit the translations in the dashboard's language editor, and write your edits back to disk:

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

Extraction finds free-form translation strings — calls like `__('Save changes')`, `trans('Dashboard')`, and `@lang('...')`. It deliberately skips file-based keys such as `__('validation.required')`, since those live in PHP language files rather than the editable JSON set. File-based keys enter the editor only through `lang:sync`.

## Notes

- **Sync is destructive.** `lang:sync` replaces all of a language's tokens with what's on disk, so any unpublished edits are lost.
- **Editing un-publishes.** Changing a translation marks it as needing to be published again, so you always re-run `lang:publish` to apply it.

## Related

- [JS Translations](/frontend/translations) — how `__()` resolves on the front end.
- [Asset & Init System](/frontend/asset-system) — the compiled bundles that publishing rebuilds.
