# Datatable Frontend & Scaffolding

This page covers getting a datatable onto a page: scaffolding the class, dropping it into a view, and loading its assets. See the [Datatables overview](/packages/datatables/overview) for columns, filters, actions, and export.

## Scaffold a datatable

Generate a datatable class with the `make:datatable` command, naming the table and the model it lists:

```bash
# Provide the model up front
php artisan make:datatable Users --model=User

# Or be prompted for the model
php artisan make:datatable Users

# Overwrite an existing class
php artisan make:datatable Users --model=User --force
```

Options:

- **`--model`** / **`-m`** — the model the table lists. A bare name like `User` resolves to `App\Models\User`; omit it and you'll be prompted.
- **`--force`** / **`-f`** — overwrite an existing datatable class.

The generated class lives under `App\Livewire\Datatables` with empty `query()`, `columns()`, `actions()`, and `filters()` methods ready to fill in:

```php
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

## Render it on a page

A datatable is a Livewire component, so mount it with Livewire's tag syntax. Public properties pass through as attributes:

```blade
<livewire:datatables.users />
```

## Assets

The package ships plain JS and CSS under `public/vendor/datatables` — there's no build step or `npm` publish. Link them once (like `storage:link`), then the table injects them automatically the first time one renders:

```bash
php artisan datatables:link
php artisan datatables:link --force
php artisan datatables:link --relative
```

- **`--force`** — recreate the symlink if it already exists.
- **`--relative`** — create a relative symlink (useful in some deploy layouts).

Prefer a copy instead of a symlink? Publish the assets:

```bash
php artisan vendor:publish --tag=datatables::assets --force
```

Re-run the link (or publish with `--force`) after upgrading `redot/core` so CSS/JS stay current. The scripts expect jQuery, Bootstrap dropdowns, and Livewire to be present globally. See the [Asset & Init System](/frontend/asset-system) for how front-end assets are served.

If you define a global `warnBeforeAction` function, confirmable [actions](/packages/datatables/actions) use it for their prompt; otherwise the browser's native confirm dialog is used.

## Customizing the markup and strings

Publish the package's views or translations to override them:

```bash
# Blade views -> resources/views/vendor/datatables
php artisan vendor:publish --tag=datatables::views

# Translations -> lang/vendor/datatables
php artisan vendor:publish --tag=datatables::lang
```

All built-in labels, placeholders, and messages are localizable through the published translation files.

## Related

- [Datatables overview](/packages/datatables/overview)
- [Asset & Init System](/frontend/asset-system)
- [Components overview](/components/overview)
