# Datatable Filters

Filters add structured, column-aware filtering to a [Datatable](/packages/datatables/overview). Each filter is a small class that renders a control in the filter panel and translates the user's input into Eloquent query constraints. The package ships with six filter types — string, number, date, select, ternary, and trashed — all extending a shared base class.

## Key Concepts

Every filter extends the abstract `Redot\Datatables\Filters\Filter` class. A filter is responsible for two things:

1. Rendering its UI (via a Blade `$view`).
2. Applying its value to the query in `apply(Builder $query, mixed $value): void`.

You declare filters by returning instances from your datatable's `filters()` method. Each filter is built with the static `make()` factory and configured fluently.

```php
public function filters(): array
{
    return [
        StringFilter::make('name', __('Name')),
        TernaryFilter::make('active', __('Active')),
    ];
}
```

### How filters are applied

The datatable holds user input in the public `$filtered` array, keyed by each filter's auto-incrementing `index`. When building the query, `Datatable` separates **global** filters from **nested** filters:

- **Global** filters (`$global = true`) are applied directly to the query.
- **Nested** filters are grouped together inside a single wrapping `where(...)` closure.

For each filter, the datatable reads `$filtered[$filter->index]`. If the value is `null` or `''` the filter is skipped. Otherwise, if a custom `query()` closure was set on the filter it is invoked with `($query, $value)`; if not, the filter's own `apply()` runs.

### Columns, relations, and OR logic

A filter can target a single column, multiple columns, or a related column using dot notation (e.g. `profile.name`). The base class resolves these through the `BuildAttributes` and `InteractsWithRelations` traits:

- Multiple columns are combined with **OR** logic by default (`$or = true`). The conditions are wrapped in a grouping `where(...)`, with the first column using `where`/`whereHas` and subsequent columns using `orWhere`/`orWhereHas`. Call `->or(false)` to combine columns with AND instead.
- A column containing a `.` is split into a relation path and field, and applied via `whereHas` / `orWhereHas` on the relation. So `posts.title` filters the parent rows whose related `posts` match the field constraint.

## The Base Filter

`Redot\Datatables\Filters\Filter` (abstract). Uses `Macroable`, so you can extend filters with `Filter::macro()`.

Constructor / factory:

```php
public function __construct(string|array|null $column = null, ?string $label = null)
public static function make(string|array|null $column = null, ?string $label = null): static
```

Shared public properties:

- `int $index` — unique, auto-incremented identifier (also the key used in `$filtered`).
- `string $wireKey` — Livewire key, defaults to `filtered.{index}`.
- `?string $label` — display label.
- `string|array|null $column` — target column(s).
- `bool $or = true` — combine multiple columns with OR.
- `?Closure $query` — overrides `apply()` when set.
- `bool $global = false` — apply outside the nested filter group.
- `string $view` — the Blade view rendered for the control.

Shared fluent setters:

```php
->label(string $label): static
->column(string|array $column): static
->columns(array $columns): static     // alias for column() with an array
->or(bool $or = true): static
->query(Closure $query): static       // signature: fn (Builder $query, mixed $value)
```

`init()` is an empty hook called at the end of the constructor; concrete filters override it to set up defaults (operators, options, labels, placeholders).

## StringFilter

`Redot\Datatables\Filters\StringFilter` — text input plus an operator dropdown. View: `datatables::filters.string`.

Its value is an array `['operator' => ..., 'value' => ...]`. Operator defaults to `equals`; an empty `value` skips the filter. Supported operators:

`equals`, `not_equals`, `contains`, `not_contains`, `starts_with`, `not_starts_with`, `ends_with`, `not_ends_with` — mapped to the appropriate `where` / `like` / `not like` clauses.

```php
StringFilter::make('title', __('Title')),
StringFilter::make('slug', __('Shortened Url')),
```

## NumberFilter

`Redot\Datatables\Filters\NumberFilter` — numeric input with an operator dropdown. View: `datatables::filters.number`.

Same `['operator' => ..., 'value' => ...]` value shape as `StringFilter`, defaulting to `equals` and skipping empty values. Operators:

`equals`, `not_equals`, `greater_than`, `greater_than_or_equals`, `less_than`, `less_than_or_equals`.

```php
NumberFilter::make('clicks', __('Clicks')),
```

## DateFilter

`Redot\Datatables\Filters\DateFilter` — a date range. View: `datatables::filters.date`.

Its value is an array `['from' => ..., 'to' => ...]`. If both are empty the filter is skipped. Otherwise:

- `from` only → `whereDate($column, '>=', $from)`
- `to` only → `whereDate($column, '<=', $to)`
- both → `whereBetween($column, [$from, $to])`

```php
DateFilter::make('date', __('Date')),
```

## SelectFilter

`Redot\Datatables\Filters\SelectFilter` — a dropdown of predefined options. View: `datatables::filters.select`.

Applies a plain equality match (`where($column, $value)`). Extra setters:

```php
->placeholder(string $placeholder): static
->options(array|Collection $options): static
```

`placeholder` defaults to `datatables::datatable.filters.select.placeholder`.

```php
SelectFilter::make('status', __('Status'))
    ->options([
        'draft' => __('Draft'),
        'published' => __('Published'),
    ]),
```

## TernaryFilter

`Redot\Datatables\Filters\TernaryFilter` — a three-state (yes / no / empty) dropdown. View: `datatables::filters.ternary`.

By default the value `yes` matches `where($column, true)`, `no` matches `where($column, false)`, and `empty` matches `whereNull($column)` (all routed through the column/relation handling). A value outside these keys is ignored.

Customization:

```php
->queries(?Closure $yes = null, ?Closure $no = null, ?Closure $empty = null): static
->labels(?string $yes = null, ?string $no = null, ?string $empty = null): static
->placeholder(string $placeholder): static
->empty(bool $empty = true): static     // expose the "empty"/null option
```

Default labels and placeholder come from `datatables::datatable.filters.ternary.*`.

Simple boolean column:

```php
TernaryFilter::make('active', __('Active')),
TernaryFilter::make('is_published', __('Published')),
```

Custom queries (from the Users datatable — the value is derived from `email_verified_at` rather than a boolean column):

```php
use Illuminate\Database\Eloquent\Builder;

TernaryFilter::make(label: __('Verified'))
    ->queries(
        yes: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
        no: fn (Builder $query) => $query->whereNull('email_verified_at')
    ),
```

## TrashedFilter

`Redot\Datatables\Filters\TrashedFilter` — controls soft-deleted rows. View: `datatables::filters.trashed`.

This filter is **global** (`$global = true`), so it is applied directly to the query rather than inside the nested filter group. In `init()` it defaults its label to `datatables::datatable.filters.trashed.label` and its column to `deleted_at`.

Its value maps to soft-delete scopes:

- `with` → `withTrashed()`
- `only` → `onlyTrashed()`
- anything else → `withoutTrashed()`

```php
TrashedFilter::make(),
```

> Requires the underlying model to use `SoftDeletes`.

## Usage

A complete `filters()` method, drawn from the dashboard's `Users` datatable:

```php
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Redot\Datatables\Filters\TrashedFilter;

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
```

### Filtering across multiple columns

Pass an array of columns (or call `->columns()`). They are combined with OR by default; use `->or(false)` for AND:

```php
StringFilter::make(['first_name', 'last_name'], __('Name')),

StringFilter::make()
    ->columns(['title', 'body'])
    ->label(__('Content'))
    ->or(false),
```

### Filtering through a relation

Use dot notation; the relation is queried with `whereHas`:

```php
StringFilter::make('author.name', __('Author')),
```

### Overriding the query entirely

`->query()` replaces a filter's `apply()` for the whole filter (the closure receives the raw value):

```php
SelectFilter::make('role', __('Role'))
    ->options(['admin' => 'Admin', 'editor' => 'Editor'])
    ->query(fn (Builder $query, $value) => $query->whereRelation('roles', 'name', $value)),
```

## Gotchas

- The datatable skips any filter whose `$filtered` value is `null` or `''`. Individual filters also short-circuit on their own empty cases (empty string value, empty date range, unknown ternary key).
- `index` is assigned from a static counter shared across all filter instances; the datatable keys input by this `index`, so don't rely on stable indexes between unrelated renders — let the package manage `$filtered`.
- `TrashedFilter` is global and defaults to hiding trashed rows; the model must use `SoftDeletes`.
- Labels, placeholders, and operator/ternary option text are resolved from the `datatables::datatable.filters.*` translation keys, so they are localizable.

See [Datatable Columns](/packages/datatables/columns) and the [Datatables overview](/packages/datatables/overview) for the surrounding API.
