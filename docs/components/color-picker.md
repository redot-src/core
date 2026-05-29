# Color Picker

The `<x-color-picker>` component renders a text input wired to the [Coloris](https://github.com/mdbassit/Coloris) color-picker library. It outputs a labelled, hinted form control that opens a color swatch popup when focused, and lazily injects the Coloris CSS/JS the first time it is used on a page.

## What it is

`<x-color-picker>` is a class-based Blade component backed by `App\View\Components\ColorPicker`. Its view (`resources/components/color-picker.blade.php`) renders a single text `<input>` with the `init="coloris"` data attribute. The dashboard's asset/init system picks up that attribute and boots a Coloris instance against the input.

The component itself does not bundle any JavaScript inline — it pushes the Coloris stylesheet to the `plugins-styles` stack and the Coloris script to the `plugins-scripts` stack (each `@pushOnce`, so they are emitted only once regardless of how many pickers appear on a page).

## Props

These come from the constructor of `App\View\Components\ColorPicker`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `uniqid('color-picker-')` | The input's `id`. If omitted, a unique id is generated at render time and also used to associate the `<x-label>`. |
| `title` | `?string` | `null` | Label text. When set, an `<x-label>` is rendered above the input, bound to the input via `for`. |
| `hint` | `?string` | `null` | Helper text. When set, an `<x-hint>` is rendered below the input. |

### Computed view data

- `required` — passed to `<x-label>` and derived from the attribute bag: it is `true` when the `validation` attribute string contains `required` (e.g. `validation="required|..."`). It controls whether the label shows a required marker; it does not by itself add HTML validation.

### Passthrough attributes

Any other attribute you put on the tag is merged onto the `<input>`. Notably:

- `class` is merged with the base `form-control` class.
- `init="coloris"` is force-merged onto the input (you do not need to add it; it is how the picker is initialized).
- `name`, `value`, `placeholder`, etc. pass straight through to the input. Use `value="#rrggbb"` to seed the initial color.
- Coloris options can be supplied as `coloris-*` attributes (see [Coloris init](#javascript-coloris-init)).

There are no named slots; the component is self-closing.

## JavaScript (Coloris init)

The input carries `init="coloris"`, which the dashboard init system maps to `public/assets/inits/coloris.js`. That initializer:

- Assigns a unique `coloris-id` attribute to the input if it lacks one, and targets Coloris at `[coloris-id="..."]`.
- Reads per-element options from `coloris-*` attributes on the input (via `getOptionsFromSelector(selector, 'coloris-')`).
- Merges them over these defaults:
  - `rtl`: `true` when `<html dir="rtl">`
  - `themeMode`: current value of `localStorage[window.themerKey]` (light/dark sync)
  - `theme`: `'polaroid'`
  - `formatToggle`: `true`
- Stores the created instance on the element via `$input.data('coloris', picker)`.

This wraps the vendored Coloris library at `public/vendor/coloris/coloris.min.js` / `coloris.min.css`.

## Usage

Basic labelled pickers with seeded values (from `resources/views/dashboard/qr-code/index.blade.php`):

```blade
<x-color-picker name="background" value="#ffffff" :title="__('Background Color')" />

<x-color-picker name="foreground" value="#000000" :title="__('Foreground Color')" />
```

With a hint and required validation marker:

```blade
<x-color-picker
    name="brand_color"
    value="#3b82f6"
    :title="__('Brand Color')"
    :hint="__('Used across buttons and links')"
    validation="required"
/>
```

Passing a Coloris option through a `coloris-*` attribute (e.g. disabling the format toggle):

```blade
<x-color-picker name="accent" value="#ff0000" coloris-format-toggle="false" />
```

## Gotchas

- The `required` marker on the label is driven only by the `validation` attribute containing the substring `required`; it does not enforce browser/server validation on its own.
- The stylesheet and script are emitted with `@pushOnce`, so the `plugins-styles` and `plugins-scripts` stacks must be rendered in your layout for the picker to work.
- If you do not pass `id`, one is auto-generated, so the label-to-input association still works without manual ids.
