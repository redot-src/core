# Icon

`<x-icon>` renders an icon — either from an icon-font class string (Font Awesome
style) or from a raw inline SVG/markup string.

## Usage

```blade
<x-icon icon="fas fa-plus" />
```

## Options

- **`icon`** — required. Either an icon class string (e.g. `fas fa-plus`) or a
  raw markup string starting with `<` (e.g. an inline `<svg>`).

When you pass a class string, extra `class` values you add are merged onto the
rendered element.

## Examples

### Dynamic icon with an extra size class

```blade
<x-icon :icon="$item->icon" class="fa-3x" />
```

## Related

- [Icon Picker](/components/icon-picker) — UI for picking an icon class.
