# Radios

`<x-radios>` renders a group of radio buttons from an `options` map, with an
optional label, hint, per-option disabling, and inline layout. Use it for
single-choice fields.

## Usage

```blade
<x-radios name="theme" :title="__('Theme')" :value="$current" :options="$options" />
```

`options` is a key/label map: the **key** is the value submitted with the form,
the **value** is the visible label. It shares the
[common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`).

## Options

- **`options`** — key/label map of radios. The key becomes the submitted value;
  the value is the displayed label.
- **`title`** — label shown above the group.
- **`hint`** — helper text shown below the group.
- **`value`** — the option key to pre-select.
- **`disabled`** — keys to render disabled. Accepts an array or a CSV string.
- **`inline`** — lay the radios out horizontally on one row.
- **`id`** — wrapper id; auto-generated when omitted.

## Examples

### Inline single-choice selector

```blade
<x-radios
    name="dashboard_sidebar_theme"
    :title="__('Sidebar theme')"
    :value="setting('dashboard_sidebar_theme', 'inherit')"
    :inline="true"
    :options="[
        'inherit' => __('Inherit'),
        'dark' => __('Force dark'),
    ]"
/>
```

### Required group

```blade
<x-radios name="plan" :title="__('Plan')" :options="$plans" validation="required" />
```

## Related

- [Radios Colored](/components/radios-colored) — swatch-style variant for picking colors.
- [Checkboxes](/components/checkboxes) — multi-choice equivalent.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
