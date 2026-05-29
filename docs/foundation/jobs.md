# Jobs

Redot Core ships four queued jobs that drive its database-backed translation workflow. Together they move translations between the application's source code, the `language_tokens` database table, and the published Laravel language files (`lang/*.json` and `lang/<locale>/*.php`). Each job is a thin, reusable unit that is invoked both from artisan commands and from dashboard HTTP controllers.

All four jobs live in the `Redot\Jobs` namespace, implement `ShouldQueue`, and take a single `Redot\Models\Language` in their constructor.

## Key concepts

The workflow revolves around the `Redot\Models\LanguageToken` model, which stores one row per translation key/value pair per language. The relevant columns are:

- `key` — the translation key (a JSON key, or a dot-notation file key like `auth.failed`).
- `value` — the current (possibly edited) translation.
- `original_translation` — the value as it was last synced from disk, used to detect edits.
- `from_json` — `true` for JSON-based keys (`lang/<locale>.json`), `false` for PHP file keys (`lang/<locale>/*.php`).
- `is_published` — whether the current `value` has been written back to disk.

`LanguageToken` exposes query scopes the jobs rely on: `published()`, `unpublished()`, `modified()` (`value != original_translation`), `notModified()`, `fromJson()`, and `notFromJson()`. The model also has a `updating` hook that resets `is_published` to `false` whenever `value` changes, so editing a token automatically marks it as needing republishing.

The four jobs map to the four lifecycle stages of a token:

| Stage | Job | Direction |
|-------|-----|-----------|
| Discover new keys in code | `ExtractLanguageTokens` | code → DB |
| Load existing files into DB | `SyncLanguageTokens` | files → DB |
| Write edits back to files | `PublishLanguageTokens` | DB → files |
| Undo unpublished edits | `RevertLanguageTokens` | DB only |

## The jobs

### ExtractLanguageTokens

```php
new ExtractLanguageTokens(Language $language)
```

Scans the application source for translation strings and creates a token for any key that does not already exist. It builds a [`Redot\LangExtractor\LangExtractor`](/packages/lang-extractor) configured to search `app_path()`, `public_path('assets')`, and `resource_path('views')` for `.php` and `.js` files, then calls `extract()->all()`.

For each extracted string it calls `firstOrCreate` on the language's tokens keyed by `key`, defaulting `value` and `original_translation` to the extracted string and `from_json` to `true`. Because it uses `firstOrCreate`, existing tokens are left untouched — this only adds newly discovered keys.

### SyncLanguageTokens

```php
new SyncLanguageTokens(Language $language)
```

Rebuilds the token table for the language from the on-disk language files. It first deletes **all** existing tokens for the language, then:

1. Reads `lang/<code>.json` and seeds each entry with `from_json = true`.
2. Iterates every `lang/<code>/*.php` file via Symfony's `Finder`, flattens nested arrays with `Arr::dot()` (producing dot keys like `auth.failed`), and seeds each entry with `from_json = false`.

The internal `syncTokens()` helper uses `updateOrCreate` and marks every seeded token as `is_published = true` (since the value came straight from a published file) with `original_translation` equal to `value`. Array values are skipped. Note the destructive delete: any edits in the DB that were never published are lost on a sync.

### PublishLanguageTokens

```php
new PublishLanguageTokens(Language $language)
```

Writes the token values back to disk, then marks all of the language's tokens `is_published = true` and calls `trigger_dependencies_build()`.

- **JSON translations** (`fromJson()`): all values are plucked as `value` keyed by `key`, sorted, and written to `lang/<code>.json` with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`. The locale is lowercased.
- **File translations** (`notFromJson()->unpublished()`): only unpublished entries are processed. Each dot key is split into a filename (first segment) and an exact key (last segment); the existing `lang/<code>/<filename>.php` file is read and the matching `'key' => 'value'` line is rewritten in place via `preg_replace`, matching against the current translated value (`__($fullKey)`).

The closing `trigger_dependencies_build()` deletes the `dist_path()` directory so compiled assets/translations are rebuilt on the next request.

::: warning
File-based publishing edits existing PHP files in place with a regex and does not create files for keys whose target file does not exist. It targets the key/value pair as it currently appears on disk, so the corresponding PHP file must already contain the key.
:::

### RevertLanguageTokens

```php
new RevertLanguageTokens(Language $language)
```

Undoes unpublished edits in the database only — it touches no files. It selects the `modified()` tokens (`value != original_translation`) and sets `value` back to `DB::raw('original_translation')` and `is_published = false`.

## Usage

The jobs are dispatched synchronously (`dispatchSync`) everywhere in the stack, so despite implementing `ShouldQueue` they run inline by default in both commands and controllers.

### From artisan commands

Each job has a matching command registered in `RedotServiceProvider`. The optional `language` argument is the language **code**; when omitted the command runs the job for every `Language`.

```bash
# Extract new translation keys from source into the DB
php artisan lang:extract
php artisan lang:extract ar

# Sync DB tokens from the on-disk language files (destructive rebuild)
php artisan lang:sync en

# Publish DB token values back to lang files and rebuild dependencies
php artisan lang:publish ar

# Revert unpublished edits back to their original values
php artisan lang:revert
```

### From the dashboard app

The Redot Dashboard exposes these as single-action controllers wired to routes under `languages/{language}/tokens/*`. The `{language}` route binding resolves by `code` (the `Language` model overrides `getRouteKeyName()`):

```php
// app/Http/Controllers/Dashboard/PublishLanguageTokensController.php
class PublishLanguageTokensController extends Controller
{
    public function __invoke(Language $language)
    {
        PublishLanguageTokens::dispatchSync($language);

        return $this->success(
            __('Language tokens published successfully.'),
            'dashboard.languages.tokens.index',
            $language,
        );
    }
}
```

```php
// routes/dashboard.php
Route::get('languages/{language}/tokens/extract', ExtractLanguageTokensController::class)
    ->name('languages.tokens.extract');
Route::get('languages/{language}/tokens/publish', PublishLanguageTokensController::class)
    ->name('languages.tokens.publish');
Route::get('languages/{language}/tokens/revert', RevertLanguageTokensController::class)
    ->name('languages.tokens.revert');
```

### Dispatching directly

```php
use Redot\Jobs\SyncLanguageTokens;
use Redot\Models\Language;

$language = Language::where('code', 'ar')->firstOrFail();

SyncLanguageTokens::dispatchSync($language); // run inline
SyncLanguageTokens::dispatch($language);     // queue it
```

## Gotchas

- **`SyncLanguageTokens` is destructive.** It deletes every token for the language before re-seeding from disk. Run `lang:publish` first if the DB holds unpublished edits you want to keep.
- **`ExtractLanguageTokens` only adds.** It never updates or removes existing tokens; deleting stale keys requires a sync.
- **Extract marks keys as JSON.** Newly extracted tokens get `from_json = true`, so they publish into `lang/<code>.json`.
- **Publish rebuilds dependencies.** It always calls `trigger_dependencies_build()`, which removes the `dist_path()` directory.
- **Locale casing.** Publishing lowercases the language `code` for the output filename/directory.

## Related

- [Lang Extractor](/packages/lang-extractor) — the scanner used by `ExtractLanguageTokens`.
- [Helpers](/foundation/helpers) — `trigger_dependencies_build()`, `lang_path()`, `dist_path()`.
