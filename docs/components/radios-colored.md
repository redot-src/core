# Radios Colored

`<x-radios-colored>` renders a group of radio inputs displayed as colored swatches instead of text labels. It is built on top of the [Radios component](/components/radios) and is used in the dashboard for picking a theme color scheme.

## What it is

The component is a PHP-class component, `App\View\Components\RadiosColored`, that **extends** `App\View\Components\Radios`. It reuses the parent's constructor and rendering logic but swaps the view to `components.radios-colored` and pre-processes each option into a valid CSS color before rendering.

In the `radios-colored` view, the `$options` array is iterated as `key => color`: the **key** becomes the radio's `value`, and the **color** is applied as the swatch's `background`.

### Color resolution

Before rendering, `RadiosColored::render()` maps every option value through `getColor()`:

```php
protected function getColor(string $color): string
{
    $color = trim($color);
    $isDirectColor = preg_match('/^[a-zA-Z]+$/', $color);

    return $isDirectColor ? "var(--tblr-$color, $color)" : $color;
}
```

- A purely alphabetic value (e.g. `blue`, `azure`) is treated as a Tabler color token and resolved to `var(--tblr-blue, blue)` — using the Tabler CSS variable with the raw word as fallback.
- Anything else (hex like `#3366ff`, `rgb(...)`, etc.) is passed through unchanged as the literal `background` value.

## Props

The props come from the inherited `Radios` constructor. All are optional.

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` | Wrapper `<div>` id. Auto-generated as `uniqid('radios-')` when not set. Also used as the label `for` and the `validation-container` target. |
| `title` | `?string` | `null` | Renders an `<x-label>` above the swatches. Omitted when null. |
| `hint` | `?string` | `null` | Renders an `<x-hint>` below the swatches. Omitted when null. |
| `name` | `?string` | `null` | The shared `name` attribute for every radio input in the group. |
| `options` | `array\|Collection` | `[]` | Map of `value => color`. The key is the submitted value; the color is the swatch background (resolved via `getColor()`). |
| `value` | `?string` | `null` | The currently selected option key; matching radio gets `checked`. |
| `disabled` | `array\|string` | `[]` | Option keys to disable. A CSV string is parsed via `parse_csv()`; a non-array becomes a single-element array. |
| `inline` | `bool` | `false` | Inherited from `Radios`. Not referenced by the colored view. |
| `validation` | `?string` | `null` | Validation rule string. Applied to the first input only, with `validation-container` pointing at `#{id}`. If it contains `required`, the label is marked required. |
| `required` | `bool` | `false` | Public property (not a constructor arg). Set to `true` automatically when `validation` contains `required`. Drives the label's required marker. |

### Rendered markup

Each option produces:

```html
<label class="form-colorinput">
    <input type="radio" class="form-colorinput-input" name="{name}" value="{key}" ... />
    <span class="form-colorinput-color" style="background: {color}"></span>
</label>
```

The whole group is wrapped in `<div id="{id}">`. These are Tabler `form-colorinput` classes; no extra JavaScript plugin is bound by the component itself.

## Slots and wire:model

There are no named slots — content is driven entirely by the `options` prop. The component does not declare any `wire:model` binding; selection is plain `name`/`value` form submission. Behavior on change (such as live theme preview) is wired up by the consuming page's own scripts, not by the component.

## Usage

Real usage from the dashboard theme settings page (`resources/views/dashboard/settings/partials/theme-customizations.blade.php`):

```blade
<div class="mb-3">
    <x-radios-colored name="theme[primary]" :title="__('Color scheme')" :value="setting('theme.primary')" :options="[
        'blue' => 'blue',
        'azure' => 'azure',
        'indigo' => 'indigo',
        'purple' => 'purple',
        'pink' => 'pink',
        'red' => 'red',
        'orange' => 'orange',
        'yellow' => 'yellow',
        'lime' => 'lime',
        'green' => 'green',
        'teal' => 'teal',
        'cyan' => 'cyan',
        'black' => 'black',
    ]" />
</div>
```

Here every option value is a Tabler color name, so each swatch background resolves to `var(--tblr-blue, blue)`, `var(--tblr-azure, azure)`, and so on. The selected color is read back from `setting('theme.primary')`, and a sibling `@push('scripts')` block listens for `change` on `[name^="theme"]` to live-update `data-bs-theme-primary` on `<html>`.

You can also pass explicit colors instead of token names:

```blade
<x-radios-colored
    name="brand_color"
    :title="__('Brand color')"
    :options="[
        'primary' => '#206bc4',
        'success' => '#2fb344',
    ]"
    value="primary"
/>
```

## Gotchas

- `options` is a `value => color` map — the key is what gets submitted, not the color. This is the opposite mental model of a label-based radio list.
- Alphabetic color values are rewritten to Tabler CSS variables. To force a literal named color that is *not* a Tabler token, use a non-alphabetic form (e.g. hex) so it bypasses `getColor()`'s rewrite.
- `validation` is emitted only on the first input in the group, scoped to `validation-container="#{id}"`.
- The `inline` prop is accepted (inherited) but has no effect in the colored layout.

## Related

- [Radios component](/components/radios) — the text-label parent component this extends.
