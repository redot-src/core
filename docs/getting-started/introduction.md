# Introduction

`redot/core` is the foundation package behind the **Redot Dashboard**. It bundles the shared models, helpers, console commands, validation rules, Blade behavior, and a set of in-repo sub-packages that the dashboard application depends on. Rather than a standalone app, it is a Composer **library** (`redot/core`, namespace `Redot\`) that a consuming Laravel app — the Redot Dashboard — pulls in and builds on top of.

## What it provides

When the package is installed, its single service provider `Redot\RedotServiceProvider` is auto-discovered (registered via `composer.json` under `extra.laravel.providers`). On `register()` and `boot()` it wires up everything the dashboard needs:

- Merges and publishes the `redot.php` config (publish tag `redot::config`).
- Publishes generator/view stubs (tag `redot::stubs`) and database migrations (tag `redot::migrations`).
- Registers the package console commands (entity/view generators, language token tooling, permission sync, uploads cleanup, dependency build, public link, lint — see `src/Commands`).
- Binds the `Sidebar` singleton (also aliased as `sidebar`) and registers the three sub-package providers.
- Boot-time global behavior: a `@themer` Blade directive, the default paginator view (`components.pagination`), the `api` rate limiter (60/min, keyed by user id or IP), `phone` and `captcha` validation rules, a configured `Json` cast (unescaped unicode/slashes on encode), the `app.locales` list, locale URL defaults, destructive-command protection in production, and an `about` command section for `redot/core`.

## Requirements

- **PHP** 8.3+
- **Laravel** 13+ (`laravel/framework ^13.0`)
- **Livewire** 4.2+ (`livewire/livewire ^4.2`)

Additional runtime dependencies declared by the package include `laravel/sanctum`, `spatie/laravel-permission`, `intervention/image-laravel`, `spatie/laravel-image-optimizer`, and `giggsey/libphonenumber-for-php`.

## In-repo sub-packages

The core ships five sub-packages, each PSR-4 autoloaded under its own namespace and registered (where applicable) by `RedotServiceProvider`:

- **[Auth](/packages/auth/overview)** (`Redot\Auth`) — authentication actions, contracts, route registrar, and the `RedotAuth` facade used by the dashboard's auth flows.
- **[Datatables](/packages/datatables/overview)** (`Redot\Datatables`) — Livewire-backed data tables with reusable columns, filters, and actions, plus their own routes, views, assets, and translations.
- **[LangExtractor](/packages/lang-extractor)** (`Redot\LangExtractor`) — extracts translation tokens from the codebase so UI strings can be synced, published, and reverted.
- **[Sidebar](/packages/sidebar)** (`Redot\Sidebar`) — the dashboard navigation builder, bound as a singleton (`Sidebar::class`, alias `sidebar`).
- **[Toastify](/packages/toastify)** (`Redot\Toastify`) — toast notifications with global helpers (`src/packages/toastify/src/helpers.php`) and publishable assets/config.

## Usage

The package is installed as a normal Composer dependency by the consuming dashboard:

```bash
composer require redot/core
```

After install, publish the config (and, when needed, stubs or migrations):

```bash
php artisan vendor:publish --tag=redot::config
php artisan vendor:publish --tag=redot::stubs
php artisan vendor:publish --tag=redot::migrations
```

### The `@themer` directive

The provider registers a `@themer` Blade directive that injects the theme runtime script into the `pre-content` stack. The dashboard uses it per layout, passing the theme key:

```blade
{{-- resources/layouts/dashboard/base.blade.php --}}
@themer('dashboard-theme')

{{-- resources/layouts/website/base.blade.php --}}
@themer('website-theme')
```

It resolves the script via `hashed_asset('assets/js/themer.js')` and serializes `setting('theme')` into `window.themeConfig`, with the passed expression exposed as `window.themerKey`.

### Locales

`app.locales` is populated at boot from the `Language` model when the table is available, otherwise it falls back to `config('redot.locales')` (e.g. `en` / `ar`). The first locale becomes the default `locale` URL default. The locale schema lives in the published `config/redot.php`.

## Notes and gotchas

- Boot-time behavior is global to the consuming app — the paginator view, rate limiter, URL locale default, validation rules, JSON cast, and destructive-command guard apply everywhere. Be deliberate when overriding them.
- Destructive database commands are prohibited only in the `production` environment.
- `ConvertEmptyStringsToNull` is skipped for `PUT` requests matching `*settings*`, so empty settings values are preserved.
- The package is **proprietary** and licensed for use only within the paid Redot Dashboard (see `LICENSE`).
- Package tests use Pest 4 with Orchestra Testbench against in-memory SQLite (`composer test`); formatting via Laravel Pint (`composer lint`).
