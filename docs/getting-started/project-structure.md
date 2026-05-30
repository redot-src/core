# Project Structure

This page is a usage-oriented orientation: where things go in a Redot Dashboard
app, so you know which file to open when you want to change something. The
package itself ships behind Composer in `vendor/` — you work in your own app
directory and lean on what the package provides.

## Where you put your code

A Redot Dashboard app follows the standard Laravel layout. The places you touch
most often are:

- **`app/sidebar.php`** — your dashboard navigation. Edit this file to add,
  reorder, or nest menu items. See [Sidebar](/packages/sidebar).
- **`app/Http/Controllers/`** — your dashboard and website controllers. Scaffold
  a full CRUD controller with [`make:entity`](/commands/scaffolding-and-stubs).
- **`app/Models/`** — your application models.
- **`resources/views/`** — your Blade views. Scaffolded CRUD views land under
  `resources/views/dashboard/<resource>/`.
- **`resources/layouts/`** — your page layouts and their partials (sidebar,
  navbar, footer). See [Layouts](/layouts/overview).
- **`resources/templates/`** — small reusable partials for PDF export and remote
  select options. See [Templates](/layouts/templates).
- **`config/redot.php`** — the dashboard config, once published. See
  [Configuration](/architecture/configuration).
- **`stubs/`** — published generator stubs you can edit to change scaffolded
  markup. See [Scaffolding & Stubs](/commands/scaffolding-and-stubs).

## Where data lives

- **Settings** — editable application settings (logos, app name, theme,
  analytics keys) are stored in the database and read with the
  [`setting()` helper](/foundation/settings), not from config files.
- **Languages & translation tokens** — the database-backed translation workflow
  stores its strings in the database; the
  [`lang:*` commands](/commands/overview#language-token-commands) move them to
  and from your `lang/` files. See [Localization](/foundation/localization).
- **Migrations** — the dashboard's tables (settings, languages, translation
  tokens, login tokens, permissions) come from the package and are installed by
  publishing migrations and running `php artisan migrate`. See
  [Installation](/getting-started/installation).

## Frontend assets

The dashboard frontend ships as static assets under `public/assets`, with a
build step that compiles per-locale translations and component initializers. You
don't run a bundler for the platform layer. See the
[Asset & Init System](/frontend/asset-system).

## Using what the package provides

The package exposes helpers, models, traits, casts, and rules under the `Redot\`
namespace. Reach for them from your own classes — for example the base
controller, the file-upload trait, the language and settings models, or the
language-token jobs. The bundled feature packages (Auth, Datatables, Sidebar,
Toastify, LangExtractor) each have their own documentation page linked below.

## Related

- [Installation](/getting-started/installation)
- [Layouts](/layouts/overview)
- [Datatables](/packages/datatables/overview)
- [Sidebar](/packages/sidebar)
- [Toastify](/packages/toastify)
- [Auth](/packages/auth/overview)
