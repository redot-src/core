# Translatable

`<x-translatable>` renders one form field per configured locale, grouped into
tabs, so a single multilingual attribute can be edited in every language side by
side. It wraps any other field component (input, rich editor, …) you choose.

## Usage

```blade
<x-translatable component="input" name="title" :title="__('Title')" :value="$entry?->getTranslations('title') ?? old('title')" validation="required" />
```

Each locale gets its own field with the name suffixed by the locale
(`title[en]`, `title[ar]`, …). Pass `value` as a `locale => value` map (Spatie's
`getTranslations()` returns exactly this). Any extra attribute (`type`,
`placeholder`, …) is forwarded to each per-locale field. Pair it with
[`<x-translatable-switcher>`](/components/translatable-switcher) to flip every
field's locale at once.

## Options

- **`component`** — which field component to render per locale (`input` by
  default; e.g. `rich-editor`).
- **`name`** — base field name; each locale field becomes `name[locale]`.
- **`value`** — a `locale => value` map of initial values.
- **`title`** — label shown above the field.
- **`validation`** — validation rules applied to each locale's field. See
  [RedotValidator](/frontend/plugins/redot-validator).
- **`locales`** — which locales to render. Accepts an array or a CSV string;
  defaults to the app's configured locales.
- **`validate-locales`** — keep the validation rule only for these locales.
- **`validate-only-main-locale`** — keep validation only for the first locale.
- **`id`** — element id; auto-generated when omitted.

## Examples

### Translatable input and rich editor

```blade
<x-translatable component="input" name="title"
    :value="$entry ? $entry->getTranslations('title') : old('title')"
    :title="__('Title')" validation="required" />

<x-translatable component="rich-editor" name="content"
    :value="$entry ? $entry->getTranslations('content') : old('content')"
    :title="__('Content')" />
```

### Restricting the locales

```blade
<x-translatable component="input" name="title" :title="__('Title')" :locales="['en', 'ar']" />
```

## Related

- [Translatable Switcher](/components/translatable-switcher) — flip every field's locale at once.
- [Input](/components/input) / [Rich Editor](/components/rich-editor) — common inner field components.
- [Components overview](/components/overview) — shared form-field conventions.
