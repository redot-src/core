# Query Filters

`Redot\Support\QueryFilters` is the bridge between the front-end query builder UI and your Eloquent/database queries. It has two jobs: build the **filter definitions** (fields, types, operators, inputs) the UI renders from, and **apply** a rule tree submitted by that UI back onto a query — supporting both plain columns and raw SQL expressions, with the field name encrypted end-to-end so the client can never inject an arbitrary column.

## Key concepts

The class exposes a tiny static surface backed by instance methods:

```php
namespace Redot\Support;

// Build the definitions the UI consumes.
QueryFilters::resolve(?string $model = null, ?array $filters = null): array

// Apply a rule tree (from the UI) onto a query.
QueryFilters::query(array $rules, EloquentBuilder|BaseQueryBuilder|null $query = null): EloquentBuilder|BaseQueryBuilder
```

The two halves are tied together by the `field` value. `resolve()` produces an **encrypted** `field` for every definition; the UI sends it back verbatim inside each rule, and `query()` decrypts it before touching the database. The decrypted value carries a prefix — `field:` for a real column or `query:` for a raw SQL expression — which decides how the rule is applied.

## Building definitions: `resolve()`

`resolve()` accepts either a model class or a raw filter map. If you pass `$filters` it is used directly; otherwise it is derived from `$model`.

### From a model

A model contributes filters in one of two ways:

1. **Explicit schema** — if the model defines a static `getTableSchema(): array`, that array is used as-is. This is the recommended path because it lets you control titles, types, and value lists.
2. **Auto-derived** — otherwise the class must extend `Illuminate\Database\Eloquent\Model`. It introspects the table's columns via the schema builder, skips any column listed in `$hidden`, maps each column type to a filter type, and labels it with `Str::headline()`. Columns that don't map (e.g. `json`/`jsonb`) are dropped.

If the model class doesn't exist, or isn't a `Model` (and has no `getTableSchema`), an `InvalidArgumentException` is thrown.

Column-type mapping (`mapColumnType`):

| DB type contains | Filter type |
| ---------------- | ----------- |
| `date` (exact)   | `date`      |
| `time` (exact)   | `time`      |
| `datetime`, `timestamp` | `datetime` |
| `int`            | `integer`   |
| `decimal`, `numeric`, `float`, `double`, `real` | `double` |
| `char`, `text`, `string`, `enum`, `set`, `uuid` | `string` |
| `bool`           | `boolean`   |
| `json`, `jsonb`  | (skipped)   |

### The definition shape you write

Each entry in a `getTableSchema()` (or a raw `$filters` map) is keyed by the field name and supports:

- `title` — the UI label (required; used as `label`).
- `type` — one of `integer`, `double`, `string`, `date`, `datetime`, `time`, `boolean`.
- `values` — optional. A list/map, or a callable returning one. Presence turns the input into a `select`.
- `query` — optional. A raw SQL expression to filter on instead of the column named by the key.

Here is a real schema from the Redot Dashboard (`app/Models/ShortenedUrl.php`):

```php
public static function getTableSchema(): array
{
    $admins = Admin::all()->pluck('name', 'id');

    return [
        'id'         => ['title' => __('ID'), 'type' => 'integer'],
        'url'        => ['title' => __('URL'), 'type' => 'string'],
        'slug'       => ['title' => __('Slug'), 'type' => 'string'],
        'title'      => ['title' => __('Title'), 'type' => 'string'],
        'clicks'     => ['title' => __('Clicks'), 'type' => 'integer'],
        'created_by' => ['title' => __('Created By'), 'type' => 'integer', 'values' => $admins],
        'deleted_at' => ['title' => __('Deleted At'), 'type' => 'datetime'],
        'created_at' => ['title' => __('Created At'), 'type' => 'datetime'],
    ];
}
```

### What each definition becomes

For every entry, `buildDefinition()` emits:

- `id` — `sha256(key)`.
- `field` — `encrypt('field:' . $key)`, or `encrypt('query:' . $definition['query'])` when a `query` key is present.
- `label` — the `title`.
- `type` — the filter type.
- `input` — `select` when `values` is set, otherwise derived from the type (`boolean` → `select`, `integer`/`double` → `number`, else `text`).
- `operators` — the allowed operator list for the type (see below).

Extra metadata is layered on:

- A `select` input also gets `plugin: 'tomselect'`, `input_event: 'change.tomselect update.tomselect'`, and its operators are narrowed to `equal`, `not_equal`, `is_null`, `is_not_null`.
- Date/time types get `plugin: 'datepicker'`, `input_event: 'change.td update.td'`, and `plugin_config: ['type' => $type]`.
- `values` are normalized via `Arr::from` (callables are invoked first). A `boolean` type with no `values` defaults to `[true => __('Yes'), false => __('No')]`.

### Operators per type (`operatorsFor`)

- **integer / double / date / datetime / time**: `equal`, `not_equal`, `in`, `not_in`, `less`, `less_or_equal`, `greater`, `greater_or_equal`, `between`, `not_between`, `is_null`, `is_not_null`.
- **string**: `equal`, `not_equal`, `in`, `not_in`, `begins_with`, `not_begins_with`, `contains`, `not_contains`, `ends_with`, `not_ends_with`, `is_empty`, `is_not_empty`, `is_null`, `is_not_null`.
- **boolean**: `equal`, `not_equal`, `is_null`, `is_not_null`.

## Applying rules: `query()` / `apply()`

`QueryFilters::query($rules, $query)` applies a rule tree to a query. With no `$query` it starts from `DB::query()` (a base query builder); pass an Eloquent builder to filter a model. If `$rules['rules']` is blank the query is returned untouched.

The rule tree mirrors the front-end format:

```php
[
    'condition' => 'AND',          // or 'OR' (case-insensitive; defaults to AND)
    'rules' => [
        ['field' => '<encrypted>', 'operator' => 'contains', 'value' => 'foo'],
        // nested groups: any element with its own 'rules' key
        [
            'condition' => 'OR',
            'rules' => [
                ['field' => '<encrypted>', 'operator' => 'equal', 'value' => 1],
            ],
        ],
    ],
]
```

How it is applied:

- The whole tree is wrapped in a single `where(fn ($nested) => ...)` so the filter never leaks into sibling conditions.
- Within a group, the first rule uses `and`; subsequent rules use the group's `condition`. Nested groups are wrapped via `where`/`orWhere` accordingly.
- Each leaf rule's `field` is **decrypted**. A missing or non-string `field` throws `InvalidArgumentException`. The decrypted value is split on `:` into prefix and field.
- When the prefix is `query`, the field is wrapped in `DB::raw("({$field})")` — the raw SQL expression is evaluated instead of a column. Otherwise the bare column name is used.
- The operator maps to a builder call (`where`, `whereIn`, `whereBetween`, `whereNull`, `like`/`not like` variants, etc.). `in`/`not_in`/`between`/`not_between` wrap the value with `Arr::wrap`. An unknown operator throws `InvalidArgumentException`.

Operator-to-SQL highlights: `contains` → `like %value%`, `begins_with` → `like value%`, `ends_with` → `like %value`, `is_empty` → `= ''`, `is_not_empty` → `!= ''`, `is_null` → `whereNull`, `is_not_null` → `whereNull(..., not: true)`, `not_in` → `whereIn(..., not: true)`, `not_between` → `whereBetween(..., not: true)`.

### Raw SQL expression fields

Set a `query` key on a definition to filter on an expression rather than a column. This is useful for computed/concatenated fields (e.g. a full name). The expression is stored encrypted in `field`, so the client only ever round-trips an opaque token:

```php
'full_name' => [
    'title' => __('Full Name'),
    'type'  => 'string',
    'query' => "CONCAT(first_name, ' ', last_name)",
],
```

When a rule targets this field, `query()` decrypts it to `query:CONCAT(...)` and applies the operator against `DB::raw("(CONCAT(first_name, ' ', last_name))")`.

## Usage

### Resolving definitions for a UI component

The dashboard's `x-query-builder` Blade component resolves definitions and hands them to the front-end (`app/View/Components/QueryBuilder.php`):

```php
use Redot\Support\QueryFilters;

// $model is a FQCN string, $filters an optional raw map.
$this->filters = QueryFilters::resolve($this->model, $this->filters);
```

The resolved array is JSON-encoded onto the element's `query-filters` attribute for the JS query builder to render.

### Applying submitted rules to a query

On the receiving end, take the rule tree the UI submits and apply it to your builder:

```php
use Redot\Support\QueryFilters;

$results = QueryFilters::query($rules, ShortenedUrl::query())->paginate();
```

Because `query()` accepts an Eloquent or base builder, you can compose it with other constraints — call it on a builder that already has scopes/joins applied.

## Gotchas

- **Field names are encrypted, not signed-by-you.** Only fields produced by `resolve()` decrypt to a valid `field:`/`query:` token, which is what keeps the client from filtering on arbitrary columns or injecting SQL. Don't construct `field` values by hand on the client.
- **`getTableSchema` wins over auto-derivation.** If your model defines it, column introspection and `$hidden` filtering are skipped entirely.
- **Auto-derived filters require a real `Model`.** A non-existent class, or a class without `getTableSchema` that doesn't extend `Model`, throws `InvalidArgumentException`.
- **`json`/`jsonb` columns are never auto-filtered.** Add them explicitly via `getTableSchema` (with a supported `type`) if you need them.
- **`select` inputs are operator-limited.** Defining `values` (or using a `boolean` type) restricts operators to equality/null checks regardless of the underlying type.
- **Unknown operators throw.** Only the operators listed in `operatorsFor` are supported; anything else raises `InvalidArgumentException` at apply time.

See also the [Datatables](/packages/datatables/overview) package, which renders filterable tables that pair naturally with these definitions.
