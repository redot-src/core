# Configuration

`config/redot.php` is the single configuration file for the Redot Dashboard. It
controls which application surfaces are mounted, the locales the app supports,
locale-aware routing, and the schema for persisted application settings. Its
values are available as `config('redot.*')` out of the box.

## Publishing the config

```bash
php artisan vendor:publish --tag=redot::config
```

The config is merged at runtime, so every key is readable without publishing.
Publish a local copy only when you want to override a default — values in your
published `config/redot.php` win over the packaged ones.

## Features

`redot.features` toggles the four mountable surfaces and, where relevant, their
URL prefix. The dashboard reads these flags both when registering routes and to
conditionally render UI (e.g. hiding a "Preview website" link).

- **`website`** — mounts the public website routes. Set `enabled` to turn it on
  or off.
- **`dashboard`** — mounts the back-office routes. `enabled` toggles it;
  `prefix` sets the URL segment it lives under (default `dashboard`).
- **`website-api`** — mounts the public website API routes under `api/`. Set
  `enabled` to toggle it.
- **`dashboard-api`** — mounts the dashboard API routes. `enabled` toggles it;
  `prefix` sets the segment under `api/` (default `dashboard`).

```php
// reading a feature flag in a consuming app
if (config('redot.features.website.enabled')) {
    // link to the public website
}
```

## Locales

`redot.locales` is the static, file-based list of locales used as a fallback
before any languages exist in the database. Each entry sets:

- **`code`** — the locale code (e.g. `en`, `ar`).
- **`name`** — the human-readable name shown in switchers.
- **`is_rtl`** — whether the locale renders right-to-left.

Once languages exist in the database, the active locale list is built from them,
falling back to this array. Application code should generally read
`config('app.locales')` (a `code => name` map) rather than `redot.locales`
directly. See [Localization](/foundation/localization).

## Routing

`redot.routing` controls locale-aware URL generation and fallback redirects.

- **`append_locale_to_url`** — when on, the website/dashboard routes are prefixed
  with a two-letter `{locale}` segment. Turn it off to serve those routes
  without a locale segment.
- **`redirect_non_locale_urls`** — when on, a request to a non-prefixed URL is
  redirected to its locale-prefixed equivalent (preserving the query string).
  When off, such URLs return a 404 instead.

## Settings

`redot.settings` is the schema for **persisted** application settings — values
stored in the database, edited through the dashboard settings form, and read via
the [`setting()` helper](/foundation/settings). This is distinct from plain
config: config is static and file/env-driven, while settings are runtime-editable
and cached.

Each entry is keyed by setting name and may declare:

- **`default`** — the value returned when no stored row exists. Can be a scalar,
  an array (e.g. translatable values keyed by locale), or a nested structure
  read with dot notation (e.g. `theme.primary`).
- **`rules`** — validation rules applied by the settings form. A plain list of
  rules validates the setting key itself; an associative array lets one setting
  validate several fields (e.g. `app_name` and `app_name.*`).

The shipped schema covers the things you'd expect to edit from the dashboard:
the app logos and name, the website and dashboard locale lists, page-loader and
service-worker toggles, analytics and captcha keys, custom head/body code
injection, the sidebar theme, and the overall theme (primary colour, base, font,
and radius). Add your own entries to expose more editable settings.

### Reading settings

Use the [`setting()` helper](/foundation/settings):

```php
setting();                          // all stored settings as a key => value map
setting('app_logo_dark');           // a stored value, falling back to the default
setting('theme.primary');           // a nested value via dot notation
setting('dashboard_sidebar_theme', 'inherit');  // explicit fallback
```

The related `app_name()` helper reads the translatable `app_name` setting for
the current locale, falling back to `config('app.name')`. The `theme` setting is
consumed by the `@themer` directive in the layouts.

## See also

- [Settings](/foundation/settings) — the `setting()` helper in depth.
- [Localization](/foundation/localization) — locales and language tokens.
- [Service Provider](/architecture/service-provider) — what the package sets up at boot.
