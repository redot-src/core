# Jobs

Redot Core ships four jobs that drive the database-backed translation workflow.
They move translations between your application's source code, the translation
table you edit, and the published Laravel language files. You usually trigger
them through artisan commands, but you can also dispatch them yourself.

## Usage

Each job acts on a single language and runs inline by default. Dispatch one
directly when you need to:

```php
use Redot\Jobs\SyncLanguageTokens;
use Redot\Models\Language;

$language = Language::where('code', 'ar')->firstOrFail();

SyncLanguageTokens::dispatchSync($language); // run now, inline
SyncLanguageTokens::dispatch($language);     // queue it
```

More commonly you run the matching artisan command. The optional argument is a
language **code**; omit it to run for every language:

```bash
php artisan lang:extract      # discover new keys in code -> DB
php artisan lang:sync en      # rebuild DB tokens from on-disk files
php artisan lang:publish ar   # write edited values back to lang files
php artisan lang:revert       # undo unpublished edits in the DB
```

## What each job does

- **Extract** (`lang:extract`) — scans your source for new translation strings
  and adds a token for any key that doesn't exist yet. It only *adds* — existing
  tokens are left untouched. New keys publish into the JSON catalog.
- **Sync** (`lang:sync`) — rebuilds the language's tokens from the on-disk
  language files (both the JSON catalog and the PHP files). This is destructive:
  it deletes existing tokens first, so any unpublished edits in the database are
  lost. Run `lang:publish` first if you want to keep them.
- **Publish** (`lang:publish`) — writes the edited token values back to the
  language files, marks them published, and rebuilds front-end dependencies.
  File-based keys are rewritten in place, so the target PHP file must already
  contain the key.
- **Revert** (`lang:revert`) — discards unpublished edits in the database,
  restoring each token to the value it last had on disk. Touches no files.

## Examples

### Wiring a job to a controller action

Expose these as single-action controllers, running the job inline
and redirecting back with a flash message:

```php
public function __invoke(Language $language)
{
    PublishLanguageTokens::dispatchSync($language);

    return $this->success(
        __('Language tokens published successfully.'),
        'languages.index',
        $language,
    );
}
```

## Related

- [Localization](/foundation/localization) — the translation workflow and token
  query scopes.
- [Lang Extractor](/packages/lang-extractor) — the scanner used by extract.
- [Commands](/commands/overview) — the `lang:*` artisan commands.
