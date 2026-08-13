# Icon Picker

`<x-icon-picker>` is a text field bound to a Tabler icon, with a live
preview and a search button that opens a modal to browse and pick locally hosted
Tabler icons. The selected icon class string (e.g. `ti ti-note`) is
written back into the field.

## Usage

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $category?->icon)" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). The stored value is a full Tabler
icon class string, so seed `value` with that full string. The
picker initializes itself through the [asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — the current icon class string (e.g. `ti ti-note`).
- **`id`** — element id; auto-generated when omitted.

## Examples

### Icon field with a default

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $category?->icon ?? 'ti ti-note')" />
```

### With a hint and required marker

```blade
<x-icon-picker
    name="icon"
    :title="__('Icon')"
    :hint="__('Pick a Tabler icon')"
    validation="required"
    :value="old('icon')"
/>
```

The icon search reads the locally hosted Tabler Icons stylesheet and needs no
external API access.

## Related

- [Icon Picker init](/frontend/inits/icon-picker) — the JS behind the picker.
- [Icon](/components/icon) — render a single icon.
- [Components overview](/components/overview) — shared form-field conventions.
