# Radios Colored

`<x-radios-colored>` renders a single-choice radio group as colored swatches
instead of text labels. Reach for it when picking a color scheme, where the
visual swatch is more useful than a name.

## Usage

```blade
<x-radios-colored name="theme[primary]" :title="__('Color scheme')" :value="$current" :options="$colors" />
```

`options` is a `key => color` map: the **key** is the value submitted with the
form, the **color** is the swatch background. A plain color name (e.g. `blue`,
`azure`) resolves to the matching Tabler theme color; pass a hex/`rgb()` value to
use a literal color. It shares the
[common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`).

## Options

- **`options`** — `key => color` map. The key is the submitted value; the color
  is the swatch background. Plain names map to Tabler colors; hex/`rgb()` values
  are used literally.
- **`title`** — label shown above the swatches.
- **`hint`** — helper text shown below the swatches.
- **`value`** — the option key to pre-select.
- **`disabled`** — keys to render disabled. Accepts an array or a CSV string.
- **`id`** — wrapper id; auto-generated when omitted.

## Examples

### Theme color picker (Tabler color names)

```blade
<x-radios-colored name="theme[primary]" :title="__('Color scheme')" :value="setting('theme.primary')" :options="[
    'blue' => 'blue',
    'azure' => 'azure',
    'indigo' => 'indigo',
    'purple' => 'purple',
    'green' => 'green',
    'red' => 'red',
]" />
```

### Literal hex swatches

```blade
<x-radios-colored name="brand_color" :title="__('Brand color')" value="primary" :options="[
    'primary' => '#206bc4',
    'success' => '#2fb344',
]" />
```

## Related

- [Radios](/components/radios) — the text-label variant.
- [Components overview](/components/overview) — shared form-field conventions.
