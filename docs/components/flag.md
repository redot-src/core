# Flag

`<x-flag>` renders a single country flag glyph. It's a lightweight,
presentation-only component that pulls in its own stylesheet on first use.

## Usage

```blade
<x-flag code="us" size="lg" />
```

## Options

- **`code`** — ISO country code to display (`eg` by default).
- **`size`** — flag size: `xs`, `sm`, `md` (default), `lg`.

Extra attributes (`class`, `title`, …) are merged onto the element.

## Examples

### Flag with a label

```blade
<x-flag code="fr" size="sm" class="me-2" title="France" />
```

## Related

- [Countries](/components/countries) — a selectable, flag-decorated country dropdown.
- [Localization](/foundation/localization) — country and locale display.
