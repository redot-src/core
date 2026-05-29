# Query Builder

`<x-query-builder>` renders a visual filter builder backed by the [jQuery QueryBuilder](https://querybuilder.js.org/) plugin. It produces a single hidden input that holds a JSON rule tree, which the `Redot\Support\QueryFilters` helper translates into Eloquent/DB `where` clauses on the server.

## What it is

The component is a PHP-backed Blade component (`App\View\Components\QueryBuilder`, view `components.query-builder`). On render it:

- Pushes the QueryBuilder, Tom Select, Tempus Dominus, and Popper assets onto the `plugins-styles` / `plugins-scripts` stacks.
- Emits a hidden `<input>` (with `init="query-builder"`) wrapped in a `[query-builder-container]` div alongside an empty `[query-builder]` mount node.
- Serializes the filter definitions into a `query-filters` attribute that the JS init reads to construct the builder.

The actual UI is bootstrapped by the `query-builder` JS init. See [Initializers](/frontend/inits/query-builder) for how `init="..."` attributes are wired.

## Props

All props come from the constructor of `App\View\Components\QueryBuilder`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | auto `uniqid('query-builder-')` | DOM id of the hidden input; also used as the label's `for`. |
| `title` | `?string` | `null` | When set, renders an `<x-label>` above the builder. |
| `hint` | `?string` | `null` | When set, renders an `<x-hint>` below the builder. |
| `model` | `?string` | `null` | Fully-qualified model class. Filter definitions are derived from its table schema (or its `getTableSchema()` method) when `filters` is not given. |
| `value` | `array\|string\|null` | `null` | Initial rule tree. Arrays/Collections are JSON-encoded; the JS parses it to pre-populate rules. |
| `filters` | `array\|string\|null` | `null` | Explicit filter definition map. Resolved via `QueryFilters::resolve($model, $filters)`; takes precedence over `model`-derived filters. |

### Resolved attributes

- `query-filters` — JSON of the resolved filter definitions, merged onto the input automatically.
- `init` — defaults to `query-builder` (merged on the input). Any extra attributes you pass land on the hidden input.
- `required` — passed to the label; computed `true` when the input's `validation` attribute contains `required`.

There are no slots; `title` and `hint` are scalar props, not slots.

## Filter definitions

`filters` is a map keyed by field name. Each entry supports:

- `title` — label shown in the builder.
- `type` — one of `string`, `integer`, `double`, `boolean`, `date`, `datetime`, `time`.
- `values` — optional array or callable returning options; presence switches the input to a Tom Select dropdown (operators reduced to `equal`, `not_equal`, `is_null`, `is_not_null`).
- `query` — optional raw SQL expression to filter on instead of a column; evaluated via `DB::raw(...)` server-side.

`QueryFilters::resolve()` encrypts each `field` (prefixed `field:` or `query:`) and assigns operators per type. When `filters` is omitted, columns are introspected from the `model`'s table (hidden columns and `json`/`jsonb` are skipped). `boolean` types get implicit `Yes`/`No` values.

## Server-side application

Submit the form, read the hidden input's JSON, and apply it:

```php
use Redot\Support\QueryFilters;

$rules = json_decode($request->input('builder'), true) ?? [];

$users = QueryFilters::query($rules, User::query())->get();
```

`QueryFilters::query()` walks the rule tree, honoring `AND`/`OR` group conditions and nested groups, and maps operators (`equal`, `in`, `between`, `contains`, `is_null`, etc.) to query clauses. Unsupported operators throw `InvalidArgumentException`.

## JS behavior

The `query-builder` init wraps the jQuery QueryBuilder plugin. Defaults it applies:

- `lang_code` from `<html lang>`, `display_empty_filter: false`, `display_errors: true`, `allow_groups: 2`, `allow_empty: true`, `default_filter: null`.
- Font Awesome icons for add/remove group/rule.

It reads extra options from `query-`-prefixed attributes on the input, registers `valueSetter`/`valueGetter` per filter, parses the input's current value into `rules`, and on form `submit` serializes the builder back into the hidden input's value (or `null` when empty). Select inputs use the `tomselect` plugin; date/time inputs use the `datepicker` (Tempus Dominus) plugin.

## Usage

Build filters from a model's schema:

```blade
<form method="POST" action="{{ route('reports.run') }}">
    @csrf
    <x-query-builder
        id="builder"
        :title="__('Filters')"
        :model="\App\Models\User::class"
    />
    <x-button type="submit">{{ __('Run') }}</x-button>
</form>
```

Provide explicit filter definitions (with select options and a raw query field):

```blade
<x-query-builder
    name="builder"
    :title="__('Order filters')"
    :filters="[
        'status' => [
            'title' => __('Status'),
            'type' => 'string',
            'values' => ['paid' => __('Paid'), 'pending' => __('Pending')],
        ],
        'total' => ['title' => __('Total'), 'type' => 'double'],
        'created_at' => ['title' => __('Created'), 'type' => 'datetime'],
        'full_name' => [
            'title' => __('Full name'),
            'type' => 'string',
            'query' => "concat(first_name, ' ', last_name)",
        ],
    ]"
    :value="old('builder')"
/>
```

## Gotchas

- The input has no `name` by default — pass one (e.g. `name="builder"`) so the serialized JSON is submitted with the form.
- Serialization only happens on the closest form's `submit`; the value is empty until then.
- `field` values are encrypted, so rules built on one app key cannot be applied after a key rotation.
- `json`/`jsonb` columns are excluded from auto-derived filters; define them explicitly via `filters` if needed.

## Related

- [Initializers](/frontend/inits/query-builder) — the `init` attribute system.
- [Select component](/components/select) — also uses Tom Select.
- [Label](/components/label) and [Hint](/components/hint) — rendered from `title` / `hint`.
