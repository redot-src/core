# Datatables Overview

The Datatables package (`Redot\Datatables`) provides an abstract, Livewire-powered table component for Eloquent models. You extend the `Redot\Datatables\Datatable` base class, declare a query, columns, filters, and actions, and the package handles searching, sorting, pagination, filtering, exporting, and rendering for you.

## Key Concepts

A datatable is a Livewire component. `Datatable` extends `Livewire\Component` and pulls in `WithPagination`, the package's `InteractsWithRelations` trait, the Toastify `InteractsWithToastify` trait, and `Macroable`. Because it is a Livewire component you place it on a page with the standard `<livewire:...>` tag and all interaction (search, sort, paginate, filter) happens over Livewire round-trips.

You build a concrete table by extending `Datatable` and supplying:

- A query source — either set the `protected string $model` property or override `query(): Builder`.
- `columns(): array` — required (abstract); returns `Redot\Datatables\Columns\Column` instances.
- `filters(): array` — optional; returns `Redot\Datatables\Filters\Filter` instances.
- `actions(): array` — optional; returns `Redot\Datatables\Actions\Action` / `ActionGroup` instances.

See the dedicated pages for the building blocks: [Columns](/packages/datatables/columns), [Filters](/packages/datatables/filters), and [Actions](/packages/datatables/actions).

## The Query Source

`query()` returns an `Illuminate\Database\Eloquent\Builder`. The base implementation builds a query from the `$model` property:

```php
public function query(): Builder
{
    if (isset($this->model)) {
        return app($this->model)->query();
    }

    throw new Exceptions\ResourceNotFoundException('Resource not found. Please set the model property in your datatable class.');
}
```

You may either set `protected string $model = User::class;` or override `query()` to return any builder you like — including scoped queries. The dashboard `Memos` table, for example, scopes to the current admin:

```php
public function query(): Builder
{
    return Memo::forAuthenticatedAdmin();
}
```

If neither `$model` is set nor `query()` is overridden, a `ResourceNotFoundException` is thrown.

## Defining a Datatable

Generate one with the included Artisan command (registered by the service provider):

```bash
php artisan make:datatable Users --model=User
```

The command lives at `Redot\Datatables\Commands\DatatableMakeCommand`, generates into the app's `App\Livewire\Datatables` namespace, and accepts `--model` (`-m`) and `--force` (`-f`). If `--model` is omitted you are prompted for it; the model must exist or the command re-prompts.

Here is the real `App\Livewire\Datatables\Users` datatable from the consumer dashboard:

```php
<?php

namespace App\Livewire\Datatables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Redot\Datatables\Filters\TrashedFilter;

class Users extends Datatable
{
    public function query(): Builder
    {
        return User::query();
    }

    public function columns(): array
    {
        return [
            TextColumn::make('full_name', __('Name'))
                ->width('100%')
                ->minWidth('300px')
                ->searchable()
                ->sortable(),
            TextColumn::make('email', __('Email'))
                ->width('300px')
                ->email()
                ->searchable(),
            TernaryColumn::make('email_verified_at', __('Verified')),
        ];
    }

    public function actions(): array
    {
        return Datatable::defaultActionGroup([
            Action::view('dashboard.users.show')->visible(route_allowed('dashboard.users.show')),
            Action::edit('dashboard.users.edit')->visible(route_allowed('dashboard.users.edit')),
            Action::make(__('Impersonate'), 'fas fa-user-secret')
                ->visible(route_allowed('dashboard.impersonate-users.create'))
                ->condition(fn (User $user) => ! $user->trashed())
                ->route('dashboard.impersonate-users.store', method: 'post', bounded: false)
                ->body(['user_id' => fn (User $user) => $user->id])
                ->confirmable(message: __('Are you sure you want to impersonate this user?')),
            Action::delete('dashboard.users.destroy')->visible(route_allowed('dashboard.users.destroy'))->condition(fn (User $user) => ! $user->trashed()),
            Action::restore('dashboard.users.restore')->visible(route_allowed('dashboard.users.restore'))->condition(fn (User $user) => $user->trashed()),
        ]);
    }

    public function filters(): array
    {
        return [
            StringFilter::make('full_name', __('Name')),
            StringFilter::make('email', __('Email')),
            TernaryFilter::make(label: __('Verified'))
                ->queries(
                    yes: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                    no: fn (Builder $query) => $query->whereNull('email_verified_at')
                ),
            TrashedFilter::make(),
        ];
    }
}
```

## Rendering on a Page

Render the component using Livewire's tag syntax. Public properties can be passed as attributes (for example the `LanguageTokens` table receives a bound `language`):

```blade
<livewire:datatables.users />

<livewire:datatables.language-tokens :language="$language" />
```

## Public Surface & Configurable Properties

`Datatable` exposes a number of public properties you can override on your subclass to tune behavior and appearance:

| Property | Default | Purpose |
| --- | --- | --- |
| `$id` | `uniqid('datatable-')` | Unique DOM id, auto-generated if unset. |
| `$perPageOptions` | `[5, 10, 25, 50, 100, 250, 500]` | Page-size choices. |
| `#[Url] $perPage` | `10` | Current page size (bound to query string). |
| `#[Url(as: 'q')] $search` | `''` | Global search term. |
| `#[Url(as: 'sort')] $sortColumn` | `''` | Active sort column. |
| `#[Url(as: 'direction')] $sortDirection` | `'desc'` | Sort direction. |
| `#[Url(as: 'filter')] $filtered` | `[]` | Applied filter values, keyed by filter index. |
| `$height` | `'auto'` | Max table height. |
| `$stickyHeader` | `true` | Sticky header toggle. |
| `$bordered` | `true` | Bordered styling toggle. |
| `$exportable` | `true` | Master export toggle. |
| `$allowedExports` | derived from config | Export formats enabled (e.g. `['xlsx','csv','json','pdf']`). |
| `$pdfTemplate` / `$pdfAdapter` / `$pdfOptions` | from config | PDF export settings. |
| `$emptyMessage` | `datatables::datatable.empty` | Shown when there are no rows. |

The `#[Url]` attributes mean `perPage`, search (`q`), sort (`sort`/`direction`), and filters (`filter`) are reflected in and restored from the URL query string.

### Lifecycle / render

`render()` returns the `datatables::datatable` view, populated by `viewData()`. `viewData()` resets the filter counter, computes the visible columns and actions, builds the query, paginates it with `$this->perPage`, and exposes flags such as `searchable`, `filterable`, and `exportable` (each computed from the column/filter set). It also computes `colspan` and whether the filter panel should start open (`count($this->filtered) > 0`).

### Query building

Internally `getQueryBuilder()` applies, in order:

1. **Filters** — `applyFilters()` separates `global` filters (applied directly to the root query) from the rest (wrapped in a nested `where`). A filter contributes nothing when its value is `null` or `''`. A filter with a custom `query` closure runs that closure; otherwise its `apply()` method is used.
2. **Global search** — `applyGlobalSearch()` runs only when `$search` is set, OR-ing across every `searchable` column. A column with a `searcher` closure uses it; a relationship column (name contains `.`) searches within the relation via `whereHas`; otherwise a plain `orWhere(..., 'like', '%term%')` is used.
3. **Sorting** — `applySorting()` orders by the model primary key when no sort column is set. With a sort column it finds the matching `sortable` column; a `sorter` closure takes precedence, a relationship column is sorted via `withAggregate(...)`, and otherwise a plain `orderBy` is applied. An unknown sort column throws `InvalidColumnException`.

### Sorting & search helpers

`sort(?string $column = null)` toggles direction when called with the active column, switches to a new column (ascending) otherwise, and clears sorting when passed `null`. `refresh()` simply calls `resetPage()`.

### Relationship handling

The `InteractsWithRelations` trait powers searching/sorting across dotted relation paths (e.g. `profile.name`). `withRelation()` / `orWithRelation()` apply a constraint to the base query when there is no `.`, or to the related query via `whereHas` / `orWhereHas` otherwise. Relation path and field are split with `Str::beforeLast`/`Str::afterLast`, so multi-level paths like `users.profile.name` are supported.

## Default Action Group

`Datatable::defaultActionGroup(array $actions, ?string $label = null, ?string $icon = null): array` is a helper for collapsing a long action list into a dropdown. On desktop it shows the first 2 actions inline and groups the rest into an `ActionGroup` (icon defaults to `fas fa-ellipsis-v`); on mobile (`is_mobile()`) all actions are grouped. If the total count is small enough (`<= offset + 1`) every action is shown inline. See [Actions](/packages/datatables/actions) for building the actions themselves.

## Running Inline Actions

`runAction(string $name, mixed $key): mixed` executes an action's callback against a row. It resolves the action by name (searching inside action groups too), loads the row via `findOr` falling back to `withTrashed()->find()`, and verifies `shouldRender($row)`. On success it invokes the action's `success` callback; on a thrown `Throwable` it invokes the `failure` callback if one is registered, otherwise re-throws. Missing actions/rows or unavailable actions raise `InvalidActionException`.

## Exporting

When `$exportable` is true and at least one exportable column exists, the configured formats are offered. Each export method calls `getExportData()`, which collects columns where both `exportable` and `visible` are true, uses their `label` as headings, runs the **full filtered query** (no pagination), and maps each row through `Column::get()`.

- `toXlsx(): BinaryFileResponse` and `toCsv(): BinaryFileResponse` require `maatwebsite/excel` (a `MissingDependencyException` is thrown if missing) and stream a `strip_tags`-sanitized download named `export-{timestamp}.{ext}`.
- `toJson(): StreamedResponse` streams a sanitized, pretty-printed JSON download.
- `toPdf(): StreamedResponse|Response` instantiates `$pdfAdapter` (default `Redot\Datatables\Adapters\PDF\LaravelMpdf`), verifies it is a supported `Adapter`, and renders the configured `$pdfTemplate`. PDF export does **not** strip tags.

## Configuration

Config lives at `config/datatables.php` (publish tag `datatables::config`). The defaults:

```php
'assets' => [
    'css' => [
        'file'  => '.../resources/css/datatables.css',
        'uri'   => 'datatables/datatables.css',
        'route' => 'datatables.css',
    ],
    'js' => [
        'file'  => '.../resources/js/datatables.js',
        'uri'   => 'datatables/datatables.js',
        'route' => 'datatables.js',
    ],
],

'export' => [
    'xlsx' => ['enabled' => true],
    'csv'  => ['enabled' => true],
    'json' => ['enabled' => true],
    'pdf'  => [
        'enabled'  => true,
        'template' => 'datatables::pdf.default',
        'adapter'  => LaravelMpdf::class,
        'options'  => ['format' => 'A4', 'orientation' => 'P'],
    ],
],
```

`$allowedExports` is derived in the constructor from the `export` keys whose `enabled` flag is true (unless you set `$allowedExports` explicitly on your subclass). PDF defaults (`pdfTemplate`, `pdfAdapter`, `pdfOptions`) are pulled from `export.pdf.*`; `pdfOptions` is merged on top of the config options.

## Assets & Routes

The service provider loads `routes/datatable.php`, which registers a GET route per configured asset to serve the package CSS/JS files directly from disk, named `datatables.css` and `datatables.js`. The `Datatable` constructor builds cache-busted URLs to these routes using `filemtime()` of the asset file, exposed as the `$cssAssetsUrl` and `$jsAssetsUrl` public properties for the view to load.

## Service Provider & Publishing

`Redot\Datatables\DatatablesServiceProvider` boots config, views, translations, and routes, and registers the `make:datatable` command. Publish tags:

- `datatables::config` → `config/datatables.php`
- `datatables::views` → `resources/views/vendor/datatables`
- `datatables::lang` → `lang/vendor/datatables`

## Gotchas

- `columns()` is abstract — every datatable must implement it.
- A global search OR-matches every `searchable` column; mark only the columns you want searchable.
- Filters are skipped when their value is empty (`null` or `''`), so empty filter inputs are harmless.
- Sorting a column that is not registered/sortable throws `InvalidColumnException`.
- XLSX/CSV exports hard-depend on `maatwebsite/excel`; without it those methods throw `MissingDependencyException`.
- Search/sort/filter/per-page state is persisted in the URL via Livewire `#[Url]`, so links and refreshes preserve table state.
