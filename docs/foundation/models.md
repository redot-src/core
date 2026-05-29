# Models

Redot Core ships four Eloquent models under the `Redot\Models` namespace that back the package's settings, localization, and authentication features. Their tables are created by the package's bundled migrations and consumed throughout the Redot Dashboard app.

## Overview

| Model | Table | Purpose |
| --- | --- | --- |
| `Redot\Models\Setting` | `settings` | Key/value application settings with caching and a schema-driven defaults/validation layer. |
| `Redot\Models\Language` | `languages` | Available locales (code, name, RTL flag). |
| `Redot\Models\LanguageToken` | `language_tokens` | Per-language translation strings managed by the localization tooling. |
| `Redot\Models\LoginToken` | `login_tokens` | Short-lived magic-link / OTP tokens for passwordless auth. |

All four are plain `Illuminate\Database\Eloquent\Model` subclasses, so the usual Eloquent API applies in addition to the helpers documented below.

## Setting

The settings model is key/value at rest but exposes a static API for reading and writing values with permanent caching.

### Schema and columns

Migration `create_settings_table`:

- `key` — `string`, unique
- `value` — `text`

`value` is cast with `Redot\Casts\Union`, so it transparently serializes/deserializes scalars, arrays, etc. The model is mass-assignable on `key` and `value`.

### Schema-driven configuration

Static methods read the schema from `config('redot.settings')`, where each entry may define a `default` and `rules`:

```php
Setting::schema();    // the raw config('redot.settings', [])
Setting::defaults();  // [key => default] for entries that declare a 'default'
Setting::rules();     // merged validation rules (supports list rules per key or a rules map)
Setting::default('app.name'); // resolves a default, including nested dot keys
```

`rules()` accepts two shapes per entry: a list (`['required', 'string']`) keyed by the setting key, or an associative array of rules that gets merged into the result.

### Reading and writing

```php
Setting::get(string $key, mixed $default = null, bool $fresh = false): mixed
Setting::set(string $key, mixed $value): void
```

`get()` caches values forever under `settings.{key}`. It supports nested access via dot notation (`Setting::get('mail.host')` reads the `mail` setting and pulls `host` out of its array value), falls back to `default()` when no explicit default is given, and `$fresh = true` busts the cache before reading. `set()` is an `updateOrCreate` on `key`.

The model's `booted()` hook clears caches automatically on `created` / `updated`, including nested dot-keys derived from both the default and stored array value.

### Usage (consumer app)

The dashboard's `SettingController` drives an admin form straight off the schema:

```php
$defaults = Setting::defaults();
$keys = array_keys($defaults);

$request->validate(Setting::rules());

foreach ($keys as $key) {
    $value = match (true) {
        $request->hasFile($key) => $this->uploadFile($request->file($key), 'settings'),
        is_bool($defaults[$key]) => $request->boolean($key),
        default => $request->input($key),
    };

    if ($value === null) {
        continue;
    }

    Setting::set($key, $value);
}
```

There is also a global `setting()` helper that wraps the model:

```php
setting();                       // all settings as [key => value]
setting('app.name', 'Redot');    // single value with fallback
setting('app.name', null, true); // bypass cache
```

See [Settings](/foundation/settings) for the schema format and the helper in depth.

## Language

Represents an available locale.

### Schema and columns

Migration `create_languages_table`:

- `code` — `string(2)`, unique (also the route key, see below)
- `name` — `string`
- `is_rtl` — `boolean`, default `false`

Mass-assignable: `code`, `name`, `is_rtl`.

### API

```php
Language::current(): static   // the Language whose code matches app()->getLocale()
$language->direction;          // 'rtl' when is_rtl, otherwise 'ltr' (accessor)
$language->tokens;             // hasMany(LanguageToken::class)
```

`getRouteKeyName()` returns `code`, so route-model binding resolves languages by their locale code rather than id.

### Usage (consumer app)

Layouts resolve text direction from the active language:

```php
// app/View/Layouts/Scaffold.php
$this->direction ??= Language::current()->direction;
```

## LanguageToken

A single translation string belonging to a `Language`. These are populated and maintained by the localization jobs and the dashboard's language-token datatable.

### Schema and columns

Migration `create_language_tokens_table`:

- `language_id` — foreign id, constrained, cascade on delete
- `key` — `text`
- `value` — `text`
- `original_translation` — `text`
- `from_json` — `boolean`, default `false`
- `is_published` — `boolean`, default `false`

`from_json` and `is_published` are cast to `boolean`. Mass-assignable: `language_id`, `key`, `value`, `original_translation`, `from_json`, `is_published`.

### Behavior

The `booted()` hook resets `is_published` to `false` whenever a token's `value` is changed on update — editing a translation un-publishes it until it is re-published.

### Relationship and scopes

```php
$token->language; // belongsTo(Language::class)

LanguageToken::published();    // is_published = true
LanguageToken::unpublished();  // is_published = false
LanguageToken::modified();     // value != original_translation
LanguageToken::notModified();  // value = original_translation
LanguageToken::fromJson();     // from_json = true
LanguageToken::notFromJson();  // from_json = false
```

### Usage (consumer app)

Scopes compose with the `tokens()` relationship across the localization jobs:

```php
// publish JSON-sourced tokens
$tokens = $this->language->tokens()->fromJson()->pluck('value', 'key')->sortKeys();

// re-publish edited PHP translations
$translations = $this->language->tokens()->notFromJson()->unpublished()->get();

// revert edits back to original
$this->language->tokens()->modified()->update([/* ... */]);
```

See [Localization](/foundation/localization) for the extraction, publishing, and reverting workflow.

## LoginToken

Backs passwordless authentication (magic link + OTP code). Each token targets an email under a specific guard and expires.

### Schema and columns

Migration `create_login_tokens_table`:

- `email` — `string`
- `token` — `string(64)`, unique
- `code` — `string(6)`
- `guard` — `string`
- `expires_at` — `timestamp`
- composite index on `['email', 'guard']`

`expires_at` is cast to `datetime`. Mass-assignable: `email`, `token`, `code`, `guard`, `expires_at`.

### API

```php
LoginToken::generate(string $email, string $guard): static
LoginToken::findByToken(string $token, string $guard): ?self
LoginToken::findByCode(string $code, string $email, string $guard): ?self
$loginToken->isExpired(): bool

LoginToken::valid();              // expires_at > now()
LoginToken::forGuard($guard);     // where guard = $guard
```

`generate()` deletes any existing tokens for the same email + guard, then creates a fresh record with a random 64-char `token`, a random 6-char `code`, and an `expires_at` computed from `config('auth.magic_link.expire', 15)` minutes. `findByToken()` and `findByCode()` both apply the `forGuard` and `valid` scopes, so they only return non-expired matches.

### Usage (consumer app)

The auth package's `MagicLink` action references the model class:

```php
// src/packages/auth/src/Actions/MagicLink.php
protected static ?string $loginTokenModel = LoginToken::class;
```

See [Authentication](/packages/auth/overview) for the full magic-link / OTP flow.

## Gotchas

- `Setting::get()` caches forever. Use `setting($key, $default, true)` or `Setting::get($key, $default, true)` to read fresh; cache is otherwise only flushed on model `created`/`updated`.
- `Language::current()` returns `null` if no `languages` row matches the current locale — accessing `->direction` on it will fail, so ensure the active locale exists as a row.
- Editing a `LanguageToken` value silently flips `is_published` to `false`.
- `LoginToken::generate()` is destructive: it removes prior tokens for the same email + guard before issuing a new one.
