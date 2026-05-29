# Lang Extractor

The Lang Extractor is a sub-package of **redot/core** that scans your application's source code for translation tokens, stores them in the database as `LanguageToken` records, and lets you edit them in the dashboard before publishing them back to Laravel's language files. It powers the in-app translation editing workflow: extract → edit → publish, with revert and sync as recovery tools.

## Key concepts

The system has three moving parts:

- **`Redot\LangExtractor\LangExtractor`** — a standalone scanner that finds `__()`, `trans()`, and `@lang()` calls in your files and returns the unique translation strings it finds.
- **The `Language` / `LanguageToken` models** — each `Language` has many `LanguageToken` rows. A token stores the translation `key`, the current editable `value`, the `original_translation` it was created with, whether it came from a JSON file (`from_json`), and whether it has been published (`is_published`).
- **The jobs and commands** — `ExtractLanguageTokens`, `PublishLanguageTokens`, `RevertLanguageTokens`, and `SyncLanguageTokens` orchestrate the lifecycle. Each has a matching `lang:*` Artisan command, and the dashboard app invokes the jobs directly from controllers.

## `LangExtractor`

`Redot\LangExtractor\LangExtractor` is a fluent scanner. It does not touch the database — it just reads files and produces an array of translation strings.

```php
namespace Redot\LangExtractor;

public function __construct($directories = [], $extensions = [])
public function searchIn(string ...$directories): static
public function withExtensions(string ...$extensions): static
public function extract(): static
public function mergeWithFile(string $path): static
public function mergeWithArray(array $translations): static
public function all(): array
public function save(string $path, bool $force = false): int|bool
```

### Construction and defaults

If you construct it with no `directories`, it defaults to searching `resource_path()`, `app_path('Http')`, and `app_path('Livewire')`. Extensions passed to the constructor are normalized (leading dot stripped, lowercased, deduped) via `withExtensions()`.

The match pattern is generated for the functions `__`, `trans`, and `@lang`. It captures the first string argument of any of those calls, handling both single and double quotes and escaped quotes inside the string.

### Strict uniqueness and the ignore list

`generatePatternUsing()` builds a regex that **ignores keys that look like file-based translation references**. It globs the fallback locale's PHP language files (`lang_path(config('app.fallback_locale'))/*.php`) and adds a negative lookahead for each filename followed by a dot — so a call like `__('validation.required')` is skipped because `validation` is a known language file. Only free-form JSON-style strings (e.g. `__('Save changes')`) are captured.

`extract()` collects all matches across files, un-escapes `\"` and `\'`, trims each string, drops empties, and reduces to a `unique(strict: true)` list. The result is keyed by itself (`array_combine`), so `all()` returns an associative array where key === value:

```php
use Redot\LangExtractor\LangExtractor;

$extractor = new LangExtractor;
$extractor->searchIn(app_path(), resource_path('views'))
    ->withExtensions('php', 'js');

$translations = $extractor->extract()->all();
// ['Save changes' => 'Save changes', 'Dashboard' => 'Dashboard', ...]
```

### Merging and saving

`mergeWithFile()` merges in an existing JSON translation file (existing values win, then keys are sorted), and `mergeWithArray()` does the same for an array. `save()` writes the translations to a JSON file with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`; it returns `false` without writing if the file exists and `$force` is not `true`.

## The token lifecycle

### Extract — `ExtractLanguageTokens`

`Redot\Jobs\ExtractLanguageTokens` is the bridge from the scanner to the database. It builds a `LangExtractor` over `app_path()`, `public_path('assets')`, and `resource_path('views')`, scanning `php` and `js` files, then `firstOrCreate`s a token per discovered string on the given `Language`:

```php
$this->language->tokens()->firstOrCreate(
    ['key' => $key],
    [
        'value'                => $value,
        'original_translation' => $value,
        'from_json'            => true,
    ],
);
```

Because it uses `firstOrCreate`, re-running extraction never overwrites edits — it only adds newly discovered keys. Extracted tokens are always `from_json = true`.

### Publish — `PublishLanguageTokens`

`Redot\Jobs\PublishLanguageTokens` writes the database tokens back to Laravel's language files in two passes:

- **JSON tokens** (`fromJson()`) are written to `lang_path("{locale}.json")` as a sorted `key => value` JSON map.
- **File tokens** (`notFromJson()->unpublished()`) are grouped by their first dot-segment (the language file name) and the matching `'key' => 'value'` lines are rewritten in place inside `lang/{locale}/{file}.php` via `preg_replace`.

The locale used is `strtolower($language->code)`. After writing, all tokens for the language are marked `is_published = true`, and `trigger_dependencies_build()` is called, which clears the compiled assets directory (`File::deleteDirectories(dist_path())`) so front-end translation bundles rebuild.

### Revert — `RevertLanguageTokens`

`Redot\Jobs\RevertLanguageTokens` resets edits. It targets `modified()` tokens (where `value != original_translation`) and sets `value` back to the raw `original_translation` column while marking them `is_published = false`:

```php
$this->language->tokens()->modified()->update([
    'value'        => DB::raw('original_translation'),
    'is_published' => false,
]);
```

### Sync — `SyncLanguageTokens`

`Redot\Jobs\SyncLanguageTokens` rebuilds the token table from the on-disk language files (the inverse of publish). It **deletes all tokens** for the language, then re-seeds them: the `{locale}.json` file becomes `from_json = true` tokens, and every `lang/{locale}/*.php` file is flattened with `Arr::dot()` into `from_json = false` tokens. All synced tokens are `original_translation = value` and `is_published = true`. Use this when the files on disk are the source of truth and you want the database to match.

## Models

### `Redot\Models\Language`

```php
$language->tokens();          // hasMany(LanguageToken::class)
Language::current();          // language matching app()->getLocale()
$language->direction;         // 'rtl' or 'ltr' (from is_rtl)
```

Route-model binding uses `code` as the route key. Fillable: `code`, `name`, `is_rtl`.

### `Redot\Models\LanguageToken`

Fillable: `language_id`, `key`, `value`, `original_translation`, `from_json`, `is_published`. The `from_json` and `is_published` columns are cast to boolean.

A model `updating` hook automatically sets `is_published = false` whenever `value` becomes dirty — so editing a token always un-publishes it until it is published again.

Query scopes:

```php
LanguageToken::published();      // is_published = true
LanguageToken::unpublished();    // is_published = false
LanguageToken::modified();       // value != original_translation
LanguageToken::notModified();    // value = original_translation
LanguageToken::fromJson();       // from_json = true
LanguageToken::notFromJson();    // from_json = false
```

## Artisan commands

All four commands are registered by `RedotServiceProvider`. Each takes an optional `language` argument (the language `code`); when omitted, the job runs synchronously for every `Language` in the database.

```bash
php artisan lang:extract {language?}   # scan source, add new tokens
php artisan lang:publish {language?}   # write tokens to lang files
php artisan lang:revert  {language?}   # reset edits to original values
php artisan lang:sync    {language?}   # rebuild tokens from lang files
```

When a `language` code is given it is resolved with `Language::where('code', ...)->firstOrFail()`. All commands dispatch their job with `dispatchSync()`.

```bash
# Extract tokens for the Arabic language only
php artisan lang:extract ar

# Publish every language's tokens back to disk
php artisan lang:publish
```

## Usage in the dashboard

The dashboard app drives the same jobs from HTTP controllers rather than the CLI. For example `App\Http\Controllers\Dashboard\ExtractLanguageTokensController`:

```php
use Redot\Jobs\ExtractLanguageTokens;
use Redot\Models\Language;

public function __invoke(Language $language)
{
    ExtractLanguageTokens::dispatchSync($language);

    return $this->success(__('Language tokens have been extracted successfully.'));
}
```

The routes wire each action to a single-action controller (`routes/dashboard.php`):

```php
Route::get('languages/{language}/tokens/extract', ExtractLanguageTokensController::class)->name('languages.tokens.extract');
Route::get('languages/{language}/tokens/publish', PublishLanguageTokensController::class)->name('languages.tokens.publish');
Route::get('languages/{language}/tokens/revert',  RevertLanguageTokensController::class)->name('languages.tokens.revert');
```

The token-editing screen uses the model scopes to surface how much work is pending:

```blade
{{ __('There\'s about :count token(s) that need to be published.', ['count' => $language->tokens()->unpublished()->count()]) }}
{{ __('There\'s about :count token(s) that have been modified.', ['count' => $language->tokens()->modified()->count()]) }}
```

When a new language is created from a source language, the dashboard copies the source's tokens directly, preserving the lifecycle columns:

```php
$language->tokens()->createMany(
    Language::where('code', $source)->first()->tokens->map(
        fn ($token) => $token->only(['key', 'value', 'original_translation', 'from_json', 'is_published'])
    )->toArray()
);
```

## Gotchas

- **Extraction never overwrites edits.** `ExtractLanguageTokens` uses `firstOrCreate`, so existing tokens (and their edited values) are untouched; only brand-new keys are added.
- **Sync is destructive.** `SyncLanguageTokens` deletes all of a language's tokens before re-seeding from disk — any unsaved edits in the database are lost.
- **File-based keys are ignored by the scanner.** The ignore list is built from the fallback locale's PHP files, so dotted keys like `auth.failed` are not picked up by `LangExtractor`; they enter the database only via `lang:sync`. Extracted (scanner) tokens are always `from_json = true`.
- **Publishing clears compiled assets.** `trigger_dependencies_build()` deletes `public/assets/dist`, forcing front-end bundles to rebuild so JS translations stay in sync.
- **Editing un-publishes.** Updating a token's `value` flips `is_published` to `false` automatically via the model boot hook.
- **Locale casing.** Publishing lowercases the language `code` when resolving file paths (`{code}.json`, `{code}/*.php`).
