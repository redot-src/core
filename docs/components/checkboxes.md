# Checkboxes

`<x-checkboxes>` renders a group of related checkboxes from an `options` map,
with an optional label, hint, inline layout, and group-level validation. Use it
for array fields like `tags[]` or `categories[]`.

## Usage

```blade
<x-checkboxes name="permissions[]" :options="$options" :value="$selected" />
```

`options` is a key/label map: the **key** is the value submitted with the form,
the **value** is the visible label. Add a `[]` suffix to `name` so the form
posts an array. It shares the
[common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`).

## Options

- **`options`** — key/label map of checkboxes. The key becomes the submitted
  value; the value is the displayed label.
- **`title`** — label shown above the group.
- **`hint`** — helper text shown below the group.
- **`value`** — keys to pre-check. Accepts an array or a CSV string.
- **`disabled`** — keys to render disabled. Accepts an array or a CSV string.
- **`inline`** — lay the checkboxes out horizontally.
- **`validation`** — validation rules applied to the group as a whole; if they
  contain `required`, the label shows a required marker. See
  [RedotValidator](/frontend/plugins/redot-validator).
- **`id`** — wrapper id; auto-generated when omitted.

## Examples

### Inline tag selector

```blade
<x-checkboxes
    name="tags[]"
    :title="__('Tags')"
    :options="$tags"
    :value="old('tags', $post?->tags)"
    :inline="true"
    validation="required|min:1"
/>
```

### Category grid

```blade
@php
    $options = $categories->pluck('title', 'id')->all();
@endphp

<x-checkboxes name="categories[]" :options="$options" :value="old('categories', $post?->categories)" :inline="true" />
```

## Related

- [Radios](/components/radios) — single-choice equivalent.
- [Toggle](/components/toggle) — single boolean switch.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
