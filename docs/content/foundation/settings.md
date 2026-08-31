# Settings

Application settings are persisted key/value pairs you read and write at runtime,
with types, defaults, and validation declared once by the application. This is the canonical
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

Every setting is declared once in `app/settings.php`. Register that file from an
application service provider's `register()` method so definitions are available
before providers boot:

```php
public function register(): void
{
    require base_path('app/settings.php');
}
```

The registry is the source of truth for which settings exist; each definition may declare:

- **`type`** — metadata consumers use to process the submitted value. Use
  `type('custom')` or one of the `file()`, `boolean()`, `string()`, `integer()`,
  `float()`, and `array()` shorthands.

- **`default`** — the value returned when nothing has been stored yet. A setting
  that has never been saved still resolves to its default.
- **`rules`** — validation rules for the dashboard settings form. Rules can be a
  list (applied to the setting's own key) or an associative map (to validate
  nested array members, e.g. `app_name.*`).

```php
use Redot\Models\Setting;

Setting::define('app_logo_light')
    ->file()
    ->default('assets/images/logo-light.svg');

Setting::define('app_name')
    ->array()
    ->rules([
        'app_name'   => ['required', 'array'],
        'app_name.*' => ['required', 'string'],
    ])
    ->default(['en' => 'Dashboard', 'ar' => 'لوحة التحكم']);

Setting::define('website_locales')
    ->array()
    ->rules(['required', 'array', 'min:1'])
    ->default(['en', 'ar']);

Setting::define('service_worker_enabled')
    ->boolean()
    ->default(true);

Setting::define('theme')
    ->array()
    ->default(['primary' => 'blue', 'radius' => 1]);
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

The dashboard settings form is driven entirely by the schema — writable keys,
types, and validation come straight from the registered definitions:

```php
use Redot\Models\Setting;

$request->validate(Setting::rules());

foreach (array_keys(Setting::schema()) as $key) {
    $value = match (Setting::type($key)) {
        'boolean' => $request->boolean($key),
        default => $request->input($key),
    };

    if ($value !== null) {
        Setting::set($key, $value);
    }
}
```

## Notes

- **Caching.** Reads are cached and stay warm until the value is written. Pass
  `true` as the third argument to `setting()` to refresh a single key.
- **Defaults live in `app/settings.php`, not the database.** "Empty" states rely
  on schema defaults — many string settings default to `''`.
- **Numeric coercion.** Numeric values come back as integers, so keep that in mind
  in strict comparisons (e.g. `setting('theme.radius')` is an `int`).

## Related

- [Helpers](/foundation/helpers) — `setting()`, `app_name()`, and the rest of the
  global helpers.
- [Localization](/foundation/localization) — uses the `website_locales` /
  `dashboard_locales` settings.
