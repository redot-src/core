# Localization

Redot Core ships a database-driven localization layer: a `Language` model and a `LanguageToken` model that hold per-locale translations, plus a `Localization` middleware that detects the active locale, persists it, and keeps URLs in sync. Locales are declared in config (`redot.locales`) for fresh installs and then become rows in the `languages` table that the application reads at runtime.

## Key concepts

- **Languages** are stored in the `languages` table. Each has a two-character `code` (used as the route key and Laravel locale), a display `name`, and an `is_rtl` flag.
- **Language tokens** are individual translation entries (`key` / `value`) belonging to a language. They track whether they came from a JSON catalog (`from_json`), whether they have been published (`is_published`), and the `original_translation` they were extracted with so modifications can be detected.
- **Scope** — the middleware treats requests as either `website` or `dashboard`, and each scope has its own allowed-locale list (`website_locales` / `dashboard_locales` settings) and its own session key.
- **URL locale** — when `redot.routing.append_locale_to_url` is enabled, all web routes are prefixed with a `{locale}` segment, and the middleware normalizes/redirects to the resolved locale.

## Configuration

The relevant keys live in `config/redot.php`.

```php
// Seed locales for a fresh install (mirrored into the `languages` table).
'locales' => [
    ['code' => 'en', 'name' => 'English', 'is_rtl' => false],
    ['code' => 'ar', 'name' => 'العربية', 'is_rtl' => true],
],

'routing' => [
    'append_locale_to_url'     => true, // prefix web routes with /{locale}
    'redirect_non_locale_urls' => true, // 301 non-prefixed URLs to the locale-prefixed version
],
```

Two persisted settings (under `redot.settings`) control which locales are allowed per scope. Both default to `['en', 'ar']` and are validated as a non-empty array:

```php
'website_locales'   => ['default' => ['en', 'ar'], 'rules' => ['required', 'array', 'min:1']],
'dashboard_locales' => ['default' => ['en', 'ar'], 'rules' => ['required', 'array', 'min:1']],
```

Read them at runtime with the `setting()` helper, e.g. `setting('website_locales')`.

### `config('app.locales')`

The service provider populates `app.locales` as a `code => name` map. It pulls from the `languages` table when available, falling back to `redot.locales` if the database is not reachable:

```php
try {
    config(['app.locales' => Language::pluck('name', 'code')->toArray()]);
} catch (Exception) {
    config(['app.locales' => array_column(config('redot.locales'), 'name', 'code')]);
}

URL::defaults(['locale' => Arr::first(array_keys(config('app.locales')))]);
```

So `config('app.locales')` is the canonical "code → display name" list, and `array_keys(config('app.locales'))` gives the available codes.

## The `Language` model

`Redot\Models\Language` — fillable `code`, `name`, `is_rtl`.

```php
public function tokens();                              // hasMany(LanguageToken::class)
public static function current(): static;             // language matching app()->getLocale()
public function getDirectionAttribute(): string;      // 'rtl' when is_rtl, else 'ltr'
public function getRouteKeyName(): string;             // 'code' — route model binding uses the code
```

Because the route key is `code`, route model binding resolves a `Language` by its locale code (for example `dashboard.languages.tokens.index` receives `/languages/ar/...`). The `direction` accessor is convenient for setting the document direction:

```php
use Redot\Models\Language;

// In a layout component (consumer app: app/View/Layouts/Scaffold.php)
$this->direction ??= Language::current()->direction; // 'ltr' or 'rtl'
```

## The `LanguageToken` model

`Redot\Models\LanguageToken` — fillable `language_id`, `key`, `value`, `original_translation`, `from_json`, `is_published`. The `from_json` and `is_published` columns are cast to boolean.

Boot behavior: whenever the `value` changes on update, the token is automatically marked unpublished.

```php
protected static function booted(): void
{
    static::updating(function (self $token) {
        if ($token->isDirty('value')) {
            $token->is_published = false;
        }
    });
}
```

Relationship and query scopes:

```php
public function language();          // belongsTo(Language::class)

scopePublished();    // is_published = true
scopeUnpublished();  // is_published = false
scopeModified();     // value != original_translation
scopeNotModified();  // value = original_translation
scopeFromJson();     // from_json = true
scopeNotFromJson();  // from_json = false
```

These map directly onto the token management UI. See the [Lang Extractor](/packages/lang-extractor) page for how tokens get populated from your translation files.

## The `Localization` middleware

`Redot\Http\Middleware\Localization` is appended to the `web` middleware group automatically by Redot Core (registered before `SubstituteBindings`). You do not need to register it yourself.

Resolution order for each request:

1. Determine the **scope**: `dashboard` if the route name matches `dashboard.*` or the path is `dashboard`, otherwise `website`.
2. Load the allowed locales for that scope from `setting('{scope}_locales')`; the first entry is the fallback.
3. Pick the locale from the first available of: `?locale=` query string → the `{locale}` route parameter → the session value (`{scope}_locale`) → `Accept-Language` (`$request->getPreferredLanguage($locales)`).
4. If the chosen locale is empty or not in the allowed list, use the fallback.
5. Persist it to the session, call `app()->setLocale()`, set `URL::defaults(['locale' => $locale])`, and forget the route's `locale` parameter so it does not leak into bound parameters.
6. If the URL carried a `{locale}` that differs from the resolved one, **301 redirect** to the path with the corrected locale (preserving the query string).

Because `URL::defaults(['locale' => ...])` is set, `route()` calls that target locale-prefixed routes automatically receive the current locale — you generally do not pass `locale` explicitly.

### Non-prefixed URL fallback

`Redot\Http\Controllers\FallbackController` backs the catch-all route. When both `append_locale_to_url` and `redirect_non_locale_urls` are enabled, a request to a non-prefixed path (e.g. `/about`) that matches a real route once the current locale is prepended is 301-redirected to `/{locale}/about` (query string preserved). Otherwise, or for non-GET/HEAD requests, it returns 404.

## Usage

### Switching locale via query string

The dashboard auth layout renders a locale switcher using `?locale=`, which the middleware reads and stores in the session:

```blade
@if (count(setting('dashboard_locales')) > 1)
    <ul class="list-inline list-inline-dots mt-3 mb-0 text-center">
        @foreach (setting('dashboard_locales') as $locale)
            <li class="list-inline-item">
                <a href="{{ url()->current() }}?locale={{ $locale }}">
                    {{ config('app.locales.' . $locale) }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
```

### Creating a language (copying tokens from a source)

The consumer app creates a new language by validating a `direction` and mapping it to `is_rtl`, copying the source lang files, and cloning the source language's tokens:

```php
use Redot\Models\Language;

$validated['is_rtl'] = $validated['direction'] === 'rtl';

$language = Language::create($validated); // code, name, is_rtl

$language->tokens()->createMany(
    Language::where('code', $validated['source'])->first()->tokens
        ->map(fn ($token) => $token->only([
            'key', 'value', 'original_translation', 'from_json', 'is_published',
        ]))->toArray()
);
```

### Querying tokens

Tokens drive a datatable in the dashboard. Note the `is_modified` flag is computed in SQL using the same comparison as the `modified` scope:

```php
use Redot\Models\LanguageToken;

LanguageToken::where('language_id', $this->language->id)
    ->select('*')
    ->selectRaw('value != original_translation as is_modified');

// Or via scopes:
LanguageToken::published()->fromJson()->get();
LanguageToken::unpublished()->modified()->count();
```

### Document direction

```php
use Redot\Models\Language;

$direction = Language::current()->direction; // 'rtl' for Arabic, 'ltr' otherwise
```

## Gotchas and defaults

- The `code` column is unique and limited to **2 characters** in the migration; the consumer validates `max:2` on the language form.
- Editing a token's `value` silently resets `is_published` to `false` — re-publish after edits.
- Deleting a language is blocked by the consumer when it is the last language, when it is referenced in `website_locales` / `dashboard_locales`, or when it is the active application locale.
- `setting('{scope}_locales')` is read on every request by the middleware, so changing the allowed-locale settings takes effect immediately; only locales in that list are honored, everything else falls back to the first entry.
- When `append_locale_to_url` is `false`, web routes are not prefixed and the middleware resolves locale from query/session/`Accept-Language` only; the fallback redirect is also disabled.
- `Language::current()` returns the `Language` row whose `code` equals `app()->getLocale()`; it returns `null` if no matching row exists, so ensure your locales are seeded into the `languages` table.

## Related

- [Lang Extractor](/packages/lang-extractor) — extracting and publishing translation tokens.
