# Settings

Application settings are persisted key/value pairs you read and write at runtime,
with defaults and validation declared once in config. This is the canonical
reference for the `setting()` helper and the settings store.

## Usage

Read a setting with the global `setting()` helper:

```blade
<img src="{{ setting('app_logo_light') }}" />
```

```php
$locales = setting('website_locales');
```

- `setting('key')` returns the stored value, or the schema default when nothing
  is stored.
- `setting('key', $default)` overrides the fallback used when no value is stored.
- `setting('key', $default, true)` forces a fresh, uncached read.
- `setting()` with no key returns the full `key => value` map.

Write a setting:

```php
use Redot\Models\Setting;

Setting::set('service_worker_enabled', true);
Setting::set('website_locales', ['en', 'ar']);
Setting::set('app_name', ['en' => 'My App', 'ar' => 'تطبيقي']);
```

Values are typed automatically: booleans, integers, strings, and arrays all
round-trip through the single stored value, so you read back the type you stored.

## The settings schema

Every setting is declared once in `config/redot.php` under `settings`. The schema
is the source of truth for which settings exist; each entry may declare:

- **`default`** — the value returned when nothing has been stored yet. A setting
  that has never been saved still resolves to its default.
- **`rules`** — validation rules for the dashboard settings form. Rules can be a
  list (applied to the setting's own key) or an associative map (to validate
  nested array members, e.g. `app_name.*`).

```php
'settings' => [
    'app_logo_light' => ['default' => 'assets/images/logo-light.svg'],

    // Translatable: an array keyed by locale, with per-member rules.
    'app_name' => [
        'default' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم'],
        'rules'   => [
            'app_name'   => ['required', 'array'],
            'app_name.*' => ['required', 'string'],
        ],
    ],

    // Array setting with list-style rules.
    'website_locales' => [
        'default' => ['en', 'ar'],
        'rules'   => ['required', 'array', 'min:1'],
    ],

    // Boolean settings.
    'service_worker_enabled' => ['default' => true],

    // Grouped setting read via dot notation.
    'theme' => [
        'default' => ['primary' => 'blue', 'radius' => 1],
    ],
],
```

## Translatable & grouped settings

Array-valued settings act as either translatable or grouped values, and can be
read whole or by dot notation:

- **Translatable** (e.g. `app_name`) — keyed by locale. Read the whole map with
  `setting('app_name')`, one locale with `setting('app_name.en')`, or the
  current-locale value with `app_name()`. Bind them in a form with the
  translatable component:

  ```blade
  <x-translatable component="input" type="text" name="app_name"
      :title="__('App name')" :value="setting('app_name')" />
  ```

- **Grouped** (e.g. `theme`) — read the group with `setting('theme')` or a member
  with `setting('theme.primary')`. Forms post members as array input names so a
  single write rebuilds the group:

  ```blade
  <x-radios name="theme[primary]" :value="setting('theme.primary')" />
  ```

## Examples

### Boolean setting controlling behaviour

```blade
@if (setting('page_loader_enabled'))
    {{-- ... --}}
@endif
```

### Iterating an array setting

```blade
@foreach (setting('website_locales') as $locale)
    {{-- ... --}}
@endforeach
```

### Explicit fallback for an unset key

```blade
<div data-bs-theme="{{ setting('dashboard_sidebar_theme', 'inherit') }}"></div>
```

### Saving a settings form

The dashboard settings form is driven entirely by the schema — the writable keys
come from the defaults, and validation comes straight from the declared rules:

```php
use Redot\Models\Setting;

$request->validate(Setting::rules());

foreach (array_keys(Setting::defaults()) as $key) {
    if (($value = $request->input($key)) !== null) {
        Setting::set($key, $value);
    }
}
```

## Notes

- **Caching.** Reads are cached and stay warm until the value is written. Pass
  `true` as the third argument to `setting()` to refresh a single key.
- **Defaults live in config, not the database.** "Empty" states rely on schema
  defaults — many string settings default to `''`.
- **Numeric coercion.** Numeric values come back as integers, so keep that in mind
  in strict comparisons (e.g. `setting('theme.radius')` is an `int`).

## Related

- [Helpers](/foundation/helpers) — `setting()`, `app_name()`, and the rest of the
  global helpers.
- [Localization](/foundation/localization) — uses the `website_locales` /
  `dashboard_locales` settings.
