# Service Provider

`redot/core` wires itself into your Laravel app automatically. It is
auto-discovered on install — there is nothing to register in the consuming app —
and it sets up configuration, publishable resources, the Artisan commands, the
sidebar, the bundled packages, and a collection of boot-time behaviors.

This page describes what the package does for you. Most of it is invisible: you
just get the behavior.

## Publishable resources

The package exposes three publish tags. Each resource works without publishing
(config is merged, migrations and stubs are loaded from the package); publish
only to override.

```bash
php artisan vendor:publish --tag=redot::config       # config/redot.php
php artisan vendor:publish --tag=redot::stubs        # generator stubs into stubs/
php artisan vendor:publish --tag=redot::migrations   # package migrations
```

See [Installation](/getting-started/installation) for the full publishing
walkthrough and [Configuration](/architecture/configuration) for the config keys.

## Artisan commands

All the package's operational and scaffolding commands are registered for you,
so they show up in `php artisan list` the moment the package is installed. See
[Artisan Commands](/commands/overview) and
[Scaffolding & Stubs](/commands/scaffolding-and-stubs).

## Bundled packages

The feature packages come online automatically — you never wire them up
yourself:

- [Auth](/packages/auth/overview) — the dashboard's authentication flows.
- [Datatables](/packages/datatables/overview) — Livewire data tables.
- [Toastify](/packages/toastify) — toast notifications.
- [Sidebar](/packages/sidebar) — the navigation builder. Your app builds its
  menu against it in `app/sidebar.php`.

## What boots automatically

These behaviors apply across the whole consuming app with no setup. Be
deliberate if you override them.

- **Layout components.** Both the class-based and anonymous Blade layouts are
  exposed under the `layouts` namespace, so you write `<x-layouts::dashboard>`
  and friends. See [Layouts](/layouts/overview).
- **`@themer` directive.** Injects the theme runtime into a page from the
  current `theme` setting. The layouts use it for you.
- **Default pagination view.** Paginated results render with the dashboard's
  pagination view, so you never pass a view to `->links()`.
- **API rate limiting.** The `api` limiter allows 60 requests per minute, keyed
  by the authenticated user (or IP for guests).
- **Locales & URL defaults.** The active locale list is built from your
  languages (falling back to [`redot.locales`](/architecture/configuration#locales)
  before the table exists), and the first locale becomes the default for
  localized routes — so you don't pass `locale` to every `route()` call.
- **Validation rules.** The `phone` and `captcha` rules are available as plain
  string rules in any validator.
- **Settings-friendly forms.** Empty-string-to-null conversion is skipped for
  settings updates, so a setting can be deliberately cleared to an empty string.
- **Consistent JSON casting.** Eloquent JSON columns encode and decode
  consistently, including non-Latin content (e.g. Arabic) without escaping.
- **Production safety.** Destructive database commands (`migrate:fresh`,
  `db:wipe`, …) are blocked in the `production` environment.
- **Local permission bypass.** When `redot.permissions.bypass` is on and the
  environment is `local`, every Gate check passes so dashboard work does not
  require syncing permissions. See
  [Configuration](/architecture/configuration#permissions).
- **About section.** `php artisan about` reports the installed `redot/core`
  version.

## See also

- [Installation](/getting-started/installation) — install and publishing.
- [Configuration](/architecture/configuration) — the config keys.
- [Runtime & Dependency Build](/architecture/runtime-and-dependencies) — the app kernel and build pipeline.
