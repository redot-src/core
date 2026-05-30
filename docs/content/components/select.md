# Select

`<x-select>` is an enhanced dropdown. By default it becomes a searchable,
optionally taggable widget; it can load static options or fetch and search
results remotely from a model query.

## Usage

```blade
<x-select name="direction" :title="__('Direction')" :value="old('direction', 'ltr')" :options="['ltr' => __('Left to Right'), 'rtl' => __('Right to Left')]" />
```

`options` is a key/label map: the **key** is the value submitted, the **value**
is the visible label. It shares the
[common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`), and initializes its widget
through the [asset & init system](/frontend/asset-system). For a multi-select,
add `multiple` and use a `name="...[]"` array name with an array `value`.

## Options

- **`options`** — key/label map of choices. Use the default slot to add extra
  static `<option>`s alongside it.
- **`title`** — label shown above (or, when floating, inside) the field.
- **`hint`** — helper text shown below the field.
- **`value`** — selected value(s). Accepts an array, a single value, or a CSV
  string.
- **`multiple`** — allow selecting more than one option (adds a remove button).
- **`tags`** — let users create free-typed options.
- **`removable`** — add a clear button and don't force a default selection.
- **`floating`** — use the Bootstrap floating-label layout. This renders a plain
  select with no search/tags.
- **`id`** — element id; auto-generated when omitted.

### Remote options

Point the select at a model query to load and search options over AJAX instead
of listing them inline:

- **`query`** — the data source: a model class string, or an Eloquent / query
  builder instance.
- **`search`** — column(s) searched on the server. Accepts an array or CSV.
- **`key`** — column used as each option's value (`id` by default).
- **`text`** — column used as each option's label (`name` by default).
- **`appends`** — extra attributes appended to each result row.
- **`template`** — a Blade view used to render each option.
- **`same-template`** — render the selected item with the same template as the
  dropdown option.

## Examples

### Clearable single select bound to a relationship

```blade
<x-select name="category_id" :title="__('Category')" :options="$categories" :value="old('category_id', $post?->category_id)" removable />
```

### Multiple, taggable select

```blade
<x-select name="tags[]" :title="__('Tags')" :options="$tags" :value="old('tags', $post?->tags)" tags multiple />
```

### Remote search across multiple columns

```blade
<x-select name="author_id" :title="__('Author')" :query="\App\Models\User::class" text="name" search="name, email" template="author" validation="required" />
```

### Remote options from a model class

```blade
<x-select name="category_id" :title="__('Category')" :query="\App\Models\Category::class" text="title" template="category" same-template />
```

## Related

- [Tom Select init](/frontend/inits/tomselect) — how the widget initializes.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
