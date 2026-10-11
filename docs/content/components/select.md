# Select

`<x-select>` is an enhanced dropdown. By default it becomes a searchable, optionally taggable widget over a list of options you pass in. To search a model query over AJAX instead, use [`<x-select-remote>`](/components/select-remote).

## Usage

```blade
<x-select name="direction" :title="__('Direction')" value="ltr" :options="['ltr' => __('Left to Right'), 'rtl' => __('Right to Left')]" />
```

`options` is a key/label map: the **key** is the value submitted, the **value** is the visible label. It shares the [common form-field attributes](/components/overview#shared-form-field-conventions) (`name`, `title`, `value`, `hint`, `validation`), and initializes its widget through the [asset & init system](/frontend/asset-system). For a multi-select, add `multiple` and use a `name="...[]"` array name with an array `value`.

## Options

- **`options`** — key/label map of choices. Use the default slot to add extra static `<option>`s alongside it.
- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — selected value(s). Accepts an array, a single value, or a CSV string.
- **`multiple`** — allow selecting more than one option (adds a remove button).
- **`tags`** — let users create free-typed options.
- **`removable`** — add a clear button and don't force a default selection.
- **`tom`** — set `:tom="false"` to render a plain native select with no search or tags.
- **`id`** — element id; auto-generated when omitted.

Use the **`append`** slot to place a control, such as a button, next to the select.

## Examples

### Clearable single select bound to a relationship

```blade
<x-select name="category_id" :title="__('Category')" :options="$categories" :value="$post?->category_id" removable />
```

### Multiple, taggable select

```blade
<x-select name="tags[]" :title="__('Tags')" :options="$tags" :value="$post?->tags" tags multiple />
```

### Plain native select

```blade
<x-select name="status" :title="__('Status')" :options="$statuses" :tom="false" />
```

### Searching a model remotely

Switch to [`<x-select-remote>`](/components/select-remote) and pass a `query`:

```blade
<x-select-remote name="author_id" :title="__('Author')" :query="\App\Models\User::class" search="name, email" validation="required" />
```

## Related

- [Select Remote](/components/select-remote) — options searched from a model query.
- [Tom Select init](/frontend/inits/tomselect) — how the widget initializes.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
