# Project Structure

`redot/core` is a Composer **library** (not a standalone app) that bundles the shared building blocks for the Redot Dashboard: helpers, models, casts, rules, traits, commands, migrations, generator stubs, and a set of in-repo sub-packages. This page maps where everything lives on disk and how those paths line up with the PSR-4 namespaces in `composer.json`.

## Package facts

From `composer.json`:

- **Name:** `redot/core`, **type:** `library`, **license:** `proprietary`.
- **Runtime:** PHP `^8.3`, Laravel `^13.0`, Livewire `^4.2`, Sanctum `^4.3`, `spatie/laravel-permission ^7.3`, plus `intervention/image-laravel`, `spatie/laravel-image-optimizer`, and `giggsey/libphonenumber-for-php`.
- **Auto-discovered provider:** `Redot\RedotServiceProvider` (via the `extra.laravel.providers` key).
- **Tooling:** Pest 4 + Orchestra Testbench. Scripts: `composer test`, `composer lint`, `composer coverage`.

## Top-level layout

```
core/
├── composer.json            # PSR-4 map, provider auto-discovery, scripts
├── config/
│   └── redot.php            # the single Redot config surface
├── database/
│   └── migrations/          # package-owned, published schema
├── docs/                    # this VitePress site
├── src/                     # all package code (see below)
├── stubs/                   # generator/view stubs published into apps
├── tests/                   # Pest tests + fixtures
├── pint.json / .editorconfig
└── phpunit.xml
```

## The `src/` tree

The root namespace `Redot\` maps to `src/`. The in-repo sub-packages each live under `src/packages/<name>/src` with their own namespace. Grounded in `find src -type d`:

```
src/
├── Application.php           # Redot\Application
├── RedotServiceProvider.php  # boot/register: config, views, routes, migrations, publish tags
├── helpers.php              # global helpers (autoloaded)
├── Casts/                   # Union.php
├── Commands/                # 12 artisan commands (see below)
├── Http/
│   ├── Controllers/         # Controller.php (base), FallbackController.php
│   └── Middleware/          # EnsureDependenciesBuilt, Localization, RoutePermission
├── Jobs/                    # ExtractLanguageTokens, PublishLanguageTokens, RevertLanguageTokens, SyncLanguageTokens
├── Models/                  # Language, LanguageToken, LoginToken, Setting
├── Notifications/           # MagicLinkNotification
├── Rules/                   # Captcha, Phone
├── Support/                 # QueryFilters
├── Traits/                  # CanUploadFile, RespondAsApi, Taggable, UserAuditable
└── packages/
    ├── auth/src/            # Redot\Auth\
    ├── datatables/          # Redot\Datatables\ (+ config, routes, resources, lang)
    ├── lang-extractor/src/  # Redot\LangExtractor\
    ├── sidebar/src/         # Redot\Sidebar\
    └── toastify/            # Redot\Toastify\ (+ config, resources)
```

### Where things live

| Layer | Path |
| ----- | ---- |
| Root package code | `src` |
| Service provider | `src/RedotServiceProvider.php` |
| Package config | `config/redot.php` |
| Console commands | `src/Commands` |
| HTTP controllers/middleware | `src/Http/Controllers`, `src/Http/Middleware` |
| Models | `src/Models` |
| Casts, rules, traits | `src/Casts`, `src/Rules`, `src/Traits` |
| Jobs and notifications | `src/Jobs`, `src/Notifications` |
| Shared support/helpers | `src/Support`, `src/helpers.php` |
| Package migrations | `database/migrations` |
| Generator/view stubs | `stubs` |
| Auth package | `src/packages/auth/src` |
| Datatables package | `src/packages/datatables/src`, plus `config`, `routes`, `resources`, `lang` |
| Lang extractor package | `src/packages/lang-extractor/src` |
| Sidebar package | `src/packages/sidebar/src` |
| Toastify package | `src/packages/toastify/src`, plus `config` and `resources` |
| Core tests | `tests/Feature/Core`, `tests/Unit/Core` |
| Package tests | `tests/Feature/Packages`, `tests/Unit/Packages` |
| Test fixtures | `tests/Fixtures` |

## PSR-4 autoload mapping

The directory layout above is mirrored exactly by `autoload.psr-4` in `composer.json`. Keeping these aligned is a hard rule — the root namespace covers `src/`, and each sub-package gets its own namespace so it can be reasoned about (and tested) independently.

```json
"autoload": {
    "psr-4": {
        "Redot\\": "src/",
        "Redot\\Auth\\": "src/packages/auth/src",
        "Redot\\Sidebar\\": "src/packages/sidebar/src",
        "Redot\\Toastify\\": "src/packages/toastify/src",
        "Redot\\Datatables\\": "src/packages/datatables/src",
        "Redot\\LangExtractor\\": "src/packages/lang-extractor/src"
    },
    "files": [
        "src/packages/toastify/src/helpers.php",
        "src/helpers.php"
    ]
}
```

Two `files` entries are autoloaded globally on every request: the Toastify helpers and the root `src/helpers.php`. Tests use a separate dev map: `"Tests\\": "tests/"`.

So an FQCN resolves to disk like this:

- `Redot\Models\Setting` → `src/Models/Setting.php`
- `Redot\Http\Controllers\Controller` → `src/Http/Controllers/Controller.php`
- `Redot\Datatables\Columns\...` → `src/packages/datatables/src/Columns/...`
- `Redot\Sidebar\Sidebar` → `src/packages/sidebar/src/Sidebar.php`
- `Redot\Auth\Actions\...` → `src/packages/auth/src/Actions/...`

## Sub-package internals

Each sub-package keeps its own conventional substructure:

- **Auth** (`src/packages/auth/src`): `Actions`, `Concerns`, `Contracts`, `Facades`, `Middleware`, `Routes` — the action/contract/route-registrar pattern behind the `RedotAuth` facade.
- **Datatables** (`src/packages/datatables`): code under `src/` (`Actions`, `Adapters` incl. `Adapters/PDF`, `Columns`, `Commands` with `Commands/stubs`, `Exceptions`, `Filters`, `Traits`), plus its own `config`, `routes`, `lang/{ar,en}`, and `resources/{css,js,views}` (views include `filters`, `pagination`, `partials`, `pdf`).
- **Toastify** (`src/packages/toastify`): `src/` with `Concerns`, plus `config` and `resources/views`, and the globally autoloaded `src/helpers.php`.
- **Sidebar** (`src/packages/sidebar/src`): just `Item.php` and `Sidebar.php`.
- **Lang Extractor** (`src/packages/lang-extractor/src`): a single `LangExtractor.php`.

## Migrations and stubs

`database/migrations` holds the package-owned, published schema (timestamps kept consistent across releases):

```
0001_01_01_000001_create_settings_table.php
0001_01_01_000002_create_languages_table.php
0001_01_01_000003_create_language_tokens_table.php
0001_01_01_000004_create_login_tokens_table.php
0001_01_01_000005_create_permission_tables.php
```

`stubs/` holds the templates the generator commands (e.g. `EntityMakeCommand`, `ViewMakeCommand`) emit into a consuming app: `dashboard.create.stub`, `dashboard.edit.stub`, `dashboard.index.stub`, `dashboard.index-datatable.stub`, `dashboard.show.stub`, and `website.page.stub`.

## How the consumer wires up

The Redot Dashboard app pulls this in as `"redot/core": "^0.1"` and consumes the FQCNs directly. Real examples from `/home/abdelrhman/projects/dashboard`:

```php
// app/sidebar.php
use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

// app/Http/Controllers/Api/Dashboard/ProfileController.php
use Redot\Http\Controllers\Controller;
use Redot\Traits\CanUploadFile;

// app/Http/Controllers/Dashboard/ExtractLanguageTokensController.php
use Redot\Jobs\ExtractLanguageTokens;
use Redot\Models\Language;
```

The app can also reach published package assets by path, e.g. its `config/datatables.php` references files inside the installed package:

```php
'file' => base_path('vendor/redot/core/src/packages/datatables/resources/css/datatables.css'),
'file' => base_path('vendor/redot/core/src/packages/datatables/resources/js/datatables.js'),
```

## Gotchas

- **Keep the PSR-4 map and the directory layout in lockstep.** Moving a sub-package directory without updating `composer.json` (and running `composer dump-autoload`) breaks autoloading.
- **Provider auto-discovery is load-bearing.** `Redot\RedotServiceProvider` is registered via `extra.laravel.providers`; don't remove it or apps stop booting the package.
- **Two global `files` helpers** (`src/helpers.php` and the Toastify helpers) are always loaded — treat their function names as public surface.
- **Don't touch** `vendor`, `.phpunit.cache`, generated `storage`, or consuming-app files in this repo's tasks.

## Related pages

- [Datatables](/packages/datatables/overview)
- [Sidebar](/packages/sidebar)
- [Toastify](/packages/toastify)
- [Auth](/packages/auth/overview)
