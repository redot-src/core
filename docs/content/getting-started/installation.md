# Installation & Publishing

`redot/core` is a foundation package for Laravel admin applications. Installing it
brings the dashboard, website, authentication, sidebar, toastify, datatables, and
language-extractor features online and wires them into your Laravel app.

## Requirements

- **PHP** 8.3+
- **Laravel** 13+
- **Livewire** 4.2+

The package pulls in its own runtime dependencies (Sanctum, the Spatie
permission package, Intervention Image, the image optimizer, and a phone-number
library), so you don't install those separately.

## Install via Composer

```bash
composer require redot/core
```

The package is auto-discovered — there is nothing to register in your app. As
soon as it is installed you get its config, routes, migrations, layouts, Artisan
commands, and global helpers (such as `setting()` and `hashed_asset()`) with no
extra setup. See [Service Provider](/architecture/service-provider) for the full
list of what it sets up for you.

## Publishing resources

Every resource works without publishing — config is merged at runtime, and
migrations and stubs are loaded straight from the package. Publish a resource
only when you want to override it; your published copy then wins over the
packaged default.

### Config — `redot::config`

Copies `config/redot.php` into your app so you can customize it.

```bash
php artisan vendor:publish --tag=redot::config
```

See [Configuration](/architecture/configuration) for the keys it exposes
(features, locales, routing, settings).

### Stubs — `redot::stubs`

Copies the generator stubs used by the `make` commands into your app's `stubs/`
directory. Once published, your edited copies take precedence so scaffolded
views use your markup.

```bash
php artisan vendor:publish --tag=redot::stubs
```

See [Scaffolding & Stubs](/commands/scaffolding-and-stubs).

### Migrations — `redot::migrations`

Copies the package migrations into your app and stamps them with the current
time, then run them.

```bash
php artisan vendor:publish --tag=redot::migrations
php artisan migrate
```

## Sub-package publish tags

The bundled packages expose their own tags, all following the
`package::resource` convention. Their config, views, lang, and routes load
automatically; publish only to override.

- **Datatables** — `datatables::config`, `datatables::views`, `datatables::lang`,
  `datatables::assets`. Run `php artisan datatables:link` (or publish
  `datatables::assets`) so CSS/JS are available under `public/vendor/datatables`.
  See [Datatables](/packages/datatables/overview).
- **Toastify** — `toastify::config`. See [Toastify](/packages/toastify).
- **Auth, Sidebar, LangExtractor** — nothing to publish; they are wired up for
  you.

## Publish everything at once

To copy all `redot/core` resources, or to choose tags interactively:

```bash
php artisan vendor:publish --provider="Redot\RedotServiceProvider"
php artisan vendor:publish
```

## Related

- [Service Provider](/architecture/service-provider) — what the package sets up for you.
- [Configuration](/architecture/configuration) — the `config/redot.php` keys.
- [Project Structure](/getting-started/project-structure) — where things go in your app.
