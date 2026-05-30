# Checkboxes

`<x-checkboxes>` renders a group of related checkboxes from an `options` map,
with an optional label, hint, inline layout, and group-level validation. Use it
for array fields like `permissions[]` or `website_locales[]`.

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

### Inline language selector

```blade
<x-checkboxes
    name="website_locales[]"
    :title="__('Website Languages')"
    :options="config('app.locales')"
    :value="setting('website_locales', [])"
    :inline="true"
    validation="required|min:1"
/>
```

### Permissions grid

```blade
@php
    $options = collect($permissions)
        ->mapWithKeys(fn ($p) => [$p->name => Str::title(Arr::last(explode('.', $p->name)))])
        ->all();
@endphp

<x-checkboxes name="permissions[]" :options="$options" :value="$selected" :inline="true" />
```

## Related

- [Radios](/components/radios) — single-choice equivalent.
- [Toggle](/components/toggle) — single boolean switch.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
