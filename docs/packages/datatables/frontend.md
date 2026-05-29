# Datatable Frontend & Scaffolding

The datatables package ships with everything needed to render and operate a table in the browser: an Artisan generator (`make:datatable`), self-serving JS/CSS assets, a set of Blade views/partials, and translation strings. This page covers that surface so you can scaffold a datatable and understand what gets rendered and how the client-side behavior works.

See [Datatables Overview](/packages/datatables/overview) for the PHP `Datatable` class, columns, filters, and actions.

## Scaffolding with `make:datatable`

`Redot\Datatables\Commands\DatatableMakeCommand` registers the `make:datatable` Artisan command (a Laravel `GeneratorCommand`). It generates a class in the consumer app under the `App\Livewire\Datatables` namespace.

```bash
# Prompts for the model name interactively
php artisan make:datatable Users

# Provide the model up front
php artisan make:datatable Users --model=User
php artisan make:datatable Users -m "App\Models\User"

# Overwrite an existing datatable class
php artisan make:datatable Users --model=User --force
```

Options:

- `--model` / `-m` (optional): the model the datatable represents. If omitted, the command asks `What is the model name? (e.g. App\Models\User)`.
- `--force` / `-f`: create the class even if it already exists.

Model resolution behavior in `replaceModel()`:

1. If the given value is already a resolvable class (`class_exists`), it is used as-is.
2. Otherwise it is prefixed with the app's root namespace + `Models\` (e.g. `User` becomes `App\Models\User`).
3. If still not found, the command prints `Model does not exist!` and re-prompts (the `--model` option is cleared first to avoid an infinite loop).

The generated class is written from the stub at `src/Commands/stubs/datatable.stub` and looks like this:

```php
<?php

namespace App\Livewire\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Datatable;

class Users extends Datatable
{
    public function query(): Builder
    {
        return \App\Models\User::query();
    }

    public function columns(): array
    {
        return [
            // ...
        ];
    }

    public function actions(): array
    {
        return Datatable::defaultActionGroup([
            // ...
        ]);
    }

    public function filters(): array
    {
        return [];
    }
}
```

Because the class extends `Datatable` (a Livewire component), you mount it in a view as a Livewire component:

```blade
{{-- resources/views/dashboard/users/index.blade.php (consumer app) --}}
<livewire:datatables.users />
```

A real consumer datatable fleshes out the stub like so:

```php
public function columns(): array
{
    return [
        TextColumn::make('full_name', __('Name'))
            ->width('100%')->minWidth('300px')->searchable()->sortable(),
        TextColumn::make('email', __('Email'))
            ->width('300px')->email()->searchable(),
        TernaryColumn::make('email_verified_at', __('Verified')),
    ];
}
```

## Assets (JS & CSS)

The package serves its own JS and CSS files directly through routes — there is no build step or `npm` publish required.

The asset paths, public URIs, and route names are defined in `config/datatables.php` under `assets`:

```php
'assets' => [
    'css' => [
        'file' => dirname(__DIR__) . '/resources/css/datatables.css',
        'uri'  => 'datatables/datatables.css',
        'route' => 'datatables.css',
    ],
    'js' => [
        'file' => dirname(__DIR__) . '/resources/js/datatables.js',
        'uri'  => 'datatables/datatables.js',
        'route' => 'datatables.js',
    ],
],
```

`routes/datatable.php` (loaded automatically by `DatatablesServiceProvider`) iterates over `config('datatables.assets')` and registers a `GET` route per asset that streams the file with a `text/<extension>` content type:

```php
foreach (config('datatables.assets') as $asset) {
    Route::get($asset['uri'], function () use ($asset) {
        $path = $asset['file'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return response()->file($path, ['Content-Type' => 'text/' . $extension]);
    })->name($asset['route']);
}
```

The `Datatable` component computes cache-busted URLs to these routes, exposing them to the view as `$cssAssetsUrl` and `$jsAssetsUrl` (the `?v=` query is the `md5` of the asset file's `filemtime`). The main view injects them via Livewire's `@assets` directive so they are loaded once:

```blade
@assets
    <link rel="stylesheet" href="{{ $cssAssetsUrl }}" />
    <script src="{{ $jsAssetsUrl }}" defer></script>
@endassets
```

> The JS depends on jQuery (`$`), Bootstrap dropdowns, and Livewire (`window.Livewire`) being present on the page — it does not bundle them. The Redot Dashboard provides these globally.

### What the JS does

`resources/js/datatables.js` wires up three jQuery delegated handlers on `document`:

1. **Dropdown re-parenting** — on `show.bs.dropdown` for `.datatable-actions .dropdown`, the open `.dropdown-menu` is appended to `<body>` (to escape table overflow/stacking) and tagged with `parent-wire-id` carrying its originating Livewire root `wire:id` so context is preserved.
2. **Route actions** — clicks on `.datatable-action[method]` (excluding `method="get"`) are intercepted: the handler builds and submits a hidden `POST` form to the action's `href`, spoofing the real verb via `_method`, including `_token`, and expanding the base64-encoded `request-body` attribute into hidden inputs (arrays become `name[]` fields).
3. **Inline (Livewire) actions** — clicks on `.datatable-action[action-name]` resolve the owning Livewire component (via `parent-wire-id`, falling back to `wire:id`) and call `wire.call('runAction', name, key)` using the action's `action-name` / `action-key` attributes.

Both action handlers honor a `confirm` attribute: if present, they call the global `warnBeforeAction(callback, { content })` if it exists, otherwise fall back to the native `confirm()` dialog. These attributes are produced server-side by `$action->buildAttributes($row)` rendered in `partials/action.blade.php`.

### What the CSS does

`resources/css/datatables.css` scopes everything under `.datatable` and styles:

- **Bordered tables** (`table.bordered`) with inter-cell `border-inline-end` separators using the Tabler `--tblr-border-color` variable.
- **Fixed columns** (`.fixed-start` / `.fixed-end`) via `position: sticky`, with surface backgrounds on `.datatable-cell` and separator borders.
- **Action cells** (`td.datatable-actions`) padding, the per-page control width, dropdown `z-index: 9999`, and `user-select: none` on sortable headers.

These styles assume the Tabler CSS variables are available (provided by the dashboard theme).

## The main view & partials

The component renders `datatables::datatable` (`resources/views/datatable.blade.php`). It is a Tabler `card` with `class="card datatable"`, `wire:ignore.self`, an Alpine `x-data="{ filtersOpen }"` state seeded from `$filtersOpen`, and an optional `max-height` from `$height`.

The view composes these partials (each in `resources/views/partials/`):

| Partial | Rendered when | Purpose |
| --- | --- | --- |
| `per-page` | always | rows-per-page selector |
| `search` | `$searchable` | global search input |
| `export` | `$exportable` | export dropdown |
| `filters-toggle` | `$filterable` | button toggling `filtersOpen` |
| `filters` | `$filterable` | the filter form (in an `x-show="filtersOpen" x-cloak` card body) |
| `refresh` | always | manual refresh control |
| `table` | always | the `<table>` head/body, including `empty` |
| `pagination` | always | footer pagination |
| `action` / `action-group` | per row | renders actions (the `<a>` carrying the JS attributes) |
| `empty` | no rows | "no entries" placeholder |

Most controls are wrapped in `wire:ignore` so jQuery/Bootstrap-managed widgets are not re-rendered by Livewire. To customize any of these, publish the views (see below) and edit the copies under `resources/views/vendor/datatables`.

## Translations

`lang/en/datatable.php` is loaded under the `datatables` namespace, so reference strings as `datatables::datatable.*`:

```php
__('datatables::datatable.search');                  // "Search..."
__('datatables::datatable.empty');                   // "No entries found"
__('datatables::datatable.pagination.showing', [     // "Showing :first to :last of :total entries"
    'first' => 1, 'last' => 15, 'total' => 120,
]);
__('datatables::datatable.actions.delete');          // "Delete"
__('datatables::datatable.actions.confirm');         // default confirm message
__('datatables::datatable.filters.string.contains'); // "Contains"
```

Key groups available:

- `search`, `empty`, `yes`, `no`
- `pagination.{next,previous,empty,showing}` — `showing` takes `:first`, `:last`, `:total`.
- `exports.{excel,csv,pdf,json}`
- `actions.{view,edit,delete,restore,export,confirm}`
- `filters.select.placeholder`, `filters.number.*`, `filters.string.*`, `filters.trashed.{label,without,with,only}`, `filters.ternary.{placeholder,yes,no,empty}`

## Publishing & customization

`DatatablesServiceProvider` exposes three publish tags:

```bash
# Config -> config/datatables.php
php artisan vendor:publish --tag=datatables::config

# Blade views -> resources/views/vendor/datatables
php artisan vendor:publish --tag=datatables::views

# Translations -> lang/vendor/datatables
php artisan vendor:publish --tag=datatables::lang
```

Boot-time behavior (all automatic, no registration needed in the consumer): the provider merges the config, loads views under the `datatables` namespace, loads translations under the `datatables` namespace, registers the asset routes from `routes/datatable.php`, and registers the `make:datatable` command.

### Gotchas

- **Assets are file-streamed, not published.** You don't copy JS/CSS into `public/`; they are served by named routes (`datatables.css`, `datatables.js`). If you change the asset `uri`/`route` in config, update both consistently — the URL is built from `route(...)` and the cache-bust hash from `filemtime` of `file`.
- **The JS requires global jQuery, Bootstrap dropdowns, and Livewire.** Without them the action/dropdown handlers won't function.
- **`warnBeforeAction` is optional.** If your app defines this global, confirmable actions use it; otherwise the browser's native `confirm()` is used.
- **`request-body` is base64-encoded JSON.** Route actions decode it with `atob` + `JSON.parse`; values that are arrays become `name[]` form fields.
