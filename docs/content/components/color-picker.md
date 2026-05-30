# Color Picker

`<x-color-picker>` is a text field that opens a color swatch popup, backed by the
Coloris library. It renders a labelled, hinted control and seeds an initial
color from its `value`.

## Usage

```blade
<x-color-picker name="background" value="#ffffff" :title="__('Background Color')" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). Seed the initial color with
`value="#rrggbb"`. The picker initializes itself through the
[asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — initial color, as a hex string (e.g. `#3b82f6`).
- **`id`** — element id; auto-generated when omitted.

Coloris options can be supplied per element with `coloris-*` attributes (e.g.
`coloris-format-toggle="false"`).

## Examples

### Foreground and background pickers

```blade
<x-color-picker name="background" value="#ffffff" :title="__('Background Color')" />
<x-color-picker name="foreground" value="#000000" :title="__('Foreground Color')" />
```

### With a hint and required marker

```blade
<x-color-picker
    name="brand_color"
    value="#3b82f6"
    :title="__('Brand Color')"
    :hint="__('Used across buttons and links')"
    validation="required"
/>
```

## Related

- [Components overview](/components/overview) — shared form-field conventions.
- [Asset & Init System](/frontend/asset-system) — how the picker initializes.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
