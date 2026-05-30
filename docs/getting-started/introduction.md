# Introduction

`redot/core` is the foundation package behind the **Redot Dashboard**. It gives a
consuming Laravel app a ready-made admin back-office and public website: shared
models, helpers, console commands, validation rules, page layouts, and a set of
bundled feature packages. You install it as a normal Composer dependency and
build your application's screens on top of it.

## What you can build

With `redot/core` in place you get the building blocks for a full dashboard:

- A back-office with a sidebar, navbar, themeable layouts, and CRUD sections you
  can scaffold in one command.
- A public website surface with its own layouts and routes.
- Localized, RTL-aware UI driven by a database-backed translation workflow.
- Editable application settings (logos, app name, theme, analytics keys, …)
  surfaced through a settings form and read with the [`setting()` helper](/foundation/settings).
- Livewire data tables, toast notifications, and authentication flows out of the
  box.

Everything is wired up automatically when the package is installed — there is
nothing to register by hand.

## Bundled packages

The core ships a handful of feature packages that come online with it:

- **[Auth](/packages/auth/overview)** — the dashboard's authentication flows.
- **[Datatables](/packages/datatables/overview)** — Livewire-backed data tables
  with reusable columns, filters, and actions.
- **[LangExtractor](/packages/lang-extractor)** — extracts translatable strings
  so they can be synced, published, and reverted.
- **[Sidebar](/packages/sidebar)** — the dashboard navigation builder.
- **[Toastify](/packages/toastify)** — toast notifications.

## Requirements

- **PHP** 8.3+
- **Laravel** 13+
- **Livewire** 4.2+

Installing the package brings its own runtime dependencies (Sanctum, the Spatie
permission package, image handling, and a phone-number library) along with it —
you don't add them yourself.

## Get started

```bash
composer require redot/core
```

See [Installation](/getting-started/installation) for publishing config, stubs,
and migrations, then [Project Structure](/getting-started/project-structure) for
where things go in your app.

## Related

- [Installation](/getting-started/installation) — install and publish.
- [Configuration](/architecture/configuration) — the `config/redot.php` keys.
- [Layouts](/layouts/overview) — the page layouts your views wrap in.
