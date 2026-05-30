# Date Picker

`<x-date-picker>` is a text field with a calendar/time picker, backed by Tempus
Dominus. It renders a labelled, hinted input group with a calendar icon and can
pick dates, date-times, or times only.

## Usage

```blade
<x-date-picker name="date" :title="__('Date')" :value="old('date', $entry?->date)" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). The picker initializes itself
through the [asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — initial value; defaults to `yyyy-MM-dd`. Use `old()` to preserve
  input on validation errors.
- **`datetime`** — also pick a time; the value format becomes `yyyy-MM-dd hh:mm T`.
- **`only-time`** — pick a time only, with no calendar; format becomes `hh:mm T`.
- **`id`** — element id; auto-generated when omitted.

Additional picker behavior can be set per element with `date-*` attributes.

## Examples

### Date and time field

```blade
<x-date-picker
    name="date"
    :title="__('Date')"
    :value="old('date', $entry?->date ?? now())"
    datetime
/>
```

### Time-only picker

```blade
<x-date-picker name="time" :title="__('Time')" only-time />
```

## Related

- [Tempus Dominus init](/frontend/inits/tempus-dominus) — the JS behind the picker.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
