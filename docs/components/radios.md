# Radios

The `<x-radios>` component renders a group of Bootstrap-styled radio buttons from an options map, with an optional label, hint text, per-option disabling, inline layout, and client-side validation wiring.

## What it is

`<x-radios>` is a class-based Blade component backed by `App\View\Components\Radios`. It loops over an `options` array (key => label) and emits one `<input type="radio">` per entry, all sharing the same `name`. The currently selected option is matched against `value`. The whole group is wrapped in a `<div>` that carries the component `id`, which doubles as the validation container target.

## Props

All props come from the constructor of `App\View\Components\Radios`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` → auto `uniqid('radios-')` | Wrapper `<div>` id; also used as the label's `for` and the validation container selector. |
| `title` | `?string` | `null` | Label text rendered above the group via `<x-label>`. Omitted entirely when null. |
| `hint` | `?string` | `null` | Helper text rendered below the group via `<x-hint class="mt-1">`. Omitted when null. |
| `name` | `?string` | `null` | The shared `name` attribute applied to every radio input. |
| `options` | `array\|Collection` | `[]` | Map of `value => label`. The key becomes the input `value`; the value becomes the visible label text. |
| `value` | `?string` | `null` | The option key that should be pre-checked. Compared loosely (`$key == $value`). |
| `disabled` | `array\|string` | `[]` | Option keys to disable. Accepts an array, a single value, or a CSV string (parsed via `parse_csv`). |
| `inline` | `bool` | `false` | When `true`, each option gets `form-check-inline` so radios sit on one row. |
| `validation` | `?string` | `null` | Validation rule string. Attached to the **first** radio as `validation="..."` plus `validation-container="#{id}"`. |
| `required` | `bool` | `false` | Marks the label required. Auto-set to `true` when `validation` contains `required`. |

### Behavior notes

- `id` is generated lazily in `render()` if not supplied (`uniqid('radios-')`), so each group is unique even without an explicit id.
- If `validation` contains the substring `required`, `required` is forced to `true`, so the `<x-label>` shows the required marker automatically.
- `disabled` is normalized in `render()`: a non-array value becomes an array, and a string is split with `parse_csv`. Each rendered input then uses `@disabled(in_array($key, $disabled))`.
- The `validation` / `validation-container` attributes are only emitted on `$loop->first` (the first radio), pointing the validator at `#{id}`. See [Validation](/frontend/plugins/redot-validator).
- This component does **not** ship its own JS or wire to Livewire directly. It is a plain form input — bind it with standard `name`/`value`. The settings page below wires change handlers manually with jQuery.

## Slots

None. The component has no body slot; content is driven entirely by the `options` prop. Label and hint are delegated to [`<x-label>`](/components/label) and [`<x-hint>`](/components/hint).

## Examples

Inline theme-base selector from the dashboard settings page (`resources/views/dashboard/settings/partials/theme-customizations.blade.php`):

```blade
<x-radios name="theme[base]" :title="__('Theme Base')" :value="setting('theme.base')" :inline="true" :options="[
    'default' => __('Default'),
    'slate' => __('Slate'),
    'gray' => __('Gray'),
    'zinc' => __('Zinc'),
    'neutral' => __('Neutral'),
    'stone' => __('Stone'),
    'pink' => __('Pink'),
]" />
```

Sidebar theme selector with a default value and inline layout:

```blade
<x-radios name="dashboard_sidebar_theme" :title="__('Sidebar theme')" :value="setting('dashboard_sidebar_theme', 'inherit')" :inline="true"
    :options="[
        'inherit' => __('Inherit'),
        'dark' => __('Force dark'),
    ]" />
```

These radios participate in the live theme preview: the page pushes a jQuery `change` handler on `[name^="theme"]` and `[name="dashboard_sidebar_theme"]` to update `data-bs-theme-*` attributes on the document as options are picked.

## Related

- [Radios Colored component](/components/radios-colored) — a swatch-style variant used for color scheme selection on the same settings page.
- [Label component](/components/label) and [Hint component](/components/hint) — used internally for the title and helper text.
- [Validation](/frontend/plugins/redot-validator) — consumes the `validation` / `validation-container` attributes.
