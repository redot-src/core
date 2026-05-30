# Translatable Switcher

`<x-translatable-switcher>` renders a globe dropdown of the app's locales.
Picking one switches every [`<x-translatable>`](/components/translatable) field
on the page to that language at once — handy in a form header so editors can flip
languages without touching each field's tabs.

## Usage

```blade
<x-translatable-switcher />
```

Place it on the same page as your translatable fields (commonly in a form card's
header slot). It activates the matching tab on every `<x-translatable>` present.

## Options

- **`locales`** — which locales to list. Defaults to the app's configured
  locales. Each is shown with its label and key, e.g. `English (en)`.
- **`id`** — element id; auto-generated when omitted.

Any extra `class` is added to the dropdown styling.

## Examples

### In a form card header

```blade
<x-layouts::dashboard>
    <x-form-card resource="static-pages">
        <x-slot:header>
            <x-translatable-switcher />
        </x-slot:header>
    </x-form-card>
</x-layouts::dashboard>
```

### Restricting the listed locales

```blade
<x-translatable-switcher :locales="['en', 'ar']" />
```

## Related

- [Translatable](/components/translatable) — the per-locale fields this switches.
- [Form Card](/components/form-card) — where the switcher commonly lives.
