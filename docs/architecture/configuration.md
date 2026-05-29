# Configuration

`config/redot.php` is the single configuration file shipped by **redot/core**. It controls which application features (website, dashboard, and their APIs) are mounted, the locales the app supports, locale-aware routing behavior, and the schema for persisted application settings. The file is merged into the container under the `redot` config key by `RedotServiceProvider` and can be published for customization.

## Publishing the config

`Redot\RedotServiceProvider::config()` merges the package defaults and exposes them for publishing:

```php
$this->mergeConfigFrom(dirname(__DIR__) . '/config/redot.php', 'redot');

$this->publishes([
    dirname(__DIR__) . '/config/redot.php' => config_path('redot.php'),
], 'redot::config');
```

Publish a local copy to override defaults:

```bash
php artisan vendor:publish --tag=redot::config
```

Because the file is merged (not just published), every key below is available as `config('redot.*')` even without publishing. Values you publish into `config/redot.php` win because Laravel merges published config over package defaults.

## Features

`redot.features` toggles the four mountable surfaces and (where relevant) their URL prefix. These flags are read by `Redot\Application::configure()` when registering routes:

```php
'features' => [
    'website-api'   => ['enabled' => true],
    'dashboard-api' => ['enabled' => true, 'prefix' => 'dashboard'],
    'website'       => ['enabled' => true],
    'dashboard'     => ['enabled' => true, 'prefix' => 'dashboard'],
],
```

How each flag is used during routing:

- `website-api.enabled` — loads `routes/api/website.php` under the `api/` prefix with the `api.website.` name prefix.
- `dashboard-api.enabled` / `dashboard-api.prefix` — loads `routes/api/dashboard.php` under `api/{prefix}` (default `api/dashboard`).
- `website.enabled` — loads `routes/website.php` on the `web` middleware group.
- `dashboard.enabled` / `dashboard.prefix` — loads `routes/dashboard.php` under `{prefix}` (default `dashboard`) on the `web` + `dashboard` middleware groups.

When running unit tests, `Application::configure()` force-enables all four `*.enabled` flags so routes are always available in tests.

Consumers read these flags to conditionally render UI. From the dashboard app:

```php
// app/sidebar.php
->hidden(config('redot.features.website.enabled') === false)
```

```blade
{{-- resources/layouts/dashboard/partials/navbar.blade.php --}}
@if (config('redot.features.website.enabled'))
    {{-- link to public website --}}
@endif
```

## Locales

`redot.locales` is the static, file-based list of locales used as a fallback before any `Language` records exist in the database:

```php
'locales' => [
    ['code' => 'en', 'name' => 'English',   'is_rtl' => false],
    ['code' => 'ar', 'name' => 'العربية', 'is_rtl' => true],
],
```

At boot, `RedotServiceProvider::configureApplicationLocales()` builds `app.locales` from the `Language` model, falling back to this array if the database is unavailable:

```php
try {
    config(['app.locales' => Language::pluck('name', 'code')->toArray()]);
} catch (Exception) {
    config(['app.locales' => array_column(config('redot.locales'), 'name', 'code')]);
}

URL::defaults(['locale' => Arr::first(array_keys(config('app.locales')))]);
```

So the first locale in the resolved list becomes the default `{locale}` route parameter. Application code should generally read `config('app.locales')` (a `code => name` map), not `redot.locales` directly.

## Routing

`redot.routing` controls locale-aware URL generation and fallback redirects:

```php
'routing' => [
    'append_locale_to_url'    => true,
    'redirect_non_locale_urls' => true,
],
```

- `append_locale_to_url` — when `true`, `Application::configure()` wraps the website/dashboard route group in a `{locale}` prefix constrained to two letters (`([a-zA-Z]{2})`). Disabling it serves those routes without a locale segment.
- `redirect_non_locale_urls` — used by `Redot\Http\Controllers\FallbackController`. When a non-prefixed URL is hit, the fallback re-matches the path with the current locale prepended and issues a `301` redirect to the locale-prefixed URL (preserving the query string). If either `append_locale_to_url` or `redirect_non_locale_urls` is `false`, the fallback aborts with `404` instead of redirecting.

## Settings

`redot.settings` is a schema for **persisted** application settings — values stored in the database (the `settings` table via `Redot\Models\Setting`) and surfaced through the `setting()` helper. This is distinct from plain config:

- **Plain config** (`config('redot.features.…')`) is static, file/env-driven, and read directly.
- **Settings** are runtime-editable through the dashboard settings form, cached forever (cache key `settings.{key}`), and only fall back to the schema's `default` when no DB row exists. Setting values are cast through `Redot\Casts\Union` and cache is flushed on create/update.

Each entry under `redot.settings` is keyed by setting name and may contain:

- `default` — the value returned when no stored row exists. Can be a scalar, an array (e.g. translatable values keyed by locale), or a nested structure addressable with dot notation (e.g. `theme.primary`).
- `rules` — Laravel validation rules used by the dashboard settings form. Two shapes are accepted (see below); a key may omit `rules` entirely.

The shipped schema:

```php
'settings' => [
    'app_logo_dark'  => ['default' => 'assets/images/logo-dark.svg'],
    'app_logo_light' => ['default' => 'assets/images/logo-light.svg'],
    'app_name' => [
        'default' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم'],
        'rules' => [
            'app_name'   => ['required', 'array'],
            'app_name.*' => ['required', 'string'],
        ],
    ],
    'website_locales'   => ['default' => ['en', 'ar'], 'rules' => ['required', 'array', 'min:1']],
    'dashboard_locales' => ['default' => ['en', 'ar'], 'rules' => ['required', 'array', 'min:1']],
    'page_loader_enabled'    => ['default' => false],
    'service_worker_enabled' => ['default' => true],
    'facebook_pixel_id'             => ['default' => ''],
    'google_analytics_property_id'  => ['default' => ''],
    'cloudflare_turnstile_site_key'   => ['default' => ''],
    'cloudflare_turnstile_secret_key' => ['default' => ''],
    'head_code' => ['default' => ''],
    'body_code' => ['default' => ''],
    'dashboard_sidebar_theme' => ['default' => 'inherit'],
    'theme' => [
        'default' => ['primary' => 'blue', 'base' => 'default', 'font' => 'sans-serif', 'radius' => 1],
    ],
],
```

### The two `rules` shapes

`Redot\Models\Setting::rules()` interprets the `rules` entry by whether it is a list:

- **List form** (`array_is_list`) — the rules apply to the setting key itself: `'website_locales' => ['required', 'array', 'min:1']` becomes the rule for the `website_locales` field.
- **Associative form** — the array is merged into the rule set as-is, letting one setting validate multiple fields (e.g. `app_name` and `app_name.*`).

### Schema accessors

`Setting` exposes three static helpers built from this schema:

- `Setting::schema()` — returns `config('redot.settings', [])`.
- `Setting::defaults()` — map of key to `default`, only for entries that declare one.
- `Setting::rules()` — assembled validation rules per the shapes above.
- `Setting::default($key)` — resolves a default, including nested dot keys (`theme.primary`).

The dashboard's settings controller drives the form straight from this schema:

```php
// app/Http/Controllers/Dashboard/SettingController.php
$defaults = Setting::defaults();
$keys = array_keys($defaults);

$request->validate(Setting::rules());

foreach ($keys as $key) {
    $value = match (true) {
        $request->hasFile($key)   => $this->uploadFile($request->file($key), 'settings'),
        is_bool($defaults[$key])  => $request->boolean($key),
        default                   => $request->input($key),
    };
    if ($value === null) continue;
    Setting::set($key, $value);
}
```

Note: `RedotServiceProvider::configureConvertEmptyStringToNull()` skips empty-string-to-null conversion for `PUT` requests matching `*settings*`, so settings can be intentionally cleared to an empty string.

### Reading settings

Use the global `setting()` helper (defined in `src/helpers.php`):

```php
function setting(?string $key = null, mixed $default = null, bool $fresh = false): mixed
```

- `setting()` with no key returns all stored settings as a `key => value` map.
- `setting('key')` returns the stored value, falling back to the schema default.
- Dot keys read into array values: `setting('theme.primary')`.
- `$fresh = true` bypasses and rebuilds the cache.

Real consumer usage:

```blade
{{-- resources/components/logo.blade.php --}}
<img src="{{ setting('app_logo_dark') }}" alt="{{ app_name() }}" />

{{-- nested theme value with explicit default --}}
<x-radios name="dashboard_sidebar_theme" :value="setting('dashboard_sidebar_theme', 'inherit')" />

{{-- list-valued setting --}}
@foreach (setting('website_locales') as $locale) ... @endforeach
```

```blade
{{-- service_worker_enabled gates registration --}}
@if (config('app.env') === 'production' && setting('service_worker_enabled'))
```

The related `app_name()` helper reads the translatable `app_name` setting for the current locale, falling back to `config('app.name')`:

```php
function app_name(): string
{
    return Arr::get(setting('app_name'), app()->getLocale()) ?: config('app.name');
}
```

The `theme` setting is also consumed at boot by the `@themer` Blade directive, which JSON-encodes `setting('theme')` into the page for the front-end themer script.

## See also

- [Datatables](/packages/datatables/overview)
