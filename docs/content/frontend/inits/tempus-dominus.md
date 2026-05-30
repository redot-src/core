# Tempus Dominus Initializer

`tempus-dominus` turns an input into a date / time picker (powered by [Tempus Dominus](https://getdatepicker.com/)). It backs the [`<x-date-picker>` component](/components/date-picker) and follows the page's light/dark theme and text direction.

## Enable it

You rarely write `init="tempus-dominus"` yourself — the [`<x-date-picker>` component](/components/date-picker) adds it for you:

```blade
<x-date-picker name="published_at" :title="__('Published at')" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Pick the mode with a flag attribute on the field:

- **`datetime`** — show both calendar and clock (format `yyyy-MM-dd hh:mm T`).
- **`only-time`** — clock only, no calendar (format `hh:mm T`).
- *(neither)* — calendar only (format `yyyy-MM-dd`).

```blade
<x-date-picker name="date" :title="__('Date')" :value="old('date', now())" datetime />
```

Tune the picker further with `date-` attributes (kebab-case maps to nested Tempus Dominus options):

- **`date-localization-format`** — display/parse format, e.g. `date-localization-format="dd/MM/yyyy"`.

```blade
<x-date-picker name="dob" :title="__('Date of birth')" date-localization-format="dd/MM/yyyy" />
```

The picker re-themes itself when the user toggles light/dark.

## Related

- [Date Picker component](/components/date-picker) — the component that enables this for you.
- [Theming](/frontend/theming) — the light/dark mode the picker follows.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
