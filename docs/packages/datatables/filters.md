# Datatable Filters

Filters add a filter panel to your table so users can narrow rows by a column's value — text matches, number comparisons, date ranges, dropdown choices, yes/no toggles, and soft-deleted rows. You return them from your datatable's `filters()` method. See the [Datatables overview](/packages/datatables/overview) for the surrounding setup.

## Usage

Build each filter with its `make()`, passing the column name and a label, and chain options onto it:

```php
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Redot\Datatables\Filters\TrashedFilter;

public function filters(): array
{
    return [
        StringFilter::make('name', __('Name')),
        TernaryFilter::make('active', __('Active')),
        TrashedFilter::make(),
    ];
}
```

A filter with no value applied simply does nothing, so users can mix and match.

## Options

These apply to every filter:

- **`label`** — the label shown above the control.
- **`column`** / **`columns`** — the column(s) to filter on. Use a dotted name (e.g. `author.name`) to filter through a relationship.
- **`or`** — when filtering multiple columns, combine them with OR (the default); pass `false` for AND.
- **`query`** — replace the filter's default behavior with your own callback that constrains the query for the entered value.

## Filter types

### StringFilter

A text box with an operator dropdown — equals, contains, starts/ends with, and their negations.

```php
StringFilter::make('title', __('Title'));
```

### NumberFilter

A number box with comparison operators — equals, not equals, greater/less than (or equal).

```php
NumberFilter::make('clicks', __('Clicks'));
```

### DateFilter

A from/to date range. Supply either bound or both.

```php
DateFilter::make('date', __('Date'));
```

### SelectFilter

A dropdown of predefined choices:

- **`options`** — the selectable values (array or collection).
- **`placeholder`** — the empty-state text.

```php
SelectFilter::make('status', __('Status'))
    ->options(['draft' => __('Draft'), 'published' => __('Published')]);
```

### TernaryFilter

A three-state dropdown (yes / no / unset):

- **`labels`** — the text for the yes / no / empty choices.
- **`queries`** — custom callbacks for what "yes", "no", and "empty" mean.
- **`placeholder`** — the empty-state text.
- **`empty`** — expose the third "unset / null" option.

```php
TernaryFilter::make('is_published', __('Published'));
```

### TrashedFilter

Controls whether soft-deleted rows are hidden, included, or shown exclusively. Requires the model to use soft deletes.

```php
TrashedFilter::make();
```

## Examples

### Filtering across multiple columns

Pass an array of columns (combined with OR by default; pass `or: false` for AND):

```php
StringFilter::make(['first_name', 'last_name'], __('Name'));
```

### Filtering through a relationship

```php
StringFilter::make('author.name', __('Author'));
```

### A derived yes/no filter

When the filter doesn't map to a plain boolean column, define what each choice means:

```php
use Illuminate\Database\Eloquent\Builder;

TernaryFilter::make(label: __('Verified'))
    ->queries(
        yes: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
        no: fn (Builder $query) => $query->whereNull('email_verified_at'),
    );
```

### Custom query for any filter

`query` takes over the whole filter for the value the user enters:

```php
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('role', __('Role'))
    ->options(['admin' => 'Admin', 'editor' => 'Editor'])
    ->query(fn (Builder $query, $value) => $query->whereRelation('roles', 'name', $value));
```

## Related

- [Add & configure columns](/packages/datatables/columns)
- [Add row & bulk actions](/packages/datatables/actions)
- [Datatables overview](/packages/datatables/overview)
