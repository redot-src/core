# Installation & Publishing

`redot/core` is the foundation package for the Redot Dashboard. It bundles the
authentication, sidebar, toastify, datatables and language-extractor packages,
registers the shared configuration, commands, blade directives, validation rules
and migrations, and wires everything into a Laravel 13 application.

## Requirements

The package targets a modern Laravel stack (see `composer.json`):

- PHP `^8.3`
- `laravel/framework` `^13.0`
- `laravel/sanctum` `^4.3`
- `livewire/livewire` `^4.2`
- `spatie/laravel-permission` `^7.3`
- `spatie/laravel-image-optimizer` `^1.8`
- `intervention/image-laravel` `^4.0`
- `giggsey/libphonenumber-for-php` `^9.0`

## Install via Composer

```bash
composer require redot/core
```

The Redot Dashboard app pins it as `"redot/core": "^0.1"` in its `composer.json`.

### Auto-discovery

You do not need to register the service provider manually. `composer.json`
declares it under Laravel's package extra block, so it is auto-discovered:

```json
"extra": {
    "laravel": {
        "providers": [
            "Redot\\RedotServiceProvider"
        ],
        "dont-discover": []
    }
}
```

`Redot\RedotServiceProvider` in turn registers the bundled package providers, so
installing `redot/core` is enough to bring all of them online:

- `Redot\Auth\RedotAuthServiceProvider`
- `Redot\Datatables\DatatablesServiceProvider`
- `Redot\Toastify\LaravelToastifyServiceProvider`

The Sidebar is bound as a singleton (`Redot\Sidebar\Sidebar`, aliased `sidebar`)
directly inside `RedotServiceProvider::register()`.

### Autoloaded helpers

Two helper files are loaded automatically via Composer's `files` autoload, so
their functions (such as `setting()`, `hashed_asset()`, and the toastify
helpers) are available everywhere without any extra setup:

- `src/helpers.php`
- `src/packages/toastify/src/helpers.php`

## What boots automatically

`RedotServiceProvider::boot()` configures a number of framework behaviors at
boot time. These take effect with no publishing required:

- Adds a **Redot** section to `php artisan about` (version + website).
- Registers the `@themer` Blade directive and the `layouts` anonymous component
  path (`resource_path('layouts')`) / namespace (`App\View\Layouts`).
- Sets the default pagination view to `components.pagination`.
- Defines an `api` rate limiter of 60 requests/minute, keyed by user id or IP.
- Skips `ConvertEmptyStringsToNull` for `PUT` requests to `*settings*` routes.
- Prohibits destructive DB commands in `production`.
- Builds `app.locales` from the `languages` table (falling back to
  `redot.locales`) and sets the default `locale` URL default.
- Registers the `phone` and `captcha` validation rules.
- Customizes Eloquent JSON casting to use `JSON_UNESCAPED_UNICODE` /
  `JSON_UNESCAPED_SLASHES` on encode and associative decode.

## Publishing

`redot/core` exposes several publish tags. Run the appropriate
`vendor:publish` command to copy resources into your app.

### Config — `redot::config`

Publishes the main `config/redot.php`. The config is already merged at runtime
via `mergeConfigFrom`, so publishing is only needed to customize it.

```bash
php artisan vendor:publish --tag=redot::config
```

This writes to `config_path('redot.php')`. See
[Configuration (redot.php)](/architecture/configuration) for the keys it
exposes (`features`, `locales`, `routing`, `settings`).

### Stubs — `redot::stubs`

Publishes the generator stubs used by the `make` commands
(`dashboard.*` and `website.page` stubs) into your app's `stubs/` directory.

```bash
php artisan vendor:publish --tag=redot::stubs
```

This writes from the package `stubs/` directory to `base_path('stubs/')`.
Published stubs include `dashboard.create.stub`, `dashboard.edit.stub`,
`dashboard.index.stub`, `dashboard.index-datatable.stub`, `dashboard.show.stub`
and `website.page.stub`.

### Migrations — `redot::migrations`

Publishes the package migrations. These are registered with
`publishesMigrations`, so Laravel rewrites their timestamps to the current time
on publish.

```bash
php artisan vendor:publish --tag=redot::migrations
php artisan migrate
```

This writes from the package `database/migrations/` to
`database_path('migrations/')`. The bundled migrations create the tables for
settings, languages, language tokens, login tokens, and the Spatie permission
tables:

- `create_settings_table`
- `create_languages_table`
- `create_language_tokens_table`
- `create_login_tokens_table`
- `create_permission_tables`

## Sub-package publish tags

The providers registered by `RedotServiceProvider` add their own tags.

### Datatables

The `Redot\Datatables\DatatablesServiceProvider` registers three tags. Its
config, views, lang files and routes are loaded automatically; publish only to
override them.

```bash
# config/datatables.php
php artisan vendor:publish --tag=datatables::config

# resources/views/vendor/datatables
php artisan vendor:publish --tag=datatables::views

# lang/vendor/datatables
php artisan vendor:publish --tag=datatables::lang
```

See [Datatables](/packages/datatables/overview) for details.

### Toastify

`Redot\Toastify\LaravelToastifyServiceProvider` merges and exposes its config
under the namespaced `toastify::config` tag, matching the other packages:

```bash
php artisan vendor:publish --tag=toastify::config
```

This writes to `config_path('toastify.php')`. The provider also registers the
`@toastifyCss` and `@toastifyJs` Blade directives. See
[Toastify](/packages/toastify).

### Auth & Sidebar & LangExtractor

`Redot\Auth\RedotAuthServiceProvider` only binds the `RedotAuthManager`
singleton and registers the `RedotAuth` facade alias — it publishes nothing. The
Sidebar and LangExtractor packages likewise expose no publish tags; they are
wired through `RedotServiceProvider` and Composer autoloading.

## Publish everything at once

To copy all `redot/core` resources, target the provider:

```bash
php artisan vendor:publish --provider="Redot\RedotServiceProvider"
```

You can also choose tags interactively:

```bash
php artisan vendor:publish
```

## Gotchas

- **Auto-discovery covers everything.** The Redot Dashboard app does not run any
  `vendor:publish` for `redot/core` during a normal install — config, views,
  lang, routes and migrations are all loaded from the package by default.
  Publish only when you need to override a resource.
- **Migration timestamps** are regenerated on publish (`publishesMigrations`),
  so the published order follows publish time, not the original prefixes.
- **All publish tags follow the `package::resource` convention** —
  `redot::config`, `datatables::views`, `toastify::config`, etc.
- **`app.locales`** is derived from the `languages` table at boot; before that
  table exists (e.g. fresh install, before migrating) it falls back to the
  `redot.locales` array in config.
