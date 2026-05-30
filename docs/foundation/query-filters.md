# Query Filters

Query filters power the dashboard's visual query builder: they describe the
filterable fields a model exposes (their labels, types, and value lists), and
they apply the rule tree the UI submits back onto an Eloquent or database query.
Field identifiers are encrypted end-to-end, so the client can never filter on an
arbitrary column or inject SQL.

## Usage

Two steps: build the field definitions the UI renders from, then apply the rules
it sends back.

```php
use Redot\Support\QueryFilters;

// 1. Build definitions for the query-builder UI (from a model or a raw map).
$filters = QueryFilters::resolve(Post::class);

// 2. Apply the submitted rule tree to a query.
$results = QueryFilters::query($rules, Post::query())->paginate();
```

Because the second call accepts an Eloquent or base builder, you can compose it
with constraints you've already applied (scopes, joins, etc.).

## Defining a model's filters

A model exposes filters in one of two ways:

- **Explicit schema (recommended)** — define a static `getTableSchema()` that
  returns a map of field definitions. This gives you full control over labels,
  types, and value lists.
- **Auto-derived** — with no schema, the columns are introspected from the
  table, labelled from their names, and mapped to filter types. Hidden columns
  are skipped, and JSON columns are dropped (add them explicitly if you need
  them).

A schema entry is keyed by field name and supports:

- **`title`** — the label shown in the UI (required).
- **`type`** — the value type: `integer`, `double`, `string`, `date`,
  `datetime`, `time`, or `boolean`. It decides the available operators and input.
- **`values`** — an option list/map (or a callable returning one). Providing it
  turns the input into a select and limits operators to equality/null checks.
- **`query`** — a raw SQL expression to filter on *instead* of the column named
  by the key. Useful for computed/concatenated fields.

```php
public static function getTableSchema(): array
{
    return [
        'id'          => ['title' => __('ID'), 'type' => 'integer'],
        'title'       => ['title' => __('Title'), 'type' => 'string'],
        'status'      => ['title' => __('Status'), 'type' => 'string', 'values' => ['draft' => __('Draft'), 'published' => __('Published')]],
        'category_id' => ['title' => __('Category'), 'type' => 'integer', 'values' => Category::pluck('name', 'id')],
        'published_at' => ['title' => __('Published At'), 'type' => 'datetime'],
    ];
}
```

## Examples

### Filtering on a computed field

Use a `query` expression to filter on a concatenated value rather than a single
column:

```php
'name' => [
    'title' => __('Name'),
    'type'  => 'string',
    'query' => "CONCAT(first_name, ' ', last_name)",
],
```

### Resolving definitions for the query-builder component

The dashboard's `<x-query-builder>` component resolves definitions and hands the
result to the front-end:

```php
$this->filters = QueryFilters::resolve($this->model, $this->filters);
```

### Applying submitted rules

```php
$results = QueryFilters::query($rules, Post::query())->paginate();
```

## Notes

- **Don't build field identifiers by hand.** Only identifiers produced by
  `resolve()` are valid; this is what stops the client from filtering on
  arbitrary columns or injecting SQL.
- **`getTableSchema()` wins** over auto-derivation when both could apply.
- **Selects are operator-limited** to equality/null checks, regardless of the
  underlying type.

## Related

- [Datatables](/packages/datatables/overview) — filterable tables that pair with
  these definitions.
- [Models](/foundation/models) — models that expose a filter schema.
