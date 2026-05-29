# Coloris Initializer

The `coloris` initializer wires up the [Coloris](https://github.com/mdbassit/Coloris) color-picker library to a text input. It is the engine behind the [`<x-color-picker>` Blade component](/components/color-picker) and is auto-triggered by the dashboard's `init` system.

## What it is

`coloris` is one of the dashboard's initializers, registered on the global `window.__inits` map (built into `public/assets/dist/init.js` from `public/assets/inits/coloris.js`). The init runner in `functions.js` scans the DOM for `[init]:not([initialized])` elements and calls the matching initializer for each space-separated token in the element's `init` attribute. The `<x-color-picker>` component renders an input with `init="coloris"`, so the picker is created automatically — no manual JS is required.

The initializer function has the signature:

```js
(selector, options = {}) => { ... }
```

- `selector` — the input element (passed by the init runner).
- `options` — an options object. When the component sets `init="coloris"` with no value, this is `{}`; the runner only passes options if the element also has an attribute literally named `coloris`.

## What it does

For each matched input the initializer:

1. **Assigns a stable id.** If the input has no `coloris-id` attribute, it sets one to a value from `_.uniqueId('coloris-')` (e.g. `coloris-7`). Coloris targets elements by a CSS selector, so this guarantees a unique, addressable handle per input.
2. **Builds default options:**

   ```js
   const defaultOptions = {
       rtl: $('html').attr('dir') === 'rtl',
       themeMode: localStorage.getItem(window.themerKey),
       theme: 'polaroid',
       formatToggle: true,
   };
   ```

   | Option | Default | Source / meaning |
   | --- | --- | --- |
   | `rtl` | `true` when `<html dir="rtl">` | Mirrors the picker layout for RTL locales. |
   | `themeMode` | value of `localStorage[window.themerKey]` | Light/dark mode, read from the [themer](/frontend/theming) (`window.themerKey` defaults to `'theme'`). |
   | `theme` | `'polaroid'` | Coloris visual theme. |
   | `formatToggle` | `true` | Lets the user switch between HEX/RGB/HSL output. |

3. **Merges in per-element and call-time options.** It reads `coloris-`-prefixed attributes off the element via `getOptionsFromSelector(selector, 'coloris-')` (which strips the prefix and camelCases the remaining key), then merges everything with `_.merge(defaultOptions, selectorOptions, options)` — so element attributes override defaults, and the runner-passed `options` argument wins last.
4. **Instantiates Coloris** scoped to this input:

   ```js
   const picker = new Coloris({
       el: `[coloris-id="${$input.attr('coloris-id')}"`,
       ...options,
   });
   ```
5. **Stores the instance** on the input with jQuery data: `$input.data('coloris', picker)`, so it can be retrieved later via `$(input).data('coloris')`.

### Vendor library

Wraps **Coloris** (`mdbassit/coloris`). The CSS and JS are loaded by the `<x-color-picker>` component from `vendor/coloris/coloris.min.css` and `vendor/coloris/coloris.min.js` (pushed once to the `plugins-styles` / `plugins-scripts` stacks).

## Auto-initialization

The init system binds on the `init` attribute, and the `coloris-`-prefixed attribute namespace is reserved for passing options declaratively. Any `coloris-<name>` attribute on the input becomes a (camelCased) Coloris option:

```blade
<input type="text" init="coloris" coloris-theme="large" coloris-format-toggle="false" />
```

This resolves to `{ theme: 'large', formatToggle: false }`, merged over the defaults.

## Usage

In practice you never call the initializer directly — you use the [`<x-color-picker>` component](/components/color-picker), which emits `<input ... init="coloris">`.

Real usage from the dashboard (`resources/views/dashboard/qr-code/index.blade.php`):

```blade
<x-color-picker name="background" value="#ffffff" :title="__('Background Color')" />
<x-color-picker name="foreground" value="#000000" :title="__('Foreground Color')" />
```

The component (`App\View\Components\ColorPicker`) exposes these props:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `id` | `?string` | `uniqid('color-picker-')` | Input id; auto-generated if omitted. |
| `title` | `?string` | `null` | Renders an `<x-label>` above the input. |
| `hint` | `?string` | `null` | Renders an `<x-hint>` below the input. |

Any extra attributes (e.g. `name`, `value`, `validation`) pass through to the underlying `<input>`. A `validation` containing `required` marks the label as required.

## Gotchas

- The input is given a `coloris-id` only if it lacks one; supply your own `coloris-id` to address the picker deterministically.
- `themeMode` reads `localStorage[window.themerKey]` at init time, so the picker's light/dark appearance follows the [themer](/frontend/theming); it does not live-update if the theme changes after initialization.
- The init runner sets `initialized` on the element after running, so an input is only wired once. Re-running `init()` on the same node is a no-op.

## Related

- [Color Picker component](/components/color-picker) — the Blade component that triggers this initializer.
- [Themer](/frontend/theming) — provides `window.themerKey` used for `themeMode`.
