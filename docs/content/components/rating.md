# Rating

`<x-rating>` renders a star-rating input. The selected star count submits as a
normal form value, so it needs no JavaScript and you can style the star count,
size, and icon.

## Usage

```blade
<x-rating name="quality" :title="__('Service quality')" :value="$review->quality" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). `value` is the selected star
count (a number from 1 to `stars`).

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — the pre-selected star count.
- **`stars`** — number of stars to render (5 by default).
- **`size`** — star size. A bare number is treated as pixels (e.g. `32` →
  `32px`); pass a full CSS length (e.g. `2rem`) for other units.
- **`icon`** — icon used for each star (a star by default).
- **`id`** — element id; auto-generated when omitted.

## Examples

### Custom star count and icon

```blade
<x-rating
    name="difficulty"
    :title="__('Difficulty')"
    :hint="__('Pick from 1 to 10')"
    :stars="10"
    :size="32"
    icon="ti ti-circle"
    validation="required"
/>
```

### Heart rating

```blade
<x-rating name="satisfaction" :title="__('Satisfaction')" icon="ti ti-heart" />
```

## Related

- [Icon](/components/icon) — render a single icon.
- [Components overview](/components/overview) — shared form-field conventions.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
