# Query Builder

`<x-query-builder>` renders a visual filter builder. The user composes
AND/OR rule groups, and the result is stored as a JSON rule tree in a hidden
input that you apply to a query on the server. Use it for report filters and
advanced search screens.

## Usage

```blade
<x-query-builder name="builder" :title="__('Filters')" :model="\App\Models\Post::class" :value="old('builder')" />
```

Give it a `name` so the rule tree is submitted with the form, and either a
`model` (filters are derived from its columns) or an explicit `filters`
definition. It shares the
[common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`) and initializes itself through
the [asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown above the builder.
- **`hint`** — helper text shown below the builder.
- **`value`** — initial rule tree (an array or JSON string) to pre-populate rules.
- **`model`** — a model class to derive filters from automatically. Hidden and
  JSON columns are skipped.
- **`filters`** — an explicit filter definition map, used instead of `model`.
  Each entry is keyed by field name and accepts:
  - **`title`** — label shown in the builder.
  - **`type`** — one of `string`, `integer`, `double`, `boolean`, `date`,
    `datetime`, `time`.
  - **`values`** — options for a dropdown filter (an array or a callable).
  - **`query`** — a raw SQL expression to filter on instead of a column.
- **`id`** — element id; auto-generated when omitted.

## Examples

### Derive filters from a model

```blade
<x-form :action="route('posts.index')" method="GET">
    <x-query-builder name="builder" :title="__('Filters')" :model="\App\Models\Post::class" />
    <button type="submit" class="btn btn-primary">{{ __('Run') }}</button>
</x-form>
```

### Explicit filters with options and a raw query

```blade
<x-query-builder
    name="builder"
    :title="__('Post filters')"
    :filters="[
        'status' => [
            'title' => __('Status'),
            'type' => 'string',
            'values' => ['published' => __('Published'), 'draft' => __('Draft')],
        ],
        'published_at' => ['title' => __('Published at'), 'type' => 'datetime'],
        'created_at' => ['title' => __('Created'), 'type' => 'datetime'],
        'author' => [
            'title' => __('Author'),
            'type' => 'string',
            'query' => 'concat(first_name, \' \', last_name)',
        ],
    ]"
    :value="old('builder')"
/>
```

### Apply the rules server-side

Read the submitted JSON and apply it to a query with the `QueryFilters` helper:

```php
use Redot\Support\QueryFilters;

$rules = json_decode($request->input('builder'), true) ?? [];

$posts = QueryFilters::query($rules, Post::query())->get();
```

## Related

- [Query Builder init](/frontend/inits/query-builder) — how the builder initializes.
- [Select](/components/select) — used for dropdown filters.
- [Components overview](/components/overview) — shared form-field conventions.
