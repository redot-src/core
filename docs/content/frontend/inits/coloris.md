# Coloris Initializer

`coloris` turns a text input into a color picker (powered by [Coloris](https://github.com/mdbassit/Coloris)). Reach for it when you need a hex/rgb/hsl color field. It backs the [`<x-color-picker>` component](/components/color-picker).

## Enable it

Mark an input with `init="coloris"`. You rarely write this yourself — the `<x-color-picker>` component adds it for you:

```blade
<x-color-picker name="background" value="#ffffff" :title="__('Background Color')" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Set these as `coloris-` attributes on the input:

- **`coloris-theme`** — picker style (`polaroid` by default; also `large`, `pill`, …).
- **`coloris-format-toggle`** — let the user switch HEX / RGB / HSL output (on by default).

The picker follows the page's light/dark theme and text direction automatically.

```blade
<input type="text" init="coloris" coloris-theme="large" coloris-format-toggle="false" />
```

## Related

- [Color Picker component](/components/color-picker) — the component that enables this for you.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
- [Theming](/frontend/theming) — the light/dark mode the picker follows.
