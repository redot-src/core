# Checkboxes

The `<x-checkboxes>` component renders a group of related checkbox inputs from an associative `options` array, with optional label, hint, inline layout, pre-checked/disabled state, and client-side validation. It is backed by the `App\View\Components\Checkboxes` class.

## What it is

A class-based Blade component (`App\View\Components\Checkboxes`, view `components.checkboxes`) that iterates over an `options` map and emits one Bootstrap `form-check` per entry. Each checkbox shares the same `name`, so the component is meant for array fields (e.g. `name="permissions[]"`). The component does not wrap itself in a `<form>` or use `wire:model` — it is a plain HTML checkbox group; submission is handled by the surrounding form.

## Props

All props come from the constructor of `App\View\Components\Checkboxes`:

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `id` | `?string` | auto (`uniqid('checkboxes-')`) | Wrapper `<div>` id; also linked to the label and used as the validation container. |
| `title` | `?string` | `null` | Label text rendered via `<x-label>`. When `null`, no label is shown. |
| `hint` | `?string` | `null` | Help text rendered via `<x-hint class="mt-1">` below the group. When `null`, omitted. |
| `name` | `?string` | `null` | The `name` attribute applied to every checkbox. Use `[]` suffix for multi-select array fields. |
| `options` | `array\|Collection` | `[]` | Key/label map. The array key becomes the checkbox `value` and `title`; the value becomes the visible `<span class="form-check-label">` text. |
| `value` | `array\|string\|null` | `[]` | Selected keys. A string is parsed: CSV strings via `parse_csv()`, otherwise wrapped in a single-element array. Keys present here are rendered `checked`. |
| `disabled` | `array\|string` | `[]` | Keys to render as `disabled`. Same string/CSV normalization as `value`. |
| `inline` | `bool` | `false` | When `true`, adds `form-check-inline` to lay checkboxes out horizontally. |
| `validation` | `?string` | `null` | Validation rule string applied to the first checkbox only, with `validation-container="#{id}"`. If it contains `required`, the label is marked required. |
| `required` | `bool` | `false` (public property) | Whether the label shows the required marker. Auto-set to `true` when `validation` contains `required`. |

### Render-time normalization

- `id` defaults to a generated `uniqid('checkboxes-')` when not supplied.
- If `validation` contains the substring `required`, `required` is forced to `true`.
- Non-array `value` and `disabled` are normalized: a string is run through `parse_csv()` (CSV → array), and any other non-array scalar is wrapped in `[$value]`.
- `value`/`disabled` membership is tested with `in_array($key, ...)` against the option keys.

## Validation

When `validation` is set, the attribute is attached only to the **first** rendered checkbox (`@if ($loop->first)`), along with <span v-pre>`validation-container="#{{ $id }}"`</span> so messages target the whole group. This wires into the dashboard's client-side validation system (see [Validation plugin](/frontend/plugins/redot-validator)).

## Slots

This component has no slots. Content is driven entirely by the `options` prop; the `hint` and `title` props render the surrounding `<x-hint>` and `<x-label>` components.

## Examples

Inline language selector backed by app settings, with required-min validation (from `dashboard/settings/partials/application-information.blade.php`):

```blade
<div class="mb-3">
    <x-checkboxes :title="__('Website Languages')" :options="config('app.locales')" :inline="true"
        name="website_locales[]" :value="setting('website_locales', [])"
        validation="required|min:1" />
</div>
```

Permissions grid (from `dashboard/roles/partials/form.blade.php`), where `options` is built as a `permission_name => Title` map and `value` holds the currently selected permission names:

```blade
@php
    $options = collect($values)
        ->mapWithKeys(fn($p) => [$p->name => Str::title(Arr::last(explode('.', $p->name)))])
        ->all();
@endphp

<x-checkboxes name="permissions[]" :inline="true" :options="$options" :value="$selected" />
```

## Gotchas

- `options` is a key/label map, not a flat list: the **key** is what gets submitted (the checkbox `value`), the **value** is the displayed label.
- Use a `[]` suffix on `name` (e.g. `permissions[]`) when you expect multiple selections, so the form posts an array.
- `validation` only attaches to the first checkbox; this is intentional so a single rule (e.g. `required|min:1`) validates the group as a whole via `validation-container`.
- Passing a CSV string to `value` or `disabled` works (it's parsed), but an array is clearer and avoids relying on `parse_csv()` behavior.

## Related

- [Label component](/components/label) — renders the `title`.
- [Hint component](/components/hint) — renders the `hint`.
- [Validation plugin](/frontend/plugins/redot-validator) — consumes the `validation` / `validation-container` attributes.
- [Toggle component](/components/toggle) — single boolean switch, often used alongside checkbox groups.
