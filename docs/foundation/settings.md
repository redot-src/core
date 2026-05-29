# Settings

Redot Core provides a lightweight, cached, schema-driven settings store. Persisted key/value pairs live in a single `settings` table, defaults and validation rules are declared in `config/redot.php`, and values are read everywhere through the global `setting()` helper. Values are stored as a stringified union (booleans, integers, strings, JSON arrays) and are transparently cast back to native PHP types on read.

## Key concepts

- **Schema** — Every setting is declared under the `settings` key of `config/redot.php`. Each entry may define a `default` value and request `rules` used by the dashboard settings form. The schema is the source of truth for which settings exist.
- **Storage** — Values are persisted in the `settings` table (`key` unique, `value` text). The `value` column uses the `Redot\Casts\Union` cast so a single text column can hold booleans, integers, strings, and arrays.
- **Caching** — Reads go through `cache()->rememberForever('settings.{key}', ...)`. Saving a setting (create/update) forgets the affected cache entries, including nested dot-keys.
- **Nested / translatable settings** — Array-valued settings (e.g. translatable strings keyed by locale, or grouped values like `theme`) can be read either as a whole array or by dot notation (`theme.primary`, `app_name.en`).

## Public surface

### The `setting()` helper

```php
function setting(?string $key = null, mixed $default = null, bool $fresh = false): mixed
```

Defined in `src/helpers.php`. This is the primary way consumers read settings.

- `setting()` (no arguments) returns **all** settings as an associative array of `key => value` (pulled from the database, not the cache).
- `setting('key')` returns the value for `key`, falling back to the schema default.
- `setting('key', $default)` overrides the fallback used when no stored value exists.
- `setting('key', $default, true)` forces a fresh read, bypassing (and refreshing) the cache.

Internally `setting()` delegates to `Setting::get()`.

### The `Setting` model

`Redot\Models\Setting` — an Eloquent model backing the `settings` table.

```php
protected $fillable = ['key', 'value'];
protected $casts    = ['value' => \Redot\Casts\Union::class];
```

Static API:

```php
Setting::get(string $key, mixed $default = null, bool $fresh = false): mixed
Setting::set(string $key, mixed $value): void
Setting::default(string $key): mixed
Setting::schema(): array     // config('redot.settings', [])
Setting::defaults(): array   // [key => default] for every entry that defines a default
Setting::rules(): array      // merged validation rules from the schema
```

- `get()` reads through the forever-cache. For dot-notation keys it splits on the **first** dot, loads the parent setting, and resolves the remainder with `data_get()`. The default is resolved from `Setting::default($key)` when no explicit `$default` is passed.
- `set()` performs an `updateOrCreate(['key' => $key], ['value' => $value])`. The `Union` cast serializes the value for storage (arrays become JSON, booleans become `'true'`/`'false'`).
- `default()` resolves a default from config, supporting dot notation into array defaults (e.g. `Setting::default('theme.primary')`).
- `defaults()` returns only the entries that declare a `default`; this is how the dashboard knows which keys it owns.
- `rules()` merges rules from the schema. An entry whose `rules` is a **list** (e.g. `['required', 'array']`) is keyed by the setting key; an entry whose `rules` is an **associative array** (e.g. `app_name` with `app_name` and `app_name.*` rules) is merged in as-is.

### The `Union` cast

`Redot\Casts\Union` makes the single `value` text column behave like a typed value:

- On **read**: `'true'`/`'false'` become booleans, numeric strings become integers, strings starting with `{` or `[` are `json_decode`d to arrays; everything else is returned as-is.
- On **write**: booleans become `'true'`/`'false'`, arrays are `json_encode`d (unescaped unicode/slashes); other values pass through.

Note that any numeric string is cast to `int` on read, so values like `theme.radius` come back as integers.

### `app_name()` helper

```php
function app_name(): string
```

A convenience helper that returns the translatable `app_name` setting for the current application locale, falling back to `config('app.name')`:

```php
Arr::get(setting('app_name'), app()->getLocale()) ?: config('app.name');
```

## Settings table schema

Migration: `database/migrations/0001_01_01_000001_create_settings_table.php`

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value');
    $table->timestamps();
});
```

Each setting is a single row; nested/array settings are stored as JSON in the one `value` column rather than as multiple rows.

## Config shape

Settings are declared in `config/redot.php` under `settings`. Each key maps to a definition that may contain `default` and/or `rules`:

```php
'settings' => [
    'app_logo_dark'  => ['default' => 'assets/images/logo-dark.svg'],
    'app_logo_light' => ['default' => 'assets/images/logo-light.svg'],

    // Translatable setting: array keyed by locale + associative rules
    'app_name' => [
        'default' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم'],
        'rules'   => [
            'app_name'   => ['required', 'array'],
            'app_name.*' => ['required', 'string'],
        ],
    ],

    // Array setting with list-style rules (keyed by the setting key)
    'website_locales' => [
        'default' => ['en', 'ar'],
        'rules'   => ['required', 'array', 'min:1'],
    ],

    // Boolean settings
    'page_loader_enabled'    => ['default' => false],
    'service_worker_enabled' => ['default' => true],

    // Grouped/nested setting read via dot notation
    'theme' => [
        'default' => [
            'primary' => 'blue',
            'base'    => 'default',
            'font'    => 'sans-serif',
            'radius'  => 1,
        ],
    ],
],
```

Two rule shapes are supported (see `Setting::rules()`):

- **List** rules (`['required', 'array', 'min:1']`) are assigned to the setting's own key.
- **Associative** rules (`['app_name' => [...], 'app_name.*' => [...]]`) are merged verbatim, letting you validate nested array members.

## Usage

### Reading values (Blade)

These examples are from the consumer dashboard app:

```blade
{{-- Simple string setting --}}
<img src="{{ setting('app_logo_light') }}" />

{{-- Boolean setting controls behavior --}}
@if (setting('page_loader_enabled'))
    {{-- ... --}}
@endif

@if (config('app.env') === 'production' && setting('service_worker_enabled'))
    {{-- register service worker --}}
@endif

{{-- Array setting iterated directly --}}
@if (count(setting('website_locales')) > 1)
    @foreach (setting('website_locales') as $locale)
        {{-- ... --}}
    @endforeach
@endif

{{-- Raw HTML settings --}}
{!! setting('head_code') !!}
{!! setting('body_code') !!}

{{-- Nested setting via dot notation --}}
<div data-theme="{{ setting('theme.primary') }}"></div>

{{-- Explicit default argument --}}
<div data-bs-theme="{{ setting('dashboard_sidebar_theme', 'inherit') }}"></div>
```

### Reading values (PHP)

```php
use Redot\Models\Setting;

// Via the helper (preferred)
$locales = setting('dashboard_locales', []);
if (in_array($language->code, setting('website_locales'))) {
    // ...
}

// Translatable value resolved for the current locale
$name = app_name();

// Force a fresh, uncached read
$fresh = setting('theme', null, true);

// Directly on the model
$value = Setting::get('app_name');          // ['en' => '...', 'ar' => '...']
$primary = Setting::get('theme.primary');    // 'blue'
```

### Writing values

```php
use Redot\Models\Setting;

Setting::set('service_worker_enabled', true);    // stored as 'true'
Setting::set('website_locales', ['en', 'ar']);   // stored as JSON
Setting::set('app_name', ['en' => 'My App', 'ar' => 'تطبيقي']);
```

### Saving a settings form

The dashboard `SettingController::update()` shows the full save flow, driven entirely by the schema:

```php
$defaults = Setting::defaults();          // [key => default]
$keys     = array_keys($defaults);

$request->validate(Setting::rules());     // schema-derived validation

foreach ($keys as $key) {
    $value = match (true) {
        $request->hasFile($key)   => $this->uploadFile($request->file($key), 'settings'),
        is_bool($defaults[$key])  => $request->boolean($key),
        default                   => $request->input($key),
    };

    if ($value === null) {
        continue;
    }

    Setting::set($key, $value);
}

Artisan::call('optimize:clear');
```

Note the pattern: the set of writable keys comes from `Setting::defaults()`, boolean coercion is decided by inspecting whether the default is a bool, and validation comes straight from `Setting::rules()`.

## Translatable & array settings

Settings whose default is an array act as either translatable values or grouped values:

- **Translatable** (e.g. `app_name`): the array is keyed by locale. Read the whole array with `setting('app_name')`, a single locale with `setting('app_name.en')`, or the current-locale value with `app_name()`. In forms it is bound with the `<x-translatable>` component:

  ```blade
  <x-translatable component="input" type="text" name="app_name"
      :title="__('App name')" :value="setting('app_name')" />
  ```

- **Grouped** (e.g. `theme`): read the group with `setting('theme')` or individual members with dot notation. Forms post the members back as array input names so a single `Setting::set('theme', [...])` rebuilds the group:

  ```blade
  <x-radios name="theme[primary]" :value="setting('theme.primary')" ... />
  <x-radios name="theme[radius]"  :value="setting('theme.radius')"  ... />
  ```

Dot-notation reads split on the **first** dot only, so the remainder (`app_name.en`, `theme.primary`) is resolved against the stored array via `data_get()`.

## Caching & gotchas

- **Forever cache:** Reads are cached under `settings.{key}` with `rememberForever`. Pass `true` as the third argument to `setting()`/`Setting::get()` to refresh a single key.
- **Cache invalidation on write:** The model's `created`/`updated` events call `forgetCachedValue()`, which forgets `settings.{key}` plus every nested dot-key derived from both the schema default and the newly stored array value. This keeps nested reads (e.g. `theme.primary`) consistent after `Setting::set('theme', ...)`.
- **Numeric coercion:** Because the `Union` cast converts numeric strings to `int` on read, a setting like `theme.radius` returns an integer, not a string. Keep this in mind in strict comparisons.
- **`setting()` with no key is uncached:** Calling `setting()` with no arguments runs `Setting::all()` directly and does not use the per-key cache.
- **`value` is `NOT NULL`:** The column is a plain `text`, so always store a concrete value; rely on schema defaults for "empty" states (many string settings default to `''`).
- **Defaults live in config, not the DB:** A setting that has never been saved still resolves to its `config('redot.settings.{key}.default')` via `Setting::default()`.

## Related

- [Foundation: Helpers](/foundation/helpers) — full list of global helpers including `setting()` and `app_name()`.
- The `value` column relies on the `Redot\Casts\Union` cast described above.
