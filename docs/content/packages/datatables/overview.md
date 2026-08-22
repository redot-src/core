# Datatables Overview

Datatables gives you a searchable, sortable, filterable, paginated, exportable table for any Eloquent model with very little code. You declare a query and a set of columns, and the package handles the rest — including a live, Livewire-powered frontend that preserves table state in the URL.

## Quick start

Generate a table class for a model:

```bash
php artisan make:datatable Users --model=User
```

Then declare its columns:

```php
namespace App\Livewire\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Datatable;

class Users extends Datatable
{
    public function query(): Builder
    {
        return User::query();
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name'))->searchable()->sortable(),
            TextColumn::make('email', __('Email'))->email()->searchable(),
            TernaryColumn::make('email_verified_at', __('Verified')),
        ];
    }
}
```

Drop it on a page with Livewire's tag syntax — public properties (like a bound `category`) pass through as attributes:

```blade
<livewire:datatables.users />
```

That's a working table with search, sorting, pagination, and export. Point `query()` at any builder you like, including scoped queries (e.g. only the current user's rows).

## Common tasks

- [Add & configure columns](/packages/datatables/columns) — text, dates, numbers, status, tags, icons, colors, and ternary cells.
- [Add filters](/packages/datatables/filters) — string, number, date-range, select, ternary, and soft-delete filters.
- [Add row & bulk actions](/packages/datatables/actions) — view/edit/delete buttons, custom links, confirmations, and dropdown groups.
- [Enable PDF export](/packages/datatables/export) — and other export formats.
- [Scaffold & use the frontend](/packages/datatables/frontend) — the generator, assets, and rendering.

## Options

Set these on your datatable class to tune behavior and appearance:

- **`perPageOptions`** — the page-size choices offered in the per-page selector.
- **`perPage`** — the starting page size.
- **`height`** — a max table height (the body scrolls beyond it).
- **`stickyHeader`** — keep the header visible while scrolling.
- **`bordered`** — toggle bordered cell styling.
- **`exportable`** — master switch for the export menu.
- **`allowedExports`** — which export formats to offer.
- **`emptyMessage`** — the text shown when there are no rows.

Search, sort, page size, and applied filters are reflected in the URL, so links and refreshes preserve table state. Sort uses `?sort=title,-age` — a bare column is ascending, a leading `-` is descending.

## Related

- [Components overview](/components/overview) — the form fields and UI to build your pages from.
- [Asset & Init System](/frontend/asset-system) — how front-end assets are served.
