# Countries

`<x-countries>` is a searchable country picker — a dropdown of countries, each
shown with its flag, that stores the selected country's ISO code (e.g. `eg`,
`us`) as the value.

## Usage

```blade
<x-countries name="country" :title="__('Country')" />
```

It builds on [`<x-select>`](/components/select), so every select attribute
(`name`, `title`, `hint`, `value`, `multiple`, `validation`, …) works here too.

## Options

- **`title`** — label shown above the field.
- **`hint`** — small helper text shown beneath the field.
- **`value`** — pre-selected country code(s). Accepts a single code or several.
- **`multiple`** — allow selecting more than one country, with removable chips.

## Examples

### Pre-selecting a country

The value is the ISO code, not a database id:

```blade
<x-countries name="country" :title="__('Country')" :value="$user->country" />
```

### Selecting multiple countries

```blade
<x-countries
    name="countries[]"
    multiple
    :title="__('Operating countries')"
    :hint="__('Select all countries where the service is available.')"
/>
```

## Related

- [Select](/components/select) — the underlying component and all its options.
- [Flag](/components/flag) — render a single standalone flag.
- [Localization](/foundation/localization) — country and locale display.
